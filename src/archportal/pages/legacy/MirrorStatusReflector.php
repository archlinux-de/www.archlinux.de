<?php

namespace archportal\pages\legacy;

use archportal\lib\Page;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;

class MirrorStatusReflector extends Page
{
    /** @var int */
    private $range = 604800; // 1 week
    /** @var string */
    private $text = '';
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
        $mirrors = $this->database->query('
        SELECT
            url,
            lastsync
        FROM
            mirrors
        WHERE
            lastsync >= '.(time() - $this->range).'
            AND protocol IN ("ftp", "http", "htttps")
        ORDER BY
            lastsync DESC
        ');
        foreach ($mirrors as $mirror) {
            $this->text .= gmdate('Y-m-d H:i'.$mirror['lastsync']).' '.$mirror['url']."\n";
        }
    }

    public function printPage()
    {
        $this->setContentType('text/plain; charset=UTF-8');
        echo $this->text;
    }
}
