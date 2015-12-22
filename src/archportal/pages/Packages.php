<?php

declare (strict_types = 1);

/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\pages;

use archportal\lib\Config;
use archportal\lib\Database;
use archportal\lib\Input;
use archportal\lib\Page;
use PDO;

class Packages extends Page
{
    /** @var int */
    private $page = 1;
    /** @var int */
    private $packagesPerPage = 50;
    /** @var string */
    private $orderby = '';
    /** @var string */
    private $sort = '';
    /** @var array */
    private $repository = array('id' => '', 'name' => '');
    /** @var array */
    private $architecture = array('id' => '', 'name' => '');
    /** @var int */
    private $group = 0;
    /** @var int */
    private $packager = 0;
    /** @var string */
    private $search = '';
    /** @var string */
    private $searchString = '';
    /** @var int */
    private $searchField = 0;

    public function prepare()
    {
        $this->setTitle($this->l10n->getText('Package search'));

        $this->addJS('jquery.min');
        $this->addJS('jquery.ui.core.min');
        $this->addJS('jquery.ui.widget.min');
        $this->addJS('jquery.ui.position.min');
        $this->addJS('jquery.ui.menu.min');
        $this->addJS('jquery.ui.autocomplete.min');
        $this->addCSS('jquery.ui.core.min');
        $this->addCSS('jquery.ui.theme.min');
        $this->addCSS('jquery.ui.menu.min');
        $this->addCSS('jquery.ui.autocomplete.min');

        $this->initParameters();

        $packages = Database::prepare('
            SELECT
                packages.id,
                packages.name,
                packages.version,
                packages.desc,
                packages.builddate,
                pkgarch.name AS architecture,
                repositories.name AS repository,
                repositories.testing,
                repoarch.name AS repositoryArchitecture
            FROM
                packages,
                repositories
                    JOIN architectures repoarch
                    ON repoarch.id = repositories.arch,
                architectures pkgarch
                '.(!empty($this->group) ? ',package_group, groups' : '').'
                '.(!empty($this->search) && $this->searchField == 'file' ? ',file_index, package_file_index' : '').'
            WHERE
                packages.repository = repositories.id
                '.(!empty($this->repository['name']) ? 'AND repositories.name = :repositoryName' : '').'
                AND packages.arch = pkgarch.id
                '.(!empty($this->architecture['id']) ? 'AND repositories.arch = :architectureId' : '').'
                '.(!empty($this->group) ? 'AND package_group.package = packages.id AND package_group.group = groups.id AND groups.name = :group' : '').'
                '.(empty($this->search) ? '' : $this->getSearchStatement()).'
                '.(!empty($this->search) && $this->searchField == 'file' ? ' GROUP BY packages.id ' : ' ').'
                '.($this->packager > 0 ? ' AND packages.packager = '.$this->packager : '').'
            ORDER BY
                '.$this->orderby.' '.$this->sort.'
            LIMIT
                '.(($this->page - 1) * $this->packagesPerPage).', '.$this->packagesPerPage.'
            ');
        !empty($this->repository['name']) && $packages->bindValue('repositoryName', $this->repository['name'],
            PDO::PARAM_STR);
        !empty($this->architecture['id']) && $packages->bindValue('architectureId', $this->architecture['id'],
            PDO::PARAM_INT);
        !empty($this->group) && $packages->bindValue('group', $this->group, PDO::PARAM_STR);
        !empty($this->search) && $packages->bindValue('search', $this->searchString, PDO::PARAM_STR);
        $packages->execute();

        $body = '
        <div class="box">
        <h2>'.$this->getTitle().'</h2>
        <form method="get">
        <input type="hidden" name="page" value="Packages" />
        <table id="searchbox">
            <tr>
                <th>'.$this->l10n->getText('Repository').'</th>
                <th>'.$this->l10n->getText('Architecture').'</th>
                <th>'.$this->l10n->getText('Group').'</th>
                <th>'.$this->l10n->getText('Keywords').'</th>
            </tr>
            <tr>
                <td>
                    '.$this->getRepositoryList().'
                </td>
                <td>
                    '.$this->getArchitectureList().'
                </td>
                <td>
                    '.$this->getGroupList().'
                </td>
                <td>
                    <input type="text" name="search" id="searchfield" class="ui-autocomplete-input" value="'.$this->search.'" size="34" maxlength="50" autocomplete="off" />
                    <div style="padding-top: 5px;">'.$this->getSearchFields().'</div>
                    '.(in_array($this->searchField, array(
                'name',
                'file',
            )) ? '			<script>
                        $(function () {
                            $("#searchfield").autocomplete({
                                source: "'.$this->createUrl('PackagesSuggest', array(
                    'repository' => $this->repository['id'],
                    'architecture' => $this->architecture['id'],
                    'field' => $this->searchField,
                )).'",
                                minLength: 2,
                                delay: 100
                            });
                        });
                    </script>' : '').'
                    <input type="hidden" name="packager" value="'.$this->packager.'" />
                </td>
            </tr>
        </table>
        </form>
        </div>';
        $body .= $this->showPackageList($packages);
        $this->setBody($body);
    }

    /**
     * @param string $architecture
     *
     * @return array
     */
    private function getAvailableRepositories(string $architecture = ''): array
    {
        if (empty($architecture)) {
            return array_keys(Config::get('packages', 'repositories'));
        } else {
            $repositories = array();
            foreach (Config::get('packages', 'repositories') as $repository => $architectures) {
                if (in_array($architecture, $architectures)) {
                    $repositories[] = $repository;
                }
            }

            return $repositories;
        }
    }

    /**
     * @param string $repositoryName
     * @param int    $architectureId
     *
     * @return int
     */
    private function getRepositoryId(string $repositoryName, int $architectureId): int
    {
        $stm = Database::prepare('
            SELECT
                id
            FROM
                repositories
            WHERE
                name = :repositoryName
                AND arch = :architectureId
            ');
        $stm->bindParam('repositoryName', $repositoryName, PDO::PARAM_STR);
        $stm->bindParam('architectureId', $architectureId, PDO::PARAM_INT);
        $stm->execute();

        return (int) $stm->fetchColumn();
    }

    /**
     * @param string $repository
     *
     * @return array
     */
    private function getAvailableArchitectures(string $repository = ''): array
    {
        if (empty($repository)) {
            $uniqueArchitectures = array();
            foreach (Config::get('packages', 'repositories') as $architectures) {
                foreach ($architectures as $architecture) {
                    $uniqueArchitectures[$architecture] = 1;
                }
            }

            return array_keys($uniqueArchitectures);
        } else {
            $repositories = Config::get('packages', 'repositories');

            return $repositories[$repository];
        }
    }

    /**
     * @param string $architectureName
     *
     * @return int
     */
    private function getArchitectureId(string $architectureName): int
    {
        $stm = Database::prepare('
            SELECT
                id
            FROM
                architectures
            WHERE
                name = :architectureName
            ');
        $stm->bindParam('architectureName', $architectureName, PDO::PARAM_STR);
        $stm->execute();

        return $stm->fetchColumn();
    }

    private function initParameters()
    {
        $this->orderby = $this->getRequest('orderby', array(
            'builddate',
            'name',
            'repository',
            'architecture',
        ));
        $this->sort = $this->getRequest('sort', array(
            'desc',
            'asc',
        ));
        $this->page = Input::get()->getInt('p', 1);

        $this->repository['name'] = $this->getRequest('repository', $this->getAvailableRepositories(), '');
        $this->architecture['name'] = $this->getRequest('architecture',
            $this->getAvailableArchitectures($this->repository['name']),
            (Input::get()->isRequest('architecture') ? '' : Config::get('packages', 'default_architecture'))
        );
        $this->architecture['id'] = $this->getArchitectureId($this->architecture['name']);
        $this->repository['id'] = $this->getRepositoryId($this->repository['name'], $this->architecture['id']);

        $this->group = Input::get()->getString('group', '');
        $this->packager = Input::get()->getInt('packager', 0);

        $this->search = $this->cutString(htmlspecialchars(preg_replace('/[^\w\.\+\- ]/', '',
            Input::get()->getString('search', ''))), 50);
        if (strlen($this->search) < 2) {
            $this->search = '';
        }

        $searchFields = array('name', 'description');
        if (Config::get('packages', 'files')) {
            $searchFields[] = 'file';
        }
        $this->searchField = $this->getRequest('searchfield', $searchFields);
    }

    /**
     * @param string      $name
     * @param array       $allowedValues
     * @param string|null $default
     *
     * @return string
     */
    private function getRequest(string $name, array $allowedValues, $default = null): string
    {
        if (is_null($default)) {
            $default = $allowedValues[0];
        }
        $request = Input::get()->getString($name, $default);
        if (in_array($request, $allowedValues)) {
            return $request;
        } else {
            return $default;
        }
    }

    /**
     * @return string
     */
    private function getSearchStatement(): string
    {
        switch ($this->searchField) {
            case 'name':
                // FIXME: this cannot use any index
                $this->searchString = '%'.$this->search.'%';

                return 'AND packages.name LIKE :search';
                break;
            case 'description':
                // FIXME: this cannot use any index
                $this->searchString = '%'.$this->search.'%';

                return 'AND packages.desc LIKE :search';
                break;
            case 'file':
                // FIXME: this is a very expensive query
                $this->searchString = $this->search.'%';

                return 'AND file_index.name LIKE :search AND file_index.id = package_file_index.file_index AND package_file_index.package = packages.id';
                break;
            default:
                return '';
        }
    }

    /**
     * @return string
     */
    private function getSearchFields(): string
    {
        $options = '';
        $searchFields = array(
            'name' => $this->l10n->getText('Name'),
            'description' => $this->l10n->getText('Description'),
        );
        if (Config::get('packages', 'files')) {
            $searchFields['file'] = $this->l10n->getText('File');
        }
        foreach ($searchFields as $key => $value) {
            if ($key == $this->searchField) {
                $selected = ' checked="checked"';
            } else {
                $selected = '';
            }
            $options .= ' <input type="radio" id="searchfield_'.$key.'" name="searchfield" value="'.$key.'"'.$selected.'  onchange="this.form.submit()" /> <label for="searchfield_'.$key.'">'.$value.'</label>';
        }

        return $options;
    }

    /**
     * @return string
     */
    private function getRepositoryList(): string
    {
        $options = '<select name="repository" onchange="this.form.submit()">
            <option value="">&nbsp;</option>';

        foreach ($this->getAvailableRepositories($this->architecture['name']) as $repository) {
            $options .= '<option value="'.$repository.'"'.($this->repository['name'] == $repository ? ' selected="selected"' : '').'>'.$repository.'</option>';
        }

        return $options.'</select>';
    }

    /**
     * @return string
     */
    private function getArchitectureList(): string
    {
        $options = '<select name="architecture" onchange="this.form.submit()">
            <option value="">&nbsp;</option>';

        foreach ($this->getAvailableArchitectures($this->repository['name']) as $architecture) {
            $options .= '<option value="'.$architecture.'"'.($this->architecture['name'] == $architecture ? ' selected="selected"' : '').'>'.$architecture.'</option>';
        }

        return $options.'</select>';
    }

    /**
     * @return string
     */
    private function getGroupList(): string
    {
        $options = '<select name="group" onchange="this.form.submit()">
            <option value="">&nbsp;</option>';

        $groups = Database::query('
            SELECT
                name
            FROM
                groups
            ORDER BY
                name ASC
            ');
        while (($group = $groups->fetchColumn())) {
            $options .= '<option value="'.$group.'"'.($this->group == $group ? ' selected="selected"' : '').'>'.$group.'</option>';
        }

        return $options.'</select>';
    }

    /**
     * @param \Traversable $packages
     *
     * @return string
     */
    private function showPackageList(\Traversable $packages): string
    {
        $parameters = array(
            'repository' => $this->repository['name'],
            'architecture' => $this->architecture['name'],
            'group' => $this->group,
            'packager' => $this->packager,
            'search' => $this->search,
            'searchfield' => $this->searchField,
        );

        $newSort = ($this->sort == 'asc' ? 'desc' : 'asc');

        $next = ' <a href="'.$this->createUrl('Packages', array_merge($parameters,
                array('orderby' => $this->orderby, 'sort' => $this->sort, 'p' => ($this->page + 1)))).'">&#187;</a>';
        $prev = ($this->page > 1 ? '<a href="'.$this->createUrl('Packages', array_merge($parameters, array(
                'orderby' => $this->orderby,
                'sort' => $this->sort,
                'p' => max(1, $this->page - 1),
            ))).'">&#171;</a>' : '');

        $body = '<table class="pretty-table">
            <tr>
                <td class="pages" colspan="6">'.$prev.$next.'</td>
            </tr>
            <tr>
                <th><a href="'.$this->createUrl('Packages', array_merge($parameters,
                array('orderby' => 'repository', 'sort' => $newSort))).'">'.$this->l10n->getText('Repository').'</a></th>
                <th><a href="'.$this->createUrl('Packages', array_merge($parameters,
                array('orderby' => 'architecture', 'sort' => $newSort))).'">'.$this->l10n->getText('Architecture').'</a></th>
                <th><a href="'.$this->createUrl('Packages', array_merge($parameters,
                array('orderby' => 'name', 'sort' => $newSort))).'">'.$this->l10n->getText('Name').'</a></th>
                <th>'.$this->l10n->getText('Version').'</th>
                <th>'.$this->l10n->getText('Description').'</th>
                <th><a href="'.$this->createUrl('Packages', array_merge($parameters,
                array('orderby' => 'builddate', 'sort' => $newSort))).'">'.$this->l10n->getText('Last update').'</a></th>
            </tr>';
        foreach ($packages as $package) {
            $style = ($package['testing'] == 1 ? ' class="less"' : '');
            $body .= '<tr'.$style.'>
                <td>'.$package['repository'].'</td><td>'.$package['architecture'].'</td><td><a href="'.$this->createUrl('PackageDetails',
                    array(
                        'repo' => $package['repository'],
                        'arch' => $package['repositoryArchitecture'],
                        'pkgname' => $package['name'],
                    )).'">'.$package['name'].'</a></td><td>'.$package['version'].'</td><td>'.$this->cutString($package['desc'],
                    70).'</td><td>'.$this->l10n->getDateTime($package['builddate']).'</td>
            </tr>';
        }
        $body .= '
            <tr>
                <td class="pages" colspan="6">'.$prev.$next.'</td>
            </tr>
        </table>';

        return $body;
    }
}
