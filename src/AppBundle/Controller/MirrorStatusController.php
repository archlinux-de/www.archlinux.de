<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Response\Datatables\Response as DatatablesResponse;

class MirrorStatusController extends Controller
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
     * @Route("/mirrors", methods={"GET"})
     * @Cache(smaxage="900")
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('mirrors/index.html.twig');
    }

    /**
     * @Route("/mirrors/datatables", methods={"GET"})
     * @Cache(smaxage="600")
     * @return Response
     */
    public function datatablesAction(): Response
    {
        $mirrors = $this->database->query('
        SELECT
            mirrors.url,
            countries.name AS country,
            mirrors.durationAvg,
            mirrors.delay,
            mirrors.lastsync
        FROM
            mirrors
            JOIN countries
            ON mirrors.countryCode = countries.code
        WHERE
            protocol = "https"
        ')->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json(new DatatablesResponse($mirrors));
    }
}
