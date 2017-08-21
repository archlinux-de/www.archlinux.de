<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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
     * @return Response
     */
    public function indexAction(): Response
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
        ')->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('mirrors/index.html.twig', [
            'mirrors' => $mirrors
        ])->setSharedMaxAge(600);
    }
}
