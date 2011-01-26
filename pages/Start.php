<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

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

class Start extends Page {

	private $arch = 1;

	public function prepare() {
		$this->arch = Input::cookie()->getInt('architecture', $this->arch);
		$this->setValue('title', $this->l10n->getText('Start'));
		$body = '<div id="left-wrapper">
		<div id="left">
			<div id="intro" class="box">
				'.$this->l10n->getTextFile('StartWelcome').'
			</div>
			<div id="news">
			' . $this->getNews() . '
			</div>
		</div>
	</div>
		<div id="right">
			<div id="pkgsearch">
				<form method="post" action="?page=Packages">
					<label for="searchfield">'.$this->l10n->getText('Package search').':</label>
					<input type="text" class="ui-autocomplete-input" name="search" size="20" maxlength="200" id="searchfield" autocomplete="off" />
					<script type="text/javascript" src="style/jquery.min.js?v=1.4.4"></script>
					<script type="text/javascript" src="style/jquery-ui-autocomplete.min.js?v=1.8.8"></script>
					<script>
						$(function() {
							$("#searchfield").autocomplete({
								source: "?page=PackagesSuggest;repo=2;arch=' . $this->arch . ';field=0",
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
				'.$this->l10n->getTextFile('StartSidebar').'
			</div>
		</div>
	';
		$this->setValue('body', $body);
	}

	private function getNews() {
		$result = '';
		if (!($result = ObjectCache::getObject('news_feed'))) {
			try {
				$download = new Download(Config::get('news', 'feed'));
				$feed = new SimpleXMLElement($download->getFile(), 0, true);
				$result = '<h3>'.$this->l10n->getText('Recent news').' <span class="more">(<a href="' . Config::get('news', 'archive') . '">mehr</a>)</span></h3><a href="' . Config::get('news', 'feed') . '" class="rss-icon"><img src="style/rss.png" alt="RSS Feed" /></a>';
				foreach ($feed->entry as $entry) {
					$result.= '
					<h4><a href="' . $entry->link->attributes()->href . '">' . $entry->title . '</a></h4>
					<p class="date">' . $this->l10n->getDate(strtotime($entry->updated)) . '</p>
					' . $entry->summary . '
					';
				}
				ObjectCache::addObject('news_feed', $result, 1800);
			} catch (Exception $e) {
			}
		}
		return $result;
	}

	private function getRecentPackages() {
		$packages = DB::prepare('
		SELECT
			packages.id,
			packages.name,
			packages.version,
			repositories.name AS repository,
			architectures.name AS architecture
		FROM
			packages,
			repositories,
			architectures
		WHERE
			packages.repository = repositories.id
			AND packages.arch = architectures.id
			AND packages.arch = :arch
		ORDER BY
			packages.builddate DESC
		LIMIT
			20
		');
		$packages->bindParam('arch', $this->arch, PDO::PARAM_INT);
		$packages->execute();
		$result = '<h3>'.$this->l10n->getText('Recent packages').' <span class="more">(<a href="?page=Packages">mehr</a>)</span></h3><a href="?page=GetRecentPackages" class="rss-icon"><img src="style/rss.png" alt="RSS Feed" /></a><table>';
		foreach ($packages as $package) {
			$result.= '
			<tr class="' . $package['repository'] . '">
				<td class="pkgname"><a href="?page=PackageDetails;repo=' . $package['repository'] . ';arch=' . $package['architecture'] . ';pkgname=' . $package['name'] . '">' . $package['name'] . '</a></td>
				<td class="pkgver">' . $package['version'] . '</td>
			</tr>
			';
		}
		return $result . '</table>';
	}
}

?>
