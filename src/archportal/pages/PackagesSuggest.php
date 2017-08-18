<?php

namespace archportal\pages;

use archportal\lib\Config;
use archportal\lib\Page;
use Doctrine\DBAL\Driver\Connection;
use PDO;
use Symfony\Component\HttpFoundation\Request;

class PackagesSuggest extends Page
{
    /** @var array */
    private $suggestions = array();
    /** @var Connection */
    private $database;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->database = $connection;
    }

    public function prepare(Request $request)
    {
        $term = $request->get('term');
        if (strlen($term) < 2 || strlen($term) > 20) {
            return;
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
                $stm->bindValue('name', $term . '%', PDO::PARAM_STR);
                $arch > 0 && $stm->bindParam('arch', $arch, PDO::PARAM_INT);
                $repo > 0 && $stm->bindParam('repository', $repo, PDO::PARAM_INT);
                break;
            case 'file':
                if (Config::get('packages', 'files')) {
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
                    $stm->bindValue('name', $term . '%', PDO::PARAM_STR);
                } else {
                    return;
                }
                break;
            default:
                return;
        }
        $stm->execute();
        while (($suggestion = $stm->fetchColumn())) {
            $this->suggestions[] = $suggestion;
        }
    }

    public function printPage()
    {
        $this->setContentType('application/json; charset=UTF-8');
        echo json_encode($this->suggestions);
    }
}
