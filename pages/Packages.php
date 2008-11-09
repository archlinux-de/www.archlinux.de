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
private $maxPackages 	= 50;
private $orderby 	= 'builddate';
private $sort 		= 1;
private $repository	= 0;
private $architecture	= 1;
private $group		= 0;
private $packager	= 0;
private $search		= '';
private $searchString	= '';
private $searchField 	= 0;

protected function makeMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=Spenden">Spenden</a></li>
			<li class="selected">Pakete</li>
			<li><a href="http://wiki.archlinux.de">Wiki</a></li>
			<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li><a href="?page=Start">Start</a></li>
		</ul>';
	}

protected function makeSubMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
			<li><a href="?page=PackageStatistics">Statistiken</a></li>
			<li><a href="?page=MirrorStatus">Server</a></li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li><a href="?page=ArchitectureDifferences">Architekturen</a></li>
			<li class="selected">Suche</li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', 'Paket-Suche');

	try
		{
		if (in_array($this->Input->Request->getString('orderby'), array('name', 'builddate', 'repository', 'architecture')))
			{
			$this->orderby = $this->Input->Request->getString('orderby');
			}
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->sort = $this->Input->Request->getInt('sort') > 0 ? 1 : 0;
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->package = $this->Input->Request->getInt('package');
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->repository = $this->Input->Request->getInt('repository');
		}
	catch (RequestException $e)
		{
		}

	try
		{
		if ($this->Input->Get->isValid('architecture'))
			{
			$this->architecture = $this->Input->Get->getInt('architecture');
			}
		elseif ($this->Input->Post->isValid('architecture'))
			{
			$this->architecture = $this->Input->Post->getInt('architecture');
			}
		else
			{
			$this->architecture = $this->Input->Cookie->getInt('architecture');
			}

		$this->Output->setCookie('architecture', $this->architecture, (time() + 31536000));
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->group = $this->Input->Request->getInt('group');
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->packager = $this->Input->Request->getInt('packager');
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->search = cutString(htmlspecialchars(preg_replace('/[^\w\.\+\- ]/', '', $this->Input->Request->getString('search'))), 50);
		if (strlen($this->search) < 2)
			{
			$this->search = '';
			}
		}
	catch (RequestException $e)
		{
		}

	try
		{
		$this->searchField = $this->Input->Request->getInt('searchfield');
		}
	catch (RequestException $e)
		{
		}


	try
		{
		$stm = $this->DB->prepare
			('
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
				'.($this->group > 0 ? ',package_group': '').'
				'.(!empty($this->search) && $this->searchField == 2 ? ',file_index, package_file_index' : '').'
			WHERE
				packages.repository = repositories.id
				'.($this->repository > 0 ? 'AND packages.repository = '.$this->repository : '').'
				AND packages.arch = architectures.id
				'.($this->architecture > 0 ? 'AND packages.arch = '.$this->architecture : '').'
				'.($this->group > 0 ? 'AND package_group.package = packages.id AND package_group.group = '.$this->group : '').'
				'.(empty($this->search) ? '' : $this->getSearchStatement()).'
				'.(!empty($this->search) && $this->searchField == 2 ? ' GROUP BY packages.id ' : ' ').'
				'.($this->packager > 0 ? ' AND packages.packager = '.$this->packager : '').'
			ORDER BY
				'.$this->orderby.' '.($this->sort > 0 ? 'DESC' : 'ASC').'
			LIMIT
				'.$this->package.','.$this->maxPackages.'
			');
		!empty($this->search) && $stm->bindString($this->searchString);
		$packages = $stm->getRowSet();
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
				<th>Architektur</th>
				<th>Gruppe</th>
				<th colspan="2">Schlüsselwörter</th>
			</tr>
			<tr>
				<td>
					'.$this->getRepositoryList().'
				</td>
				<td>
					'.$this->getArchitectureList().'
				</td>
				<td>
					'.$this->getGroupList().'
				</td>
				<td>
					<input type="text" name="search" id="searchfield" value="'.$this->search.'" size="34" maxlength="50" />
					<div style="padding-top: 5px;">'.$this->getSearchFields().'</div>
				</td>
				<td>
					<input type="submit" value="Suchen" />
					<input type="hidden" name="packager" value="'.$this->packager.'" />
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
		case 0: $this->searchString = '%'.$this->search.'%'; return 'AND packages.name LIKE ?'; break;
		case 1: $this->searchString = $this->search; return 'AND MATCH(packages.desc) AGAINST ( ? )'; break;
		case 2: $this->searchString = $this->search.'%'; return 'AND package_file_index.package = packages.id AND file_index.id = package_file_index.file_index AND file_index.name LIKE ?'; break;
		default: $this->searchString = '%'.$this->search.'%'; return 'AND packages.name LIKE ?';
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
	$options = '<select name="repository" onchange="this.form.submit()">';

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
				repositories
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

private function getArchitectureList()
	{
	$options = '<select name="architecture" onchange="this.form.submit()">';

	try
		{
		$architectures = $this->DB->getRowSet
			('
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

		foreach ($architectures as $architecture)
			{
			if ($this->architecture == $architecture['id'])
				{
				$selected = ' selected="selected"';
				}
			else
				{
				$selected = '';
				}

			$options .= '<option value="'.$architecture['id'].'"'.$selected.'>'.$architecture['name'].'</option>';
			}
		}
	catch (DBNoDataException $e)
		{
		}

	return $options.'</select>';
	}

private function getGroupList()
	{
	$options = '<select name="group" onchange="this.form.submit()">';

	try
		{
		$groups = $this->DB->getRowSet
			('
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

		foreach ($groups as $group)
			{
			if ($this->group == $group['id'])
				{
				$selected = ' selected="selected"';
				}
			else
				{
				$selected = '';
				}

			$options .= '<option value="'.$group['id'].'"'.$selected.'>'.$group['name'].'</option>';
			}
		}
	catch (DBNoDataException $e)
		{
		}

	return $options.'</select>';
	}

private function showPackageList($packages)
	{
	$link = '?page=Packages;package='.$this->package.';repository='.$this->repository.';architecture='.$this->architecture.';group='.$this->group.';packager='.$this->packager.';search='.urlencode($this->search).';searchfield='.$this->searchField;
	$curlink = '?page=Packages;orderby='.$this->orderby.';sort='.$this->sort.';repository='.$this->repository.';architecture='.$this->architecture.';group='.$this->group.';packager='.$this->packager.';search='.urlencode($this->search).';searchfield='.$this->searchField;

	$next = ' <a href="'.$curlink.';package='.($this->maxPackages+$this->package).'">&#187;</a>';
	$last = ($this->package > 0
		? '<a href="'.$curlink.';package='.nat($this->package-$this->maxPackages).'">&#171;</a>'
		: '');

	$body = '<table id="packages">
			<tr>
				<th><a href="'.$link.';orderby=repository;sort='.abs($this->sort-1).'">Repositorium</a></th>
				<th><a href="'.$link.';orderby=architecture;sort='.abs($this->sort-1).'">Architektur</a></th>
				<th><a href="'.$link.';orderby=pkgname;sort='.abs($this->sort-1).'">Name</a></th>
				<th>Version</th>
				<th>Beschreibung</th>
				<th><a href="'.$link.';orderby=lastupdate;sort='.abs($this->sort-1).'">Aktualisierung</a></th>
			</tr>
			<tr>
				<td class="pages" colspan="6">'.$last.$next.'</td>
			</tr>';

	$line = 0;

	foreach ($packages as $package)
		{
		$style = $package['repository'] == 'testing' ? ' testingpackage' : '';

		$body .= '<tr class="packageline'.$line.$style.'">
				<td>'.$package['repository'].'</td><td>'.$package['architecture'].'</td><td><a href="?page=PackageDetails;package='.$package['id'].'">'.$package['name'].'</a></td><td>'.$package['version'].'</td><td>'.cutString($package['desc'], 70).'</td><td>'.$this->L10n->getDateTime($package['builddate']).'</td>
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