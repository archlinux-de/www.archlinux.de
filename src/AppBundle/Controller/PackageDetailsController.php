<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class PackageDetailsController extends Controller
{
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
            'licenses' => $this->getLicenses($data['id']),
            'groups' => $this->getGroups($data['id']),
            'depends' => $this->getRelations($data['id'], 'depends'),
            'inverse_depends' => $this->getInverseRelations($data['id'], 'depends'),
            'provides' => $this->getRelations($data['id'], 'provides'),
            'conflicts' => $this->getRelations($data['id'], 'conflicts'),
            'replaces' => $this->getRelations($data['id'], 'replaces'),
            'optdepends' => $this->getRelations($data['id'], 'optdepends'),
            'inverse_optdepends' => $this->getInverseRelations($data['id'], 'optdepends'),
            'makedepends' => $this->getRelations($data['id'], 'makedepends'),
            'inverse_makedepends' => $this->getInverseRelations($data['id'], 'makedepends'),
            'checkdepends' => $this->getRelations($data['id'], 'checkdepends'),
            'repo' => $repo,
            'pkgname' => $pkgname
        ]);
    }

    /**
     * @param int $pkgid
     * @return array
     */
    private function getLicenses(int $pkgid): array
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
        $stm->bindParam('package', $pkgid, \PDO::PARAM_INT);
        $stm->execute();

        return $stm->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param int $pkgid
     * @return array
     */
    private function getGroups(int $pkgid): array
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
        $groups->bindParam('package', $pkgid, \PDO::PARAM_INT);
        $groups->execute();

        return $groups->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param int $pkgid
     * @param string $type
     * @return array
     */
    private function getRelations(int $pkgid, string $type): array
    {
        $stm = $this->database->prepare('
        SELECT
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
        $stm->bindParam('packageId', $pkgid, \PDO::PARAM_INT);
        $stm->bindParam('type', $type, \PDO::PARAM_STR);
        $stm->execute();

        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param int $pkgid
     * @param string $type
     * @return array
     */
    private function getInverseRelations(int $pkgid, string $type): array
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
        $stm->bindParam('packageId', $pkgid, \PDO::PARAM_INT);
        $stm->bindParam('type', $type, \PDO::PARAM_STR);
        $stm->execute();

        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }
}
