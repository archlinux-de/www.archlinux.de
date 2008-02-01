<?php
/*
	Copyright 2002-2007 Pierre Schmitz <pschmitz@laber-land.de>

	This file is part of LL.

	LL is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	LL is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with LL.  If not, see <http://www.gnu.org/licenses/>.
*/

class Packages extends Page{

private $package 	= 0;
private $maxPackages 	= 100;
private $orderby 	= 'lastupdate';
private $sort 		= 1;
private $repository	= 0;
private $category	= 0;
private $search		= '';
private $searchString	= '';
private $searchField 	= 0;

protected function makeMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=Download">ISOs</a></li>
			<li class="selected">Pakete</li>
			<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Bugs">Bugs</a></li>
			<li><a href="http://wiki.archlinux.de">Wiki</a></li>
			<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li><a href="?page=Start">Start</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', 'Paket-Suche');

	try
		{
		if (in_array($this->Io->getString('orderby'), array('pkgname', 'lastupdate', 'category', 'repository')))
			{
			$this->orderby = $this->Io->getString('orderby');
			}
		}
	catch (IoRequestException $e)
		{
		}

	try
		{
		$this->sort = $this->Io->getInt('sort') > 0 ? 1 : 0;
		}
	catch (IoRequestException $e)
		{
		}

	try
		{
		$this->package = $this->Io->getInt('package');
		}
	catch (IoRequestException $e)
		{
		}

	try
		{
		$this->repository = $this->Io->getInt('repository');
		}
	catch (IoRequestException $e)
		{
		}

	try
		{
		$this->category = $this->Io->getInt('category');
		}
	catch (IoRequestException $e)
		{
		}

	try
		{
		$this->search = cutString(htmlspecialchars(preg_replace('/[^\w\.\+ ]/', '', $this->Io->getString('search'))), 50);
		}
	catch (IoRequestException $e)
		{
		}

	try
		{
		$this->searchField = $this->Io->getInt('searchfield');
		}
	catch (IoRequestException $e)
		{
		}


	try
		{
		if (empty($this->search))
			{
			$packages = $this->DB->getRowSet
				('
				SELECT
					packages.id,
					packages.pkgname,
					packages.pkgver,
					packages.pkgrel,
					packages.pkgdesc,
					packages.lastupdate,
					packages.needupdate,
					categories.name AS category,
					repositories.name AS repository
				FROM
					pkgdb.packages,
					pkgdb.categories,
					pkgdb.repositories
				WHERE
					packages.category = categories.id
					AND packages.repository = repositories.id
					'.($this->repository > 0 ? 'AND packages.repository = '.$this->repository : '').'
					'.($this->category > 0 ? 'AND packages.category = '.$this->category : '').'
				ORDER BY
					'.$this->orderby.' '.($this->sort > 0 ? 'DESC' : 'ASC').'
				LIMIT
					'.$this->package.','.$this->maxPackages.'
				');
			}
		else
			{
			$stm = $this->DB->prepare
				('
				SELECT
					packages.id,
					packages.pkgname,
					packages.pkgver,
					packages.pkgrel,
					packages.pkgdesc,
					packages.lastupdate,
					packages.needupdate,
					categories.name AS category,
					repositories.name AS repository
				FROM
					pkgdb.packages,
					pkgdb.categories,
					pkgdb.repositories
					'.($this->searchField == 2 ? ',pkgdb.files' : '').'
				WHERE
					packages.category = categories.id
					AND packages.repository = repositories.id
					'.($this->repository > 0 ? 'AND packages.repository = '.$this->repository : '').'
					'.($this->category > 0 ? 'AND packages.category = '.$this->category : '').'
					'.$this->getSearchStatement().'
				GROUP BY
					packages.id
				ORDER BY
					'.$this->orderby.' '.($this->sort > 0 ? 'DESC' : 'ASC').'
				LIMIT
					'.$this->package.','.$this->maxPackages.'
				');
			$stm->bindString($this->searchString);
			$packages = $stm->getRowSet();
			}
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$body = '
		<div class="greybox" id="searchbox">
		<h4 style="text-align: right">Paket-Suche</h4>
		<form method="post" action="?page=Packages">
		<table width="100%">
			<tr>
				<th>Repositorium</th>
				<th>Kategorie</th>
				<th colspan="2">Schlüsselwörter</th>
			</tr>
			<tr>
				<td>
					'.$this->getRepositoryList().'
				</td>
				<td>
					'.$this->getCategoryList().'
				</td>
				<td>
					<input type="text" name="search" id="searchfield" value="'.$this->search.'" size="34" maxlength="50" />
					<div style="padding-top: 5px;">'.$this->getSearchFields().'</div>
				</td>
				<td>
					<input type="submit" value="Suchen" />
				</td>
			</tr>
		</table>
		</form>
		<script type="text/javascript">
			/* <![CDATA[ */
			document.getElementById("searchfield").focus();
			/* ]]> */
		</script>
		</div>';

	$body .= $this->showPackageList($packages);
	isset($stm) && $stm->close();

	$this->setValue('body', $body);
	}

private function getSearchStatement()
	{
	switch ($this->searchField)
		{
		case 0: $this->searchString = $this->search.'%'; return 'AND packages.pkgname LIKE ?'; break;
		case 1: $this->searchString = $this->search.'*'; return 'AND MATCH(packages.pkgdesc) AGAINST ( ? IN BOOLEAN MODE )'; break;
		case 2: $this->searchString = $this->search.'%'; return 'AND files.package = packages.id AND files.file LIKE ?'; break;
		default: $this->searchString = $this->search.'%'; return 'AND packages.pkgname LIKE ?';
		}
	}

private function getSearchFields()
	{
	$options = '';

	foreach (array(0 => 'Name', 1 => 'Beschreibung', 2 => 'Datei') as $key => $value)
		{
		if ($key == $this->searchField)
			{
			$selected = ' checked="checked"';
			}
		else
			{
			$selected = '';
			}

		$options .= '<input type="radio" name="searchfield" value="'.$key.'"'.$selected.' />'.$value;
		}

	return $options;
	}

private function getRepositoryList()
	{
	$options = '<select name="repository">';

	try
		{
		$repositories = $this->DB->getRowSet
			('
			SELECT 0 AS id, \'\' AS name
			UNION
			SELECT
				id,
				name
			FROM
				pkgdb.repositories
			');

		foreach ($repositories as $repository)
			{
			if ($this->repository == $repository['id'])
				{
				$selected = ' selected="selected"';
				}
			else
				{
				$selected = '';
				}

			$options .= '<option value="'.$repository['id'].'"'.$selected.'>'.$repository['name'].'</option>';
			}
		}
	catch (DBNoDataException $e)
		{
		}

	return $options.'</select>';
	}

private function getCategoryList()
	{
	$options = '<select name="category">';

	try
		{
		$categories = $this->DB->getRowSet
			('
			SELECT 0 AS id, \'\' AS name
			UNION
			SELECT
				id,
				name
			FROM
				pkgdb.categories
			');

		foreach ($categories as $category)
			{
			if ($this->category == $category['id'])
				{
				$selected = ' selected="selected"';
				}
			else
				{
				$selected = '';
				}

			$options .= '<option value="'.$category['id'].'"'.$selected.'>'.$category['name'].'</option>';
			}
		}
	catch (DBNoDataException $e)
		{
		}

	return $options.'</select>';
	}

private function showPackageList($packages)
	{
	$link = '?page=Packages;package='.$this->package.';category='.$this->category.';repository='.$this->repository.';search='.urlencode($this->search).';searchfield='.$this->searchField;
	$curlink = '?page=Packages;orderby='.$this->orderby.';sort='.$this->sort;

	$next = ' <a href="'.$curlink.';package='.($this->maxPackages+$this->package).'">&#187;</a>';
	$last = ($this->package > 0
		? '<a href="'.$curlink.';package='.nat($this->package-$this->maxPackages).'">&#171;</a>'
		: '');

	$body = '<table id="packages">
			<tr>
				<th><a href="'.$link.';orderby=repository;sort='.abs($this->sort-1).'">Repositorium</a></th>
				<th><a href="'.$link.';orderby=category;sort='.abs($this->sort-1).'">Kategorie</a></th>
				<th><a href="'.$link.';orderby=pkgname;sort='.abs($this->sort-1).'">Name</a></th>
				<th>Version</th>
				<th>Beschreibung</th>
				<th><a href="'.$link.';orderby=lastupdate;sort='.abs($this->sort-1).'">Letzte&nbsp;Aktualisierung</a></th>
			</tr>
			<tr>
				<td class="pages" colspan="6">'.$last.$next.'</td>
			</tr>';

	$line = 0;

	foreach ($packages as $package)
		{
		$needupdate = $package['needupdate'] > 0 ? ' class="outdated"' : '';

		$body .= '<tr class="packageline'.$line.'">
				<td>'.$package['repository'].'</td><td>'.$package['category'].'</td><td><a href="?page=PackageDetails;package='.$package['id'].'">'.$package['pkgname'].'</a></td><td'.$needupdate.'>'.$package['pkgver'].'-'.$package['pkgrel'].'</td><td>'.cutString($package['pkgdesc'], 70).'</td><td>'.formatDate($package['lastupdate']).'</td>
			</tr>';

		$line = abs($line-1);
		}

	$body .= '
			<tr>
				<td class="pages" colspan="6">'.$last.$next.'</td>
			</tr>
		</table>';

	return $body;
	}

}

?>