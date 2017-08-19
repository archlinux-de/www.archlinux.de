<?php

namespace AppBundle\Controller;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        if (strlen($term) < 2 || strlen($term) > 20) {
            throw new BadRequestHttpException();
        }
        $arch = $request->get('architecture', 0);
        $repo = $request->get('repository', 0);
        $field = $request->get('field', 'name');
        switch ($field) {
            case 'name':
                $stm = $this->database->prepare('
                        SELECT DISTINCT
                            packages.name
                        FROM
                            packages
                            ' . ($arch > 0 || $repo > 0 ? '
                                JOIN repositories
                                ON packages.repository = repositories.id' : '') . '
                        WHERE
                            packages.name LIKE :name
                            ' . ($arch > 0 ? 'AND repositories.arch = :arch' : '') . '
                            ' . ($repo > 0 ? 'AND repositories.id = :repository' : '') . '
                        ORDER BY
                            packages.name ASC
                        LIMIT 20
                    ');
                $stm->bindValue('name', $term . '%', \PDO::PARAM_STR);
                $arch > 0 && $stm->bindParam('arch', $arch, \PDO::PARAM_INT);
                $repo > 0 && $stm->bindParam('repository', $repo, \PDO::PARAM_INT);
                break;
            case 'file':
                $stm = $this->database->prepare('
                        SELECT DISTINCT
                            name
                        FROM
                            file_index
                        WHERE
                            name LIKE :name
                        ORDER BY
                            name ASC
                        LIMIT 20
                    ');
                $stm->bindValue('name', $term . '%', \PDO::PARAM_STR);
                break;
            default:
                throw new BadRequestHttpException();
        }
        $stm->execute();
        $suggestions = [];
        while (($suggestion = $stm->fetchColumn())) {
            $suggestions[] = $suggestion;
        }

        return $this->json($suggestions);
    }
}
