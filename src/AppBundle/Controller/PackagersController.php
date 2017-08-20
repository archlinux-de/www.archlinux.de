<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $orderBy = 'name';
        if (in_array($request->get('orderby'), array(
            'name',
            'lastbuilddate',
            'packages',
        ))) {
            $orderBy = $request->get('orderby');
        }

        $sort = $request->get('sort', 0) > 0 ? 1 : 0;
        $packages = $this->database->query('SELECT COUNT(*) FROM packages')->fetchColumn();
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
            ORDER BY
            ' . $orderBy . ' ' . ($sort > 0 ? 'DESC' : 'ASC') . '
        ');

        return $this->render('packagers/index.html.twig', [
            'packages' => $packages,
            'packagers' => $packagers,
            'sort' => abs($sort - 1)
        ]);
    }
}
