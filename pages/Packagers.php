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

class Packagers extends Page {

	private $orderby = 'name';
	private $sort = 0;

	public function prepare() {
		$this->setValue('title', $this->l10n->getText('Packagers'));
		try {
			if (in_array(Input::get()->getString('orderby') , array(
				'name',
				'lastbuilddate',
				'packages'
			))) {
				$this->orderby = Input::get()->getString('orderby');
			}
		} catch(RequestException $e) {
		}
		$this->sort = Input::get()->getInt('sort', 0) > 0 ? 1 : 0;
		$packages = DB::query('SELECT COUNT(*) FROM packages')->fetchColumn();
		$packagers = DB::query('
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
			' . $this->orderby . ' ' . ($this->sort > 0 ? 'DESC' : 'ASC') . '
		');
		$body = '
		<table class="pretty-table">
			<tr>
				<th><a href="?page=Packagers;orderby=name;sort=' . abs($this->sort - 1) . '">'.$this->l10n->getText('Name').'</a></th>
				<th>'.$this->l10n->getText('Email').'</th>
				<th colspan="2"><a href="?page=Packagers;orderby=packages;sort=' . abs($this->sort - 1) . '">'.$this->l10n->getText('Packages').'</a></th>
				<th><a href="?page=Packagers;orderby=lastbuilddate;sort=' . abs($this->sort - 1) . '">'.$this->l10n->getText('Last update').'</a></th>
			</tr>';
		foreach ($packagers as $packager) {
			$percent = round(($packager['packages'] / $packages) * 100);
			$body.= '<tr>
				<td>' . $packager['name'] . '</td>
				<td>' . (empty($packager['email']) ? '' : '<a href="mailto:' . $packager['email'] . '">' . $packager['email'] . '</a>') . '</td>
				<td style="text-align:right;"><a href="?page=Packages;packager=' . $packager['id'] . '">' . $packager['packages'] . '</a></td>
				<td style="width:100px;"><div style="background-color:#1793d1;width:' . $percent . 'px;">&nbsp;</div></td>
				<td>' . $this->l10n->getDateTime($packager['lastbuilddate']) . '</td>
			</tr>';
		}
		$body.= '</table>';
		$this->setValue('body', $body);
	}
}

?>
