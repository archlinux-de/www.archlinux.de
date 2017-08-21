<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PackagesSuggestController extends Controller
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
     * @Route("/packages/suggest", methods={"GET"})
     * @param Request $request
     * @return Response
     */
    public function suggestAction(Request $request): Response
    {
        $term = $request->get('term');
        if (strlen($term) < 1 || strlen($term) > 50) {
            return $this->json([]);
        }
        $suggestions = $this->database->prepare('
                        SELECT DISTINCT
                            packages.name
                        FROM
                            packages
                        WHERE
                            packages.name LIKE :name
                        ORDER BY
                            packages.name ASC
                        LIMIT 10
                    ');
        $suggestions->bindValue('name', $term . '%', \PDO::PARAM_STR);
        $suggestions->execute();

        return $this->json($suggestions->fetchAll(\PDO::FETCH_COLUMN))->setSharedMaxAge(600);
    }
}
