<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PackageDetailsController extends Controller
{
    /** @var int */
    private $pkgid = 0;
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
     * @Route("/packages/{repo}/{arch}/{pkgname}", methods={"GET"})
     * @param string $repo
     * @param string $arch
     * @param string $pkgname
     * @return Response
     */
    public function indexAction(string $repo, string $arch, string $pkgname): Response
    {
        $repository = $this->database->prepare('
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
        $repository->bindParam('repositoryName', $repo, \PDO::PARAM_STR);
        $repository->bindParam('architectureName', $arch, \PDO::PARAM_STR);
        $repository->execute();

        $stm = $this->database->prepare('
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
        $stm->bindValue('repositoryId', $repository->fetchColumn(), \PDO::PARAM_STR);
        $stm->bindParam('package', $pkgname, \PDO::PARAM_STR);
        $stm->execute();
        $data = $stm->fetch();
        if ($data === false) {
            throw $this->createNotFoundException('Paket wurde nicht gefunden');
        }
        $this->pkgid = $data['id'];
        $cgitUrl = $this->getParameter('app.packages.cgit') . (in_array($data['repository'], array(
                'community',
                'community-testing',
                'multilib',
                'multilib-testing',
            )) ? 'community' : 'packages')
            . '.git/';

        return $this->render('package/index.html.twig', [
            'package' => $data,
            'cgit_url' => $cgitUrl,
            'arch' => $arch,
            'licenses' => $this->getLicenses(),
            'groups' => $this->getGroups(),
            'depends' => $this->getRelations('depends'),
            'inverse_depends' => $this->getInverseRelations('depends'),
            'provides' => $this->getRelations('provides'),
            'conflicts' => $this->getRelations('conflicts'),
            'replaces' => $this->getRelations('replaces'),
            'optdepends' => $this->getRelations('optdepends'),
            'inverse_optdepends' => $this->getInverseRelations('optdepends'),
            'makedepends' => $this->getRelations('makedepends'),
            'inverse_makedepends' => $this->getInverseRelations('makedepends'),
            'checkdepends' => $this->getRelations('checkdepends'),
            'repo' => $repo,
            'pkgname' => $pkgname
        ]);
    }

    /**
     * @return string
     */
    private function getLicenses(): string
    {
        $stm = $this->database->prepare('
        SELECT
            licenses.name
        FROM
            licenses,
            package_license
        WHERE
            package_license.license = licenses.id
            AND package_license.package = :package
        ');
        $stm->bindParam('package', $this->pkgid, \PDO::PARAM_INT);
        $stm->execute();
        $list = array();
        while (($license = $stm->fetchColumn())) {
            $list[] = $license;
        }

        return implode(', ', $list);
    }

    /**
     * @return string
     */
    private function getGroups(): string
    {
        $groups = $this->database->prepare('
            SELECT
                groups.name
            FROM
                groups,
                package_group
            WHERE
                package_group.group = groups.id
                AND package_group.package = :package
        ');
        $groups->bindParam('package', $this->pkgid, \PDO::PARAM_INT);
        $groups->execute();
        $list = array();
        while (($group = $groups->fetchColumn())) {
            $list[] = '<a href="' .
                $this->router->generate('app_packages_index', ['group' => $group])
                . '">' . $group . '</a>';
        }

        return implode(', ', $list);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getRelations(string $type): string
    {
        $stm = $this->database->prepare('
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
        $stm->bindParam('packageId', $this->pkgid, \PDO::PARAM_INT);
        $stm->bindParam('type', $type, \PDO::PARAM_STR);
        $stm->execute();
        $list = '<ul class="list-unstyled pl-4">';
        foreach ($stm as $dependency) {
            if (is_null($dependency['id'])) {
                $list .= '<li>' . $dependency['name'] . $dependency['version'] . '</li>';
            } else {
                $list .= '<li><a href="' . $this->router->generate('app_packagedetails_index', array(
                        'repo' => $dependency['repo'],
                        'arch' => $dependency['arch'],
                        'pkgname' => $dependency['name'],
                    )) . '">' . $dependency['name'] . '</a>' . $dependency['version'] . '</li>';
            }
        }
        $list .= '</ul>';

        return $list;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getInverseRelations(string $type): string
    {
        $stm = $this->database->prepare('
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
        $stm->bindParam('packageId', $this->pkgid, \PDO::PARAM_INT);
        $stm->bindParam('type', $type, \PDO::PARAM_STR);
        $stm->execute();
        $list = '<ul class="list-unstyled pl-4">';
        foreach ($stm as $dependency) {
            $list .= '<li><a href="' . $this->router->generate('app_packagedetails_index', array(
                    'repo' => $dependency['repo'],
                    'arch' => $dependency['arch'],
                    'pkgname' => $dependency['name'],
                )) . '">' . $dependency['name'] . '</a>' . $dependency['version'] . '</li>';
        }
        $list .= '</ul>';

        return $list;
    }
}
