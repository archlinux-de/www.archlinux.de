<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MirrorStatusController extends Controller
{
    /** @var string */
    private $orderby = 'lastsync';
    /** @var string */
    private $sort = 'desc';
    /** @var int */
    private $range = 604800; // 1 week
    /** @var array */
    private $orders = array(
        'url',
        'country',
        'lastsync',
        'delay',
        'durationAvg',
    );
    /** @var array */
    private $sorts = array(
        'asc',
        'desc',
    );
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
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        if (in_array($request->get('orderby'), $this->orders)) {
            $this->orderby = $request->get('orderby');
        }
        if (in_array($request->get('sort'), $this->sorts)) {
            $this->sort = $request->get('sort');
        }

        $mirrors = $this->database->query('
        SELECT
            mirrors.url,
            countries.name AS country,
            mirrors.lastsync,
            mirrors.delay,
            mirrors.durationAvg
        FROM
            mirrors
            JOIN countries
            ON mirrors.countryCode = countries.code
        WHERE
            mirrors.lastsync >= ' . (time() - $this->range) . '
        ORDER BY
            ' . $this->orderby . ' ' . $this->sort . '
        ');

        return $this->render('mirrors/index.html.twig', [
            'mirrors' => $mirrors,
            'sort' => $this->sort == 'desc' ? 'asc' : 'desc'
        ]);
    }
}
