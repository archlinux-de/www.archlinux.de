<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Response\Datatables\Response as DatatablesResponse;

class PackagersController extends Controller
{
    /** @var Connection */
    private $database;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->database = $connection;
    }

    /**
     * @Route("/packages/packagers", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('packagers/index.html.twig');
    }

    /**
     * @Route("/packages/packagers/datatables", methods={"GET"})
     * @Cache(smaxage="600")
     * @return Response
     */
    public function datatablesAction(): Response
    {
        $packagers = $this->database->query('
            SELECT
            packagers.id,
            packagers.name,
            packagers.email,
            (
                SELECT
                    COUNT(packages.id)
                FROM
                    packages
                WHERE
                    packages.packager = packagers.id
            ) AS packages,
            (
                SELECT
                    MAX(packages.builddate)
                FROM
                    packages
                WHERE
                    packages.packager = packagers.id
            ) AS lastbuilddate
            FROM
            packagers
        ')->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json(new DatatablesResponse($packagers));
    }
}
