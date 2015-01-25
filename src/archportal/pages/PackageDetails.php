<?php

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
use archportal\lib\Output;
use archportal\lib\Page;
use archportal\lib\RequestException;
use PDO;
use RuntimeException;

class PackageDetails extends Page
{

    private $pkgid = 0;
    private $repo = '';
    private $arch = '';
    private $pkgname = '';

    public function prepare()
    {
        $this->setTitle($this->l10n->getText('Package details'));
        try {
            $this->repo = Input::get()->getString('repo');
            $this->arch = Input::get()->getString('arch');
            $this->pkgname = Input::get()->getString('pkgname');
        } catch (RequestException $e) {
            $this->showFailure($this->l10n->getText('No package specified'));
        }

        $repository = Database::prepare('
            SELECT
                repositories.id
            FROM
                repositories
                    JOIN architectures
                    ON architectures.id = repositories.arch
            WHERE
                repositories.name = :repositoryName
                AND architectures.name = :architectureName
            ');
        $repository->bindParam('repositoryName', $this->repo, PDO::PARAM_STR);
        $repository->bindParam('architectureName', $this->arch, PDO::PARAM_STR);
        $repository->execute();

        $stm = Database::prepare('
            SELECT
                packages.id,
                packages.filename,
                packages.name,
                packages.base,
                packages.version,
                packages.desc,
                packages.csize,
                packages.isize,
                packages.md5sum,
                packages.sha256sum,
                packages.pgpsig,
                packages.url,
                packages.builddate,
                packages.mtime,
                architectures.name AS architecture,
                repositories.name AS repository,
                packagers.name AS packager,
                packagers.id AS packagerid,
                packagers.email AS packageremail
            FROM
                packages
                    LEFT JOIN packagers ON packages.packager = packagers.id,
                architectures,
                repositories
            WHERE
                repositories.id = :repositoryId
                AND packages.name = :package
                AND packages.arch = architectures.id
                AND packages.repository = repositories.id
        ');
        $stm->bindValue('repositoryId', $repository->fetchColumn(), PDO::PARAM_STR);
        $stm->bindParam('package', $this->pkgname, PDO::PARAM_STR);
        $stm->execute();
        $data = $stm->fetch();
        if ($data === false) {
            $this->setStatus(Output::NOT_FOUND);
            $this->showFailure($this->l10n->getText('Package was not found'));
        }
        $this->pkgid = $data['id'];
        $this->setTitle($data['name']);
        $cgitUrl = Config::get('packages', 'cgit') . (in_array($data['repository'], array(
                    'community',
                    'community-testing',
                    'multilib',
                    'multilib-testing'
                )) ? 'community' : 'packages')
                . '.git/';
        $body = '<div class="box">
        <h2>' . $data['name'] . '</h2>
        <table id="packagedetails">
            <tr>
                <th colspan="2" class="packagedetailshead">' . $this->l10n->getText('Package details') . '</th>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Name') . '</th>
                <td>' . $data['name'] . '</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Version') . '</th>
                <td>' . $data['version'] . '</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Description') . '</th>
                <td>' . $data['desc'] . '</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('URL') . '</th>
                <td><a rel="nofollow" href="' . $data['url'] . '">' . $data['url'] . '</a></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Licenses') . '</th>
                <td>' . $this->getLicenses() . '</td>
            </tr>
            <tr>
                <th colspan="2" class="packagedetailshead">' . $this->l10n->getText('Package details') . '</th>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Repository') . '</th>
                <td><a href="' . $this->createUrl('Packages', array('repository' => $data['repository'])) . '">' . $data['repository'] . '</a></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Architecture') . '</th>
                <td><a href="' . $this->createUrl('Packages', array('architecture' => $data['architecture'])) . '">' . $data['architecture'] . '</a></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Groups') . '</th>
                <td>' . $this->getGroups() . '</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Packager') . '</th>
                <td><a href="' . $this->createUrl('Packages', array('packager' => $data['packagerid'])) . '">' . $data['packager'] . '</a>' . (!empty($data['packageremail']) ? ' <a rel="nofollow" href="mailto:' . $data['packageremail'] . '">@</a>' : '') . '</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Build date') . '</th>
                <td>' . $this->l10n->getDateTime($data['builddate']) . '</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Publish date') . '</th>
                <td>' . $this->l10n->getDateTime($data['mtime']) . '</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Source code') . '</th>
                <td><a href="' . $cgitUrl . 'tree/trunk?h=packages/' . $data['base'] . '">' . $this->l10n->getText('Source Files') . '</a>,
                <a href="' . $cgitUrl . 'log/trunk?h=packages/' . $data['base'] . '">' . $this->l10n->getText('Changelog') . '</a></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Bugs') . '</th>
                <td><a href="https://bugs.archlinux.org/index.php?string=%5B' . $data['name'] . '%5D">' . $this->l10n->getText('Bug Tracker') . '</a></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Package') . '</th>
                <td><a href="' . $this->createUrl('GetFileFromMirror', array('file' => $data['repository'] . '/os/' . $this->arch . '/' . $data['filename'])) . '">' . $data['filename'] . '</a></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('MD5 checksum') . '</th>
                <td><code>' . $data['md5sum'] . '</code></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('SHA256 checksum') . '</th>
                <td><code>' . $data['sha256sum'] . '</code></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('PGP signature') . '</th>
                <td><a href="data:application/pgp-signature;base64,' . base64_encode($data['pgpsig']) . '" download="' . $data['filename'] . '.sig">' . $data['filename'] . '.sig</a></td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Package size') . '</th>
                <td>' . $this->formatBytes($data['csize']) . 'Byte</td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('Installation size') . '</th>
                <td>' . $this->formatBytes($data['isize']) . 'Byte</td>
            </tr>
        </table>
        <table id="packagedependencies">
            <tr>
                <th colspan="5" class="packagedependencieshead">' . $this->l10n->getText('Dependencies') . '</th>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('depends on') . '</th>
                <th>' . $this->l10n->getText('required by') . '</th>
                <th>' . $this->l10n->getText('provides') . '</th>
                <th>' . $this->l10n->getText('conflicts with') . '</th>
                <th>' . $this->l10n->getText('replaces') . '</th>
            </tr>
            <tr>
                <td>
                    ' . $this->getRelations('depends') . '
                </td>
                <td>
                    ' . $this->getInverseRelations('depends') . '
                </td>
                <td>
                    ' . $this->getRelations('provides') . '
                </td>
                <td>
                    ' . $this->getRelations('conflicts') . '
                </td>
                <td>
                    ' . $this->getRelations('replaces') . '
                </td>
            </tr>
            <tr>
                <th>' . $this->l10n->getText('optionally depends on') . '</th>
                <th>' . $this->l10n->getText('optionally required by') . '</th>
                <th>' . $this->l10n->getText('make depends on') . '</th>
                <th>' . $this->l10n->getText('make required by') . '</th>
                <th>' . $this->l10n->getText('check depends on') . '</th>
            </tr>
            <tr>
                <td>
                    ' . $this->getRelations('optdepends') . '
                </td>
                <td>
                    ' . $this->getInverseRelations('optdepends') . '
                </td>
                <td>
                    ' . $this->getRelations('makedepends') . '
                </td>
                <td>
                    ' . $this->getInverseRelations('makedepends') . '
                </td>
                <td>
                    ' . $this->getRelations('checkdepends') . '
                </td>
            </tr>
        </table>';

        if (Config::get('packages', 'files')) {
            $body .= '<table id="packagefiles">
                <tr>
                    <th class="packagefileshead">' . $this->l10n->getText('Files') . '</th>
                </tr>
                <tr>
                    <td>
                        ' . (Input::get()->isInt('showfiles') ? $this->getFiles() : '<a style="font-size:10px;margin:10px;" href="' . $this->createUrl('PackageDetails', array('repo' => $this->repo, 'arch' => $this->arch, 'pkgname' => $this->pkgname, 'showfiles' => '1')) . '">' . $this->l10n->getText('Show files') . '</a>') . '
                    </td>
                </tr>
            </table>';
        }

        $body .= '</div>';
        $this->setBody($body);
    }

    private function formatBytes($bytes)
    {
        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        if ($bytes >= $gb) { // GB

            return round($bytes / $gb, 2) . ' G';
        } elseif ($bytes >= $mb) { // MB

            return round($bytes / $mb, 2) . ' M';
        } elseif ($bytes >= $kb) { // KB

            return round($bytes / $kb, 2) . ' K';
        } else {
        //  B
            return $bytes . ' ';
        }
    }

    private function getLicenses()
    {
        $stm = Database::prepare('
        SELECT
            licenses.name
        FROM
            licenses,
            package_license
        WHERE
            package_license.license = licenses.id
            AND package_license.package = :package
        ');
        $stm->bindParam('package', $this->pkgid, PDO::PARAM_INT);
        $stm->execute();
        $list = array();
        while (($license = $stm->fetchColumn())) {
            $list[] = $license;
        }

        return implode(', ', $list);
    }

    private function getGroups()
    {
        $groups = Database::prepare('
            SELECT
                groups.name
            FROM
                groups,
                package_group
            WHERE
                package_group.group = groups.id
                AND package_group.package = :package
        ');
        $groups->bindParam('package', $this->pkgid, PDO::PARAM_INT);
        $groups->execute();
        $list = array();
        while (($group = $groups->fetchColumn())) {
            $list[] = '<a href="' . $this->createUrl('Packages', array('group' => $group)) . '">' . $group . '</a>';
        }

        return implode(', ', $list);
    }

    private function getFiles()
    {
        $stm = Database::prepare('
            SELECT
                path
            FROM
                files
            WHERE
                package = :package
            ORDER BY
                path
        ');
        $stm->bindParam('package', $this->pkgid, PDO::PARAM_INT);
        $stm->execute();

        $list = '';
        if ($stm->rowCount() > 0) {
            $last = 0;
            $cur = 0;
            while (($path = $stm->fetchColumn())) {
                $cur = substr_count($path, '/');
                if (substr($path, -1) != '/') {
                    $cur++;
                }

                if ($cur == $last + 1) {
                    $list .= '<ul>';
                } elseif ($cur < $last) {
                    $list .= '</li>' . str_repeat('</ul></li>', $last - $cur);
                } elseif ($cur > $last + 1) {
                    throw new RuntimeException('incorrect list depth');
                } else {
                    $list .= '</li>';
                }

                $list .= '<li>' . basename($path);
                $last = $cur;
            }

            $list .= str_repeat('</li></ul>', $cur);
        }

        return $list;
    }

    private function getRelations($type)
    {
        $stm = Database::prepare('
        SELECT
            packages.id,
            package_relation.dependsName AS name,
            package_relation.dependsVersion AS version,
            architectures.name AS arch,
            repositories.name AS repo
        FROM
            package_relation
                LEFT JOIN packages
                ON package_relation.dependsId = packages.id
                LEFT JOIN repositories
                ON packages.repository = repositories.id
                LEFT JOIN architectures
                ON repositories.arch = architectures.id
        WHERE
            package_relation.packageId = :packageId
            AND package_relation.type = :type
        ORDER BY
            package_relation.dependsName
        ');
        $stm->bindParam('packageId', $this->pkgid, PDO::PARAM_INT);
        $stm->bindParam('type', $type, PDO::PARAM_STR);
        $stm->execute();
        $list = '<ul>';
        foreach ($stm as $dependency) {
            if (is_null($dependency['id'])) {
                $list.= '<li>' . $dependency['name'] . $dependency['version'] . '</li>';
            } else {
                $list.= '<li><a href="' . $this->createUrl('PackageDetails', array('repo' => $dependency['repo'], 'arch' => $dependency['arch'], 'pkgname' => $dependency['name'])) . '">' . $dependency['name'] . '</a>' . $dependency['version'] . '</li>';
            }
        }
        $list.= '</ul>';

        return $list;
    }

    private function getInverseRelations($type)
    {
        $stm = Database::prepare('
        SELECT
            packages.name,
            package_relation.dependsVersion AS version,
            architectures.name AS arch,
            repositories.name AS repo
        FROM
            package_relation,
            packages,
            architectures,
            repositories
        WHERE
            package_relation.dependsId = :packageId
            AND package_relation.packageId = packages.id
            AND package_relation.type = :type
            AND packages.repository = repositories.id
            AND repositories.arch = architectures.id
        ORDER BY
            packages.name
        ');
        $stm->bindParam('packageId', $this->pkgid, PDO::PARAM_INT);
        $stm->bindParam('type', $type, PDO::PARAM_STR);
        $stm->execute();
        $list = '<ul>';
        foreach ($stm as $dependency) {
            $list.= '<li><a href="' . $this->createUrl('PackageDetails', array('repo' => $dependency['repo'], 'arch' => $dependency['arch'], 'pkgname' => $dependency['name'])) . '">' . $dependency['name'] . '</a>' . $dependency['version'] . '</li>';
        }
        $list.= '</ul>';

        return $list;
    }

}
