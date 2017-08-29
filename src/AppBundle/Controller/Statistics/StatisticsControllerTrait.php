<?php

namespace AppBundle\Controller\Statistics;

use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Cache\Adapter\PdoAdapter;

trait StatisticsControllerTrait
{
    /** @var int */
    private $rangeMonths = 3;
    /** @var Connection */
    private $database;
    /** @var PdoAdapter */
    private $cache;

    /**
     * @param Connection $connection
     * @param PdoAdapter $cache
     */
    public function __construct(Connection $connection, PdoAdapter $cache)
    {
        $this->database = $connection;
        $this->cache = $cache;
    }

    /**
     * @return int
     */
    private function getRangeTime(): int
    {
        return strtotime(date('1-m-Y', strtotime('now -' . $this->rangeMonths . ' months')));
    }

    /**
     * @return string
     */
    private function getRangeYearMonth(): string
    {
        return date('Ym', $this->getRangeTime());
    }
}
