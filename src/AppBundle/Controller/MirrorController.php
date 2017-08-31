<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mirror;
use AppBundle\Service\GeoIp;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MirrorController extends Controller
{
    /** @var Connection */
    private $database;
    /** @var GeoIp */
    private $geoIp;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param Connection $connection
     * @param GeoIp $geoIp
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Connection $connection, GeoIp $geoIp, EntityManagerInterface $entityManager)
    {
        $this->database = $connection;
        $this->geoIp = $geoIp;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/download/iso/{version}/{file}",
     *      requirements={
     *          "version": "^[0-9]{4}\.[0-9]{2}\.[0-9]{2}$",
     *          "file": "[a-zA-Z0-9\.\-\+_/:]{1,255}"
     *      },
     *      methods={"GET"}
     *     )
     * @param string $version
     * @param string $file
     * @param Request $request
     * @return Response
     */
    public function isoAction(string $version, string $file, Request $request): Response
    {
        $releasedate = $this->database->prepare('
                SELECT
                    created
                FROM
                    releng_releases
                WHERE
                    version = :version
                    AND available = 1
                ');
        $releasedate->bindParam('version', $version, \PDO::PARAM_STR);
        $releasedate->execute();
        if ($releasedate->rowCount() == 0) {
            throw $this->createNotFoundException('ISO image was not found');
        }
        $lastsync = $releasedate->fetchColumn();

        return $this->redirectToMirror('iso/' . $version . '/' . $file, $lastsync, $request);
    }

    /**
     * @Route(
     *     "/download/{repository}/os/{architecture}/{file}",
     *      requirements={
     *          "file": "^[^-]+.*-[^-]+-[^-]+-[a-zA-Z0-9\.\-\+_:]{1,255}$"
     *      },
     *      methods={"GET"}
     *     )
     * @param string $repository
     * @param string $architecture
     * @param string $file
     * @param Request $request
     * @return Response
     */
    public function packageAction(string $repository, string $architecture, string $file, Request $request): Response
    {
        if (preg_match('#^([^-]+.*)-[^-]+-[^-]+-.*$#', $file, $matches)) {
            $pkgdate = $this->database->prepare('
                SELECT
                    packages.mtime
                FROM
                    packages
                    LEFT JOIN repositories
                    ON packages.repository = repositories.id
                    LEFT JOIN architectures
                    ON repositories.arch = architectures.id
                WHERE
                    packages.name = :pkgname
                    AND repositories.name = :repository
                    AND architectures.name = :architecture
                ');
            $pkgdate->bindParam('pkgname', $matches[1], \PDO::PARAM_STR);
            $pkgdate->bindParam('repository', $repository, \PDO::PARAM_STR);
            $pkgdate->bindParam('architecture', $architecture, \PDO::PARAM_STR);
            $pkgdate->execute();
            if ($pkgdate->rowCount() == 0) {
                throw $this->createNotFoundException('Package was not found');
            }
            $lastsync = $pkgdate->fetchColumn();
            return $this->redirectToMirror($repository . '/os/' . $architecture . '/' . $file, $lastsync, $request);
        }
        throw $this->createNotFoundException('Package was not found');
    }

    /**
     * @Route("/download/{file}", requirements={"file": "^[a-zA-Z0-9\.\-\+_/:]{1,255}$"}, methods={"GET"})
     * @param string $file
     * @param Request $request
     * @return Response
     */
    public function fallbackAction(string $file, Request $request): Response
    {
        $lastsync = time() - (60 * 60 * 24);
        return $this->redirectToMirror($file, $lastsync, $request);
    }

    /**
     * @param string $file
     * @param int $lastsync
     * @param Request $request
     * @return Response
     */
    private function redirectToMirror(string $file, int $lastsync, Request $request): Response
    {
        return $this->redirect(
            $this->getMirror($lastsync, $request->getClientIp()) . $file
        );
    }

    /**
     * @param int $lastsync
     *
     * @param string $clientIp
     * @return string
     */
    private function getMirror(int $lastsync, string $clientIp): string
    {
        $countryCode = $this->geoIp->getCountryCode($clientIp);
        if (empty($countryCode)) {
            $countryCode = $this->getParameter('app.mirrors.country');
        }
        $mirrors = $this->entityManager->createQueryBuilder()
            ->select('mirror.url')
            ->from(Mirror::class, 'mirror')
            ->where('mirror.lastSync > :lastsync')
            ->andWhere('mirror.country = :country')
            ->andWhere('mirror.protocol = :protocol')
            ->setParameter('lastsync', $lastsync)
            ->setParameter('country', $countryCode)
            ->setParameter('protocol', 'https')
            ->getQuery()
            ->getResult(Query::HYDRATE_SCALAR);

        if (empty($mirrors)) {
            // Let's see if any mirror is recent enough
            $mirrors = $this->entityManager->createQueryBuilder()
                ->select('mirror.url')
                ->from(Mirror::class, 'mirror')
                ->where('mirror.lastSync > :lastsync')
                ->andWhere('mirror.protocol = :protocol')
                ->setParameter('lastsync', $lastsync)
                ->setParameter('protocol', 'https')
                ->getQuery()
                ->getResult(Query::HYDRATE_SCALAR);

            if (empty($mirrors)) {
                // Fallback to the default mirror
                $mirrors = ['url' => $this->getParameter('app.mirrors.default')];
            }
        }

        srand(crc32($clientIp));
        return $mirrors[array_rand($mirrors, 1)]['url'];
    }
}
