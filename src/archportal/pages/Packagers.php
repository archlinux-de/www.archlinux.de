<?php

namespace archportal\pages;

use archportal\lib\Page;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\Request;

class Packagers extends Page
{
    /** @var string */
    private $orderby = 'name';
    /** @var int */
    private $sort = 0;
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
        $this->setTitle($this->l10n->getText('Packagers'));

        if (in_array($request->get('orderby'), array(
            'name',
            'lastbuilddate',
            'packages',
        ))) {
            $this->orderby = $request->get('orderby');
        }

        $this->sort = $request->get('sort', 0) > 0 ? 1 : 0;
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
            '.$this->orderby.' '.($this->sort > 0 ? 'DESC' : 'ASC').'
        ');
        $body = '
        <table class="pretty-table">
            <tr>
                <th><a href="'.$this->createUrl('Packagers',
                array('orderby' => 'name', 'sort' => abs($this->sort - 1))).'">'.$this->l10n->getText('Name').'</a></th>
                <th>'.$this->l10n->getText('Email').'</th>
                <th colspan="2"><a href="'.$this->createUrl('Packagers', array(
                'orderby' => 'packages',
                'sort' => abs($this->sort - 1),
            )).'">'.$this->l10n->getText('Packages').'</a></th>
                <th><a href="'.$this->createUrl('Packagers', array(
                'orderby' => 'lastbuilddate',
                'sort' => abs($this->sort - 1),
            )).'">'.$this->l10n->getText('Last update').'</a></th>
            </tr>';
        foreach ($packagers as $packager) {
            $percent = round(($packager['packages'] / $packages) * 100);
            $body .= '<tr>
                <td>'.$packager['name'].'</td>
                <td>'.(empty($packager['email']) ? '' : '<a href="mailto:'.$packager['email'].'">'.$packager['email'].'</a>').'</td>
                <td style="text-align:right;"><a href="'.$this->createUrl('Packages',
                    array('packager' => $packager['id'])).'">'.$packager['packages'].'</a></td>
                <td style="width:100px;"><div style="background-color:#1793d1;width:'.$percent.'px;">&nbsp;</div></td>
                <td>'.$this->l10n->getDateTime($packager['lastbuilddate']).'</td>
            </tr>';
        }
        $body .= '</table>';
        $this->setBody($body);
    }
}
