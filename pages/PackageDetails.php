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

class PackageDetails extends Page{

private $package = 0;


protected function makeMenu()
	{
	return '
		<ul id="nav">
			<li><a href="http://wiki.archlinux.de/?title=Spenden">Spenden</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Download">ISOs</a></li>
			<li class="selected"><a href="?page=Packages">Pakete</a></li>
			<li><a href="http://wiki.archlinux.de/?title=AUR">AUR</a></li>
			<li><a href="http://wiki.archlinux.de/?title=Bugs">Bugs</a></li>
			<li><a href="http://wiki.archlinux.de">Wiki</a></li>
			<li><a href="http://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li><a href="?page=Start">Start</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', 'Paket-Details');

	try
		{
		$this->package = $this->Io->getInt('package');
		}
	catch (IoRequestException $e)
		{
		$this->showFailure('Kein Paket angegeben!');
		}

	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				packages.pkgname,
				packages.pkgver,
				packages.pkgrel,
				packages.pkgdesc,
				packages.lastupdate,
				packages.needupdate,
				packages.url,
				packages.origid,
				repositories.name AS repository,
				maintainers.realname AS maintainer
			FROM
				pkgdb.packages
					LEFT JOIN pkgdb.maintainers ON packages.maintainer = maintainers.id,
				pkgdb.repositories
			WHERE
				packages.id = ?
				AND packages.repository = repositories.id
			');
		$stm->bindInteger($this->package);
		$data = $stm->getRow();
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		$this->Io->setStatus(Io::NOT_FOUND);
		$this->showFailure('Paket nicht gefunden!');
		}

	$this->setValue('title', $data['pkgname']);

	if ($data['repository'] == 'Testing')
		{
		$style = ' class="testingpackage"';
		}
	else
		{
		$style = '';
		}

	$svnLink = 'http://svn.archlinux.org/'.$data['pkgname'].'/repos/'.strtolower($data['repository']).'-i686';

	$body = '<div id="box">
		<h1 id="packagename">'.$data['pkgname'].'</h1>
		<div id="packagelinks">
			<ul>
				<li><a href="'.$svnLink.'">SVN Eintrag</a></li>
				<li>'.($this->Io->isRequest('view') ? '<a href="?page=PackageDetails;package='.$this->package.'">Details</a>' : '<a href="?page=PackageDetails;package='.$this->package.';view=FileList">Dateien</a>').'</li>
				'.($data['needupdate'] > 0 ? '' : '<li><a href="http://www.archlinux.org/packages/flag/'.$data['origid'].'/" onclick="return !window.open(\'http://www.archlinux.org/packages/flag/'.$data['origid'].'/\',\'Flag Package Out-of-Date\',\'height=250,width=450,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no\');">Veraltetes Paket melden</a>
				</li>').'
			</ul>
		</div>
		<table id="packagedetails">
			<tr>
				<th>Programm-Version</th>
				<td'.($data['needupdate'] > 0 ? ' class="outdated"' : '').'>'.$data['pkgver'].'</td>
			</tr>
			<tr>
				<th>Paket-Version</th>
				<td>'.$data['pkgrel'].'</td>
			</tr>
			<tr>
				<th>Repositorium</th>
				<td'.$style.'>'.$data['repository'].'</td>
			</tr>
			<tr>
				<th>Beschreibung</th>
				<td>'.$data['pkgdesc'].'</td>
			</tr>
			<tr>
				<th>URL</th>
				<td><a rel="nofollow" href="'.$data['url'].'">'.$data['url'].'</a></td>
			</tr>
			<tr>
				<th>Betreuer</th>
				<td>'.$data['maintainer'].'</td>
			</tr>
			<tr>
				<th>Letzte&nbsp;Aktualisierung</th>
				<td>'.formatDate($data['lastupdate']).'</td>
			</tr>
		</table>';

		if ($this->Io->isRequest('view'))
			{
			$body .=
			'<table id="packagedependencies">
				<tr>
					<th>Dateien</th>
				</tr>
				<tr>
					<td>
						'.$this->getFiles().'
					</td>
				</tr>
			</table>
			</div>
			';
			}
		else
			{
			$body .=
			'<table id="packagedependencies">
				<tr>
					<th>Abhängigkeiten</th>
					<th>Inverse&nbsp;Abhängigkeiten</th>
					<th>Quellen</th>
				</tr>
				<tr>
					<td>
						'.$this->getDependencies().'
					</td>
					<td>
						'.$this->getInverseDependencies().'
					</td>
					<td>
						'.$this->getSources().'
					</td>
				</tr>
			</table>
			</div>
			';
			}


	$this->setValue('body', $body);
	}

private function getFiles()
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				path
			FROM
				pkgdb.files
			WHERE
				package = ?
			');
		$stm->bindInteger($this->package);

		$list = '<ul>';
		foreach ($stm->getColumnSet() as $file)
			{
			$list .= '<li>'.$file.'</li>';
			}
		$list .= '</ul>';
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		$list = '';
		}

	return $list;
	}

private function getDependencies()
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				packages.id,
				packages.pkgname,
				dependencies.comment
			FROM
				pkgdb.dependencies
					LEFT JOIN pkgdb.packages
					ON dependencies.dependency = packages.id
			WHERE
				dependencies.package = ?
			ORDER BY
				packages.pkgname
			');
		$stm->bindInteger($this->package);

		$list = '<ul>';
		foreach ($stm->getRowSet() as $dependency)
			{
			$list .= '<li><a href="?page=PackageDetails;package='.$dependency['id'].'">'.$dependency['pkgname'].'</a>'.$dependency['comment'].'</li>';
			}
		$list .= '</ul>';
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		$list = '';
		}

	return $list;
	}

private function getInverseDependencies()
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				packages.id,
				packages.pkgname,
				dependencies.comment
			FROM
				pkgdb.packages,
				pkgdb.dependencies
			WHERE
				dependencies.dependency = ?
				AND dependencies.package = packages.id
			ORDER BY
				packages.pkgname
			');
		$stm->bindInteger($this->package);

		$list = '<ul>';
		foreach ($stm->getRowSet() as $dependency)
			{
			$list .= '<li><a href="?page=PackageDetails;package='.$dependency['id'].'">'.$dependency['pkgname'].'</a>'.$dependency['comment'].'</li>';
			}
		$list .= '</ul>';
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		$list = '';
		}

	return $list;
	}

private function getSources()
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				sources.url
			FROM
				pkgdb.sources
			WHERE
				sources.package = ?
			');
		$stm->bindInteger($this->package);

		$list = '<ul>';
		foreach ($stm->getColumnSet() as $url)
			{
			if (preg_match('#^(https?|ftp)://#', $url))
				{
				$list .= '<li><a rel="nofollow" href="'.$url.'">'.$url.'</a></li>';
				}
			else
				{
				$list .= '<li>'.$url.'</li>';
				}
			}
		$list .= '</ul>';
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		$list = '';
		}

	return $list;
	}

}

?>