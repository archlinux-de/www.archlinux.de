<?php
/*
	Copyright 2002-2012 Pierre Schmitz <pierre@archlinux.de>

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

	private $architectureId = 0;

	public function prepare() {
		$availableArchitectures = $this->getAvailableArchitectures();
		try {
			$clientArch = Input::getClientArchitecture();
			if (!in_array($clientArch, $availableArchitectures)) {
				$clientArch = $availableArchitectures[0];
			}
		} catch (RequestException $e) {
			$clientArch = $availableArchitectures[0];
		}
		$this->architectureId = $this->getArchitectureId($clientArch);

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
				<form method="get">
					<input type="hidden" name="page" value="Packages" />
					<label for="searchfield">'.$this->l10n->getText('Package search').':</label>
					<input type="text" class="ui-autocomplete-input" name="search" size="20" maxlength="200" id="searchfield" autocomplete="off" />
					<script>
						$(function() {
							$("#searchfield").autocomplete({
								source: "'.$this->createUrl('PackagesSuggest', array('architecture' => $this->architectureId)).'",
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

	private function getAvailableArchitectures() {
		$uniqueArchitectures = array();
		foreach (Config::get('packages', 'repositories') as $architectures) {
			foreach ($architectures as $architecture) {
				$uniqueArchitectures[$architecture] = 1;
			}
		}
		return array_keys($uniqueArchitectures);
	}

	private function getArchitectureId($architectureName) {
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
		$result = '<h3>'.$this->l10n->getText('Recent packages').' <span class="more">(<a href="'.$this->createUrl('Packages').'">mehr</a>)</span></h3><a href="'.$this->createUrl('GetRecentPackages').'" class="rss-icon"><img src="style/rss.png" alt="RSS Feed" /></a><table>';
		foreach ($packages as $package) {
			$result.= '
			<tr'.( $package['testing'] == 1 ? ' class="testing"' : '').'>
				<td class="pkgname"><a href="'.$this->createUrl('PackageDetails', array('repo' => $package['repository'], 'arch' => $package['architecture'], 'pkgname' => $package['name'])).'">' . $package['name'] . '</a></td>
				<td class="pkgver">' . $package['version'] . '</td>
			</tr>
			';
		}
		return $result . '</table>';
	}
}

?>
