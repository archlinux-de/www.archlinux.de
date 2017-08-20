<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PackagesController extends Controller
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
    /** @var string */
    private $searchField = '';
    /** @var Connection */
    private $database;
    /** @var RouterInterface */
    private $router;

    /**
     * @param Connection $connection
     * @param RouterInterface $router
     */
    public function __construct(Connection $connection, RouterInterface $router)
    {
        $this->database = $connection;
        $this->router = $router;
    }

    /**
     * @Route("/packages", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $this->initParameters($request);

        $packages = $this->database->prepare('
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
                ' . (!empty($this->group) ? ',package_group, groups' : '') . '
                ' . (!empty($this->search) && $this->searchField == 'file' ? ',file_index, package_file_index' : '') . '
            WHERE
                packages.repository = repositories.id
                ' . (!empty($this->repository['name']) ? 'AND repositories.name = :repositoryName' : '') . '
                AND packages.arch = pkgarch.id
                ' . (!empty($this->architecture['id']) ? 'AND repositories.arch = :architectureId' : '') . '
                ' . (!empty($this->group)
                ? 'AND package_group.package = packages.id '
                . 'AND package_group.group = groups.id '
                . 'AND groups.name = :group ' : '') . '
                ' . (empty($this->search) ? '' : $this->getSearchStatement()) . '
                ' . (!empty($this->search) && $this->searchField == 'file' ? ' GROUP BY packages.id ' : ' ') . '
                ' . ($this->packager > 0 ? ' AND packages.packager = ' . $this->packager : '') . '
            ORDER BY
                ' . $this->orderby . ' ' . $this->sort . '
            LIMIT
                ' . (($this->page - 1) * $this->packagesPerPage) . ', ' . $this->packagesPerPage . '
            ');
        !empty($this->repository['name'])
        && $packages->bindValue('repositoryName', $this->repository['name'], \PDO::PARAM_STR);
        !empty($this->architecture['id'])
        && $packages->bindValue('architectureId', $this->architecture['id'], \PDO::PARAM_INT);
        !empty($this->group) && $packages->bindValue('group', $this->group, \PDO::PARAM_STR);
        !empty($this->search) && $packages->bindValue('search', $this->searchString, \PDO::PARAM_STR);
        $packages->execute();

        return $this->render('packages/index.html.twig', [
            'packages' => $packages,
            'search' => $this->search,
            'search_field' => $this->searchField,
            'search_fields' => $this->getSearchFields(),
            'repository' => $this->repository,
            'architecture' => $this->architecture,
            'packager' => $this->packager,
            'repository_list' => $this->getRepositoryList(),
            'architecture_list' => $this->getArchitectureList(),
            'group_list' => $this->getGroupList(),
            'package_list' => $this->showPackageList($packages),
            'autocomplete' => in_array($this->searchField, ['name', 'file'])
        ]);
    }

    /**
     * @param string $architecture
     *
     * @return array
     */
    private function getAvailableRepositories(string $architecture = ''): array
    {
        if (empty($architecture)) {
            return array_keys($this->getParameter('app.packages.repositories'));
        } else {
            $repositories = array();
            foreach ($this->getParameter('app.packages.repositories') as $repository => $architectures) {
                if (in_array($architecture, $architectures)) {
                    $repositories[] = $repository;
                }
            }

            return $repositories;
        }
    }

    /**
     * @param string $repositoryName
     * @param int $architectureId
     *
     * @return int
     */
    private function getRepositoryId(string $repositoryName, int $architectureId): int
    {
        $stm = $this->database->prepare('
            SELECT
                id
            FROM
                repositories
            WHERE
                name = :repositoryName
                AND arch = :architectureId
            ');
        $stm->bindParam('repositoryName', $repositoryName, \PDO::PARAM_STR);
        $stm->bindParam('architectureId', $architectureId, \PDO::PARAM_INT);
        $stm->execute();

        return (int)$stm->fetchColumn();
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
            foreach ($this->getParameter('app.packages.repositories') as $architectures) {
                foreach ($architectures as $architecture) {
                    $uniqueArchitectures[$architecture] = 1;
                }
            }

            return array_keys($uniqueArchitectures);
        } else {
            $repositories = $this->getParameter('app.packages.repositories');

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
        $stm = $this->database->prepare('
            SELECT
                id
            FROM
                architectures
            WHERE
                name = :architectureName
            ');
        $stm->bindParam('architectureName', $architectureName, \PDO::PARAM_STR);
        $stm->execute();

        return (int)$stm->fetchColumn();
    }

    private function initParameters(Request $request)
    {
        $this->orderby = $this->getRequest($request, 'orderby', array(
            'builddate',
            'name',
            'repository',
            'architecture',
        ));
        $this->sort = $this->getRequest($request, 'sort', array(
            'desc',
            'asc',
        ));
        $this->page = max($request->get('p', 1), 1);

        $this->repository['name'] = $this->getRequest($request, 'repository', $this->getAvailableRepositories(), '');
        $this->architecture['name'] = $this->getRequest(
            $request,
            'architecture',
            $this->getAvailableArchitectures($this->repository['name']),
            ($request->query->has('architecture') ? '' : $this->getParameter('app.packages.default_architecture'))
        );
        $this->architecture['id'] = $this->getArchitectureId($this->architecture['name']);
        $this->repository['id'] = $this->getRepositoryId($this->repository['name'], $this->architecture['id']);

        $this->group = $request->get('group', '');
        $this->packager = $request->get('packager', 0);

        $this->search = $this->cutString(
            htmlspecialchars(preg_replace(
                '/[^\w\.\+\- ]/',
                '',
                $request->get('search', '')
            )),
            50
        );
        if (strlen($this->search) < 2) {
            $this->search = '';
        }

        $searchFields = array('name', 'description');
        $searchFields[] = 'file';

        $this->searchField = $this->getRequest($request, 'searchfield', $searchFields);
    }

    /**
     * @param Request $request
     * @param string $name
     * @param array $allowedValues
     * @param string|null $default
     * @return string
     */
    private function getRequest(Request $request, string $name, array $allowedValues, $default = null): string
    {
        if (is_null($default)) {
            $default = $allowedValues[0];
        }
        $request = $request->get($name, $default);
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
                $this->searchString = '%' . $this->search . '%';

                return 'AND packages.name LIKE :search';
                break;
            case 'description':
                // FIXME: this cannot use any index
                $this->searchString = '%' . $this->search . '%';

                return 'AND packages.desc LIKE :search';
                break;
            case 'file':
                // FIXME: this is a very expensive query
                $this->searchString = $this->search . '%';

                return 'AND file_index.name LIKE :search '
                    . 'AND file_index.id = package_file_index.file_index '
                    . 'AND package_file_index.package = packages.id';
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
            'name' => 'Name',
            'description' => 'Beschreibung',
            'file' => 'Datei'
        );
        foreach ($searchFields as $key => $value) {
            if ($key == $this->searchField) {
                $selected = ' checked="checked"';
            } else {
                $selected = '';
            }
            $options .= ' <input type="radio" id="searchfield_'
                . $key . '" name="searchfield" value="' . $key . '"' . $selected . '  onchange="this.form.submit()" /> '
                . '<label for="searchfield_' . $key . '">' . $value . '</label>';
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
            $options .= '<option value="' . $repository . '"'
                . ($this->repository['name'] == $repository ? ' selected="selected"' : '') . '>'
                . $repository . '</option>';
        }

        return $options . '</select>';
    }

    /**
     * @return string
     */
    private function getArchitectureList(): string
    {
        $options = '<select name="architecture" onchange="this.form.submit()">
            <option value="">&nbsp;</option>';

        foreach ($this->getAvailableArchitectures($this->repository['name']) as $architecture) {
            $options .= '<option value="' . $architecture . '"'
                . ($this->architecture['name'] == $architecture ? ' selected="selected"' : '') . '>'
                . $architecture . '</option>';
        }

        return $options . '</select>';
    }

    /**
     * @return string
     */
    private function getGroupList(): string
    {
        $options = '<select name="group" onchange="this.form.submit()">
            <option value="">&nbsp;</option>';

        $groups = $this->database->query('
            SELECT
                name
            FROM
                groups
            ORDER BY
                name ASC
            ');
        while (($group = $groups->fetchColumn())) {
            $options .= '<option value="' . $group . '"'
                . ($this->group == $group ? ' selected="selected"' : '') . '>'
                . $group . '</option>';
        }

        return $options . '</select>';
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

        $next = ' <a href="'
            . $this->router->generate(
                'app_packages_index',
                array_merge(
                    $parameters,
                    array('orderby' => $this->orderby, 'sort' => $this->sort, 'p' => ($this->page + 1))
                )
            ) . '">&#187;</a>';
        $prev = ($this->page > 1 ? '<a href="'
            . $this->router->generate(
                'app_packages_index',
                array_merge(
                    $parameters,
                    array(
                        'orderby' => $this->orderby,
                        'sort' => $this->sort,
                        'p' => max(1, $this->page - 1),
                    )
                )
            ) . '">&#171;</a>' : '');

        $body = '<table class="pretty-table">
            <tr>
                <td class="pages" colspan="6">' . $prev . $next . '</td>
            </tr>
            <tr>
                <th><a href="'
            . $this->router->generate(
                'app_packages_index',
                array_merge(
                    $parameters,
                    array('orderby' => 'repository', 'sort' => $newSort)
                )
            ) . '">Repositorium</a></th>
                <th><a href="'
            . $this->router->generate(
                'app_packages_index',
                array_merge(
                    $parameters,
                    array('orderby' => 'architecture', 'sort' => $newSort)
                )
            ) . '">Architektur</a></th>
                <th><a href="'
            . $this->router->generate(
                'app_packages_index',
                array_merge(
                    $parameters,
                    array('orderby' => 'name', 'sort' => $newSort)
                )
            ) . '">Name</a></th>
                <th>Version</th>
                <th>Beschreibung</th>
                <th><a href="'
            . $this->router->generate(
                'app_packages_index',
                array_merge(
                    $parameters,
                    array('orderby' => 'builddate', 'sort' => $newSort)
                )
            ) . '">Letzte Aktualisierung</a></th>
            </tr>';
        foreach ($packages as $package) {
            $style = ($package['testing'] == 1 ? ' class="less"' : '');
            $body .= '<tr' . $style . '>
                <td>' . $package['repository'] . '</td><td>' . $package['architecture']
                . '</td><td><a href="' . $this->router->generate(
                    'app_packagedetails_index',
                    array(
                        'repo' => $package['repository'],
                        'arch' => $package['repositoryArchitecture'],
                        'pkgname' => $package['name'],
                    )
                ) . '">' . $package['name']
                . '</a></td><td>' . $package['version'] . '</td><td>'
                . $this->cutString($package['desc'], 70)
                . '</td><td>' . date('d.m.Y H:i', $package['builddate']) . '</td>
            </tr>';
        }
        $body .= '
            <tr>
                <td class="pages" colspan="6">' . $prev . $next . '</td>
            </tr>
        </table>';

        return $body;
    }

    /**
     * @param string $string
     * @param int $length
     *
     * @return string
     */
    private function cutString(string $string, int $length): string
    {
        // Verhindere das Abschneiden im Entity
        $string = htmlspecialchars_decode(trim($string));
        $string = (mb_strlen($string, 'UTF-8') > $length
            ? mb_substr($string, 0, ($length - 3), 'UTF-8') . '...'
            : $string);

        return htmlspecialchars($string);
    }
}
