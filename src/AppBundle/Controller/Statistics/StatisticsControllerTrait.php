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
    /** @var array */
    private $barColors = array();
    /** @var array */
    private $barColorArray = array(
        '8B0000',
        'FF8800',
        '006400',
    );
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
        $this->barColors = $this->createMultiColorFade($this->barColorArray);
    }

    /**
     * @param array $hexarray
     *
     * @return array
     *
     * see http://at.php.net/manual/de/function.hexdec.php#66780
     */
    private function createMultiColorFade(array $hexarray): array
    {
        $steps = 101;
        $total = count($hexarray);
        $gradient = array();
        $start = array();
        $end = array();
        $step = array();
        $rgb = array();
        $hex = array();
        $fixend = 2;
        $passages = $total - 1;
        $stepsforpassage = floor($steps / $passages);
        $stepsremain = $steps - ($stepsforpassage * $passages);
        $stepsforthis = 0;
        for ($pointer = 0; $pointer < $total - 1; ++$pointer) {
            $hexstart = $hexarray[$pointer];
            $hexend = $hexarray[$pointer + 1];
            if ($stepsremain > 0) {
                if ($stepsremain--) {
                    $stepsforthis = $stepsforpassage + 1;
                }
            } else {
                $stepsforthis = $stepsforpassage;
            }
            if ($pointer > 0) {
                $fixend = 1;
            }
            $start['r'] = hexdec(substr($hexstart, 0, 2));
            $start['g'] = hexdec(substr($hexstart, 2, 2));
            $start['b'] = hexdec(substr($hexstart, 4, 2));
            $end['r'] = hexdec(substr($hexend, 0, 2));
            $end['g'] = hexdec(substr($hexend, 2, 2));
            $end['b'] = hexdec(substr($hexend, 4, 2));
            $step['r'] = ($start['r'] - $end['r']) / ($stepsforthis);
            $step['g'] = ($start['g'] - $end['g']) / ($stepsforthis);
            $step['b'] = ($start['b'] - $end['b']) / ($stepsforthis);
            for ($i = 0; $i <= $stepsforthis - $fixend; ++$i) {
                $rgb['r'] = floor($start['r'] - ($step['r'] * $i));
                $rgb['g'] = floor($start['g'] - ($step['g'] * $i));
                $rgb['b'] = floor($start['b'] - ($step['b'] * $i));
                $hex['r'] = sprintf('%02x', ($rgb['r']));
                $hex['g'] = sprintf('%02x', ($rgb['g']));
                $hex['b'] = sprintf('%02x', ($rgb['b']));
                $gradient[] = strtoupper(implode(null, $hex));
            }
        }
        $gradient[] = $hexarray[$total - 1];

        return $gradient;
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
        $color = $this->barColors[(int)round($percent)];

        return '<table style="width:100%;">
            <tr>
                <td style="padding:0;margin:0;">
                    <div style="background-color:#' . $color . ';width:' . round($percent) . '%;"
        title="' . number_format($value) . ' of ' . number_format($total) . '">
            &nbsp;
                </div>
                </td>
                <td style="padding:0;margin:0;width:80px;text-align:right;color:#' . $color . ';">
                    ' . number_format($percent, 2) . '&nbsp;%
                </td>
            </tr>
        </table>';
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
