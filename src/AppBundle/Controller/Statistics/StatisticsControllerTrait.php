<?php

namespace AppBundle\Controller\Statistics;

use Doctrine\DBAL\Driver\Connection;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * @param int $value
     * @param int $total
     *
     * @return string
     */
    private function getBar(int $value, int $total): string
    {
        if ($total <= 0) {
            return '';
        }
        $percent = ($value / $total) * 100;
        if ($percent > 100) {
            return '';
        }

        if ($percent <= 25) {
            $color = 'danger';
        } elseif ($percent <= 50) {
            $color = 'warning';
        } elseif ($percent <= 75) {
            $color = 'info';
        } else {
            $color = 'success';
        }

        return '<div class="progress">
                <div class="progress-bar bg-' . $color . '" role="progressbar" style="width: ' . round($percent) . '%"'
            . ' aria-valuenow="round($percent)" aria-valuemin="0" aria-valuemax="100">'
            . number_format($percent, 2) . '&nbsp;%</div></div>';
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

    private function renderPage(string $title): Response
    {

        $cachedItem = $this->getCachedItem($title);
        if (!$cachedItem->isHit()) {
            throw new NotFoundHttpException('No data found!');
        }
        return $this->render('statistics/statistic.html.twig', [
            'title' => $title,
            'body' => $cachedItem->get()
        ]);
    }

    private function savePage(string $title, string $page): bool
    {
        return $this->cache->save($this->getCachedItem($title)->set($page));
    }


    /**
     * @return CacheItemInterface
     */
    private function getCachedItem(string $title): CacheItemInterface
    {
        return $this->cache->getItem($title);
    }
}
