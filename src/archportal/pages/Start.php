<?php

/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

  This file is part of archlinux.de.

  archlinux.de is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  archlinux.de is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace archportal\pages;

use archportal\lib\Config;
use archportal\lib\Database;
use archportal\lib\Page;
use PDO;

class Start extends Page
{

    /** @var int */
    private $architectureId = 0;

    public function prepare()
    {
        $this->architectureId = $this->getArchitectureId(Config::get('packages', 'default_architecture'));

        $this->setTitle($this->l10n->getText('Start'));

        $this->addJS('jquery.min');
        $this->addJS('jquery.ui.core.min');
        $this->addJS('jquery.ui.widget.min');
        $this->addJS('jquery.ui.position.min');
        $this->addJS('jquery.ui.menu.min');
        $this->addJS('jquery.ui.autocomplete.min');
        $this->addCSS('jquery.ui.core.min');
        $this->addCSS('jquery.ui.theme.min');
        $this->addCSS('jquery.ui.menu.min');
        $this->addCSS('jquery.ui.autocomplete.min');

        $body = '<div id="left-wrapper">
            <div id="left">
                <div id="intro" class="box">
                    ' . $this->l10n->getTextFile('StartWelcome') . '
                </div>
                <div id="news">
                ' . $this->getNews() . '
                </div>
            </div>
        </div>
        <div id="right">
            <div id="pkgsearch">
                <form method="get">
                    <input type="hidden" name="page" value="Packages" />
                    <label for="searchfield">' . $this->l10n->getText('Package search') . ':</label>
                    <input type="text" class="ui-autocomplete-input" name="search" size="20" maxlength="200" id="searchfield" autocomplete="off" />
                    <script>
                        $(function () {
                            $("#searchfield").autocomplete({
                                source: "' . $this->createUrl('PackagesSuggest',
                array('architecture' => $this->architectureId)) . '",
                                minLength: 2,
                                delay: 100
                            });
                        });
                    </script>
                </form>
            </div>
            <div id="pkgrecent" class="box">
                ' . $this->getRecentPackages() . '
            </div>
            <div id="sidebar">
                ' . $this->l10n->getTextFile('StartSidebar') . '
            </div>
        </div>
    ';
        $this->setBody($body);
    }

    /**
     * @param string $architectureName
     * @return int
     */
    private function getArchitectureId(string $architectureName): int
    {
        $stm = Database::prepare('
            SELECT
                id
            FROM
                architectures
            WHERE
                name = :architectureName
            ');
        $stm->bindParam('architectureName', $architectureName, PDO::PARAM_STR);
        $stm->execute();

        return $stm->fetchColumn();
    }

    /**
     * @return string
     */
    private function getNews(): string
    {
        $result = '<h3>' . $this->l10n->getText('Recent news') . ' <span class="more">(<a href="' . htmlspecialchars(Config::get('news',
                'archive')) . '">mehr</a>)</span></h3><a href="' . htmlspecialchars(Config::get('news',
                'feed')) . '" class="rss-icon"><img src="style/rss.png" alt="RSS Feed" /></a>';

        $newsFeed = Database::query('
            SELECT
                link,
                title,
                updated,
                summary
            FROM
                news_feed
            ORDER BY
                updated DESC
            LIMIT 6
            ');
        foreach ($newsFeed as $entry) {
            $result .= '
            <h4><a href="' . htmlspecialchars($entry['link']) . '">' . $entry['title'] . '</a></h4>
            <p class="date">' . $this->l10n->getDate($entry['updated']) . '</p>
            ' . $entry['summary'] . '
            ';
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getRecentPackages(): string
    {
        $packages = Database::prepare('
        SELECT
            packages.name,
            packages.version,
            repositories.name AS repository,
            repositories.testing,
            architectures.name AS architecture
        FROM
            packages,
            repositories,
            architectures
        WHERE
            packages.repository = repositories.id
            AND repositories.arch = :architectureId
            AND architectures.id = repositories.arch
        ORDER BY
            packages.builddate DESC
        LIMIT
            20
        ');
        $packages->bindParam('architectureId', $this->architectureId, PDO::PARAM_INT);
        $packages->execute();
        $result = '<h3>' . $this->l10n->getText('Recent packages') . ' <span class="more">(<a href="' . $this->createUrl('Packages') . '">mehr</a>)</span></h3><a href="' . $this->createUrl('GetRecentPackages') . '" class="rss-icon"><img src="style/rss.png" alt="RSS Feed" /></a><table>';
        foreach ($packages as $package) {
            $result .= '
            <tr' . ($package['testing'] == 1 ? ' class="testing"' : '') . '>
                <td class="pkgname"><a href="' . $this->createUrl('PackageDetails', array(
                    'repo' => $package['repository'],
                    'arch' => $package['architecture'],
                    'pkgname' => $package['name']
                )) . '">' . $package['name'] . '</a></td>
                <td class="pkgver">' . $package['version'] . '</td>
            </tr>
            ';
        }

        return $result . '</table>';
    }
}
