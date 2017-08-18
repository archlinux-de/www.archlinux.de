<?php

namespace archportal\pages;

use archportal\lib\Page;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;

class MirrorStatus extends Page
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
        parent::__construct();
        $this->database = $connection;
    }

    public function prepare(Request $request)
    {
        $this->setTitle($this->l10n->getText('Mirror status'));

        if (in_array($request->get('orderby'), $this->orders)) {
            $this->orderby = $request->get('orderby');
        }
        if (in_array($request->get('sort'), $this->sorts)) {
            $this->sort = $request->get('sort');
        }

        $reverseSort = ($this->sort == 'desc' ? 'asc' : 'desc');
        $body = '<div class="box">
        <h2>'.$this->l10n->getText('Mirror status').'</h2>
        </div>
        <table class="pretty-table">
            <tr>
                <th><a href="'.$this->createUrl('MirrorStatus',
                array('orderby' => 'url', 'sort' => $reverseSort)).'">'.$this->l10n->getText('url').'</a></th>
                <th><a href="'.$this->createUrl('MirrorStatus',
                array('orderby' => 'country', 'sort' => $reverseSort)).'">'.$this->l10n->getText('Country').'</a></th>
                <th style="width:140px;"><a href="'.$this->createUrl('MirrorStatus', array(
                'orderby' => 'durationAvg',
                'sort' => $reverseSort,
            )).'">&empty;&nbsp;'.$this->l10n->getText('Response time').'</a></th>
                <th style="width:140px;"><a href="'.$this->createUrl('MirrorStatus', array(
                'orderby' => 'delay',
                'sort' => $reverseSort,
            )).'">&empty;&nbsp;'.$this->l10n->getText('Delay').'</a></th>
                <th><a href="'.$this->createUrl('MirrorStatus',
                array('orderby' => 'lastsync', 'sort' => $reverseSort)).'">'.$this->l10n->getText('Last update').'</a></th>
            </tr>';
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
            mirrors.lastsync >= '.(time() - $this->range).'
        ORDER BY
            '.$this->orderby.' '.$this->sort.'
        ');
        foreach ($mirrors as $mirror) {
            $body .= '<tr>
                <td><a href="'.$mirror['url'].'" rel="nofollow">'.$mirror['url'].'</a></td>
                <td>'.$mirror['country'].'</td>
                <td>'.$this->l10n->getEpoch((int) round($mirror['durationAvg'])).'</td>
                <td>'.$this->l10n->getEpoch($mirror['delay']).'</td>
                <td>'.$this->l10n->getGmDateTime($mirror['lastsync']).'</td>
            </tr>';
        }
        $body .= '</table>';
        $this->setBody($body);
    }
}
