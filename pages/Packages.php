<?php
/*
	Copyright 2002-2010 Pierre Schmitz <pierre@archlinux.de>

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

class Packages extends Page {

	private $package = 0;
	private $maxPackages = 50;
	private $orderby = 'builddate';
	private $sort = 1;
	private $repository = 0;
	private $architecture = 1;
	private $group = 0;
	private $packager = 0;
	private $search = '';
	private $searchString = '';
	private $searchField = 0;

	public function prepare() {
		$this->setValue('title', 'Paket-Suche');
		try {
			if (in_array($this->Input->Get->getString('orderby') , array(
				'name',
				'builddate',
				'repository',
				'architecture'
			))) {
				$this->orderby = $this->Input->Get->getString('orderby');
			}
		} catch(RequestException $e) {
		}
		$this->sort = $this->Input->Get->getInt('sort', 1) > 0 ? 1 : 0;
		$this->package = $this->Input->Get->getInt('package', 0);
		$this->repository = $this->Input->Post->getInt('repository', $this->Input->Get->getInt('repository', 0));
		try {
			if ($this->Input->Get->isInt('architecture')) {
				$this->architecture = $this->Input->Get->getInt('architecture');
			} elseif ($this->Input->Post->isInt('architecture')) {
				$this->architecture = $this->Input->Post->getInt('architecture');
			} else {
				$this->architecture = $this->Input->Cookie->getInt('architecture');
			}
			$this->Output->setCookie('architecture', $this->architecture, (time() + 31536000));
		} catch(RequestException $e) {
		}
		$this->group = $this->Input->Post->getInt('group', $this->Input->Get->getInt('group', 0));
		$this->packager = $this->Input->Get->getInt('packager', 0);
		$this->search = cutString(htmlspecialchars(preg_replace('/[^\w\.\+\- ]/', '', $this->Input->Post->getString('search', $this->Input->Get->getString('search', '')))) , 50);
		if (strlen($this->search) < 2) {
			$this->search = '';
		}
		$this->searchField = $this->Input->Post->getInt('searchfield', $this->Input->Get->getInt('searchfield', 0));

		$packages = DB::prepare('
		SELECT
			packages.id,
			packages.name,
			packages.version,
			packages.desc,
			packages.builddate,
			architectures.name AS architecture,
			repositories.name AS repository
		FROM
			packages,
			repositories,
			architectures
			' . ($this->group > 0 ? ',package_group' : '') . '
			' . (!empty($this->search) && $this->searchField == 2 ? ',file_index, package_file_index' : '') . '
		WHERE
			packages.repository = repositories.id
			' . ($this->repository > 0 ? 'AND packages.repository = ' . $this->repository : '') . '
			AND packages.arch = architectures.id
			' . ($this->architecture > 0 ? 'AND packages.arch = ' . $this->architecture : '') . '
			' . ($this->group > 0 ? 'AND package_group.package = packages.id AND package_group.group = ' . $this->group : '') . '
			' . (empty($this->search) ? '' : $this->getSearchStatement()) . '
			' . (!empty($this->search) && $this->searchField == 2 ? ' GROUP BY packages.id ' : ' ') . '
			' . ($this->packager > 0 ? ' AND packages.packager = ' . $this->packager : '') . '
		ORDER BY
			' . $this->orderby . ' ' . ($this->sort > 0 ? 'DESC' : 'ASC') . '
		LIMIT
			' . $this->package . ',' . $this->maxPackages . '
		');
		!empty($this->search) && $packages->bindValue('search', $this->searchString, PDO::PARAM_STR);
		$packages->execute();

		$body = '
		<div class="box">
		<h2>Paket-Suche</h2>
		<form method="post" action="?page=Packages">
		<table id="searchbox">
			<tr>
				<th>Repositorium</th>
				<th>Architektur</th>
				<th>Gruppe</th>
				<th>Schlüsselwörter</th>
			</tr>
			<tr>
				<td>
					' . $this->getRepositoryList() . '
				</td>
				<td>
					' . $this->getArchitectureList() . '
				</td>
				<td>
					' . $this->getGroupList() . '
				</td>
				<td>
					<input type="text" name="search" id="searchfield" class="ui-autocomplete-input" value="' . $this->search . '" size="34" maxlength="50" autocomplete="off" />
					<div style="padding-top: 5px;">' . $this->getSearchFields() . '</div>
					' . (in_array($this->searchField, array(
			0,
			2
		)) ? '<script type="text/javascript" src="jquery.min.js?v=1.4.2"></script>
					<script type="text/javascript" src="jquery-ui-autocomplete.min.js?v=1.8.5"></script>
					<script>
						$(function() {
							$("#searchfield").autocomplete({
								source: "?page=PackagesSuggest;repo=' . $this->repository . ';arch=' . $this->architecture . ';field=' . $this->searchField . '",
								minLength: 2,
								delay: 100
							});
						});
					</script>' : '') . '
					<input type="hidden" name="packager" value="' . $this->packager . '" />
				</td>
			</tr>
		</table>
		</form>
		</div>';
		$body.= $this->showPackageList($packages);
		$this->setValue('body', $body);
	}

	private function getSearchStatement() {
		switch ($this->searchField) {
			case 0:
				$this->searchString = '%' . $this->search . '%';
				return 'AND packages.name LIKE :search';
			break;
			case 1:
				$this->searchString = $this->search;
				return 'AND MATCH(packages.desc) AGAINST ( :search )';
			break;
			case 2:
				$this->searchString = $this->search . '%';
				return 'AND package_file_index.package = packages.id AND file_index.id = package_file_index.file_index AND file_index.name LIKE :search';
			break;
			default:
				$this->searchString = '%' . $this->search . '%';
				return 'AND packages.name LIKE :search';
		}
	}

	private function getSearchFields() {
		$options = '';
		foreach (array(
			0 => 'Name',
			1 => 'Beschreibung',
			2 => 'Datei'
		) as $key => $value) {
			if ($key == $this->searchField) {
				$selected = ' checked="checked"';
			} else {
				$selected = '';
			}
			$options.= ' <input type="radio" id="searchfield_' . $key . '" name="searchfield" value="' . $key . '"' . $selected . '  onchange="this.form.submit()" /> <label for="searchfield_' . $key . '">' . $value . '</label>';
		}
		return $options;
	}

	private function getRepositoryList() {
		$options = '<select name="repository" onchange="this.form.submit()">';
		$repositories = DB::query('
		SELECT 0 AS id, \'\' AS name
		UNION
		SELECT
			id,
			name
		FROM
			repositories
		');
		foreach ($repositories as $repository) {
			if ($this->repository == $repository['id']) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options.= '<option value="' . $repository['id'] . '"' . $selected . '>' . $repository['name'] . '</option>';
		}
		return $options . '</select>';
	}

	private function getArchitectureList() {
		$options = '<select name="architecture" onchange="this.form.submit()">';
		$architectures = DB::query('
		SELECT 0 AS id, \'\' AS name
		UNION
		SELECT
			id,
			name
		FROM
			architectures
		ORDER BY
			name ASC
		');
		foreach ($architectures as $architecture) {
			if ($this->architecture == $architecture['id']) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options.= '<option value="' . $architecture['id'] . '"' . $selected . '>' . $architecture['name'] . '</option>';
		}
		return $options . '</select>';
	}

	private function getGroupList() {
		$options = '<select name="group" onchange="this.form.submit()">';
		$groups = DB::query('
		SELECT 0 AS id, \'\' AS name
		UNION
		SELECT
			id,
			name
		FROM
			groups
		ORDER BY
			name ASC
		');
		foreach ($groups as $group) {
			if ($this->group == $group['id']) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$options.= '<option value="' . $group['id'] . '"' . $selected . '>' . $group['name'] . '</option>';
		}
		return $options . '</select>';
	}

	private function showPackageList($packages) {
		$link = '?page=Packages;package=' . $this->package . ';repository=' . $this->repository . ';architecture=' . $this->architecture . ';group=' . $this->group . ';packager=' . $this->packager . ';search=' . urlencode($this->search) . ';searchfield=' . $this->searchField;
		$curlink = '?page=Packages;orderby=' . $this->orderby . ';sort=' . $this->sort . ';repository=' . $this->repository . ';architecture=' . $this->architecture . ';group=' . $this->group . ';packager=' . $this->packager . ';search=' . urlencode($this->search) . ';searchfield=' . $this->searchField;
		$next = ' <a href="' . $curlink . ';package=' . ($this->maxPackages + $this->package) . '">&#187;</a>';
		$last = ($this->package > 0 ? '<a href="' . $curlink . ';package=' . nat($this->package - $this->maxPackages) . '">&#171;</a>' : '');
		$body = '<table class="pretty-table">
			<tr>
				<td class="pages" colspan="6">' . $last . $next . '</td>
			</tr>
			<tr>
				<th><a href="' . $link . ';orderby=repository;sort=' . abs($this->sort - 1) . '">Repositorium</a></th>
				<th><a href="' . $link . ';orderby=architecture;sort=' . abs($this->sort - 1) . '">Architektur</a></th>
				<th><a href="' . $link . ';orderby=pkgname;sort=' . abs($this->sort - 1) . '">Name</a></th>
				<th>Version</th>
				<th>Beschreibung</th>
				<th><a href="' . $link . ';orderby=lastupdate;sort=' . abs($this->sort - 1) . '">Aktualisierung</a></th>
			</tr>';
		foreach ($packages as $package) {
			$style = (in_array($package['repository'], array(
				'testing',
				'community-testing',
				'staging'
			)) ? ' class="less"' : '');
			$body.= '<tr' . $style . '>
				<td>' . $package['repository'] . '</td><td>' . $package['architecture'] . '</td><td><a href="?page=PackageDetails;repo=' . $package['repository'] . ';arch=' . $package['architecture'] . ';pkgname=' . $package['name'] . '">' . $package['name'] . '</a></td><td>' . $package['version'] . '</td><td>' . cutString($package['desc'], 70) . '</td><td>' . $this->L10n->getDateTime($package['builddate']) . '</td>
			</tr>';
		}
		$body.= '
			<tr>
				<td class="pages" colspan="6">' . $last . $next . '</td>
			</tr>
		</table>';
		return $body;
	}
}

?>
