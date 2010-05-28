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

class ArchitectureDifferences extends Page implements IDBCachable {


protected function makeMenu()
	{
	return '<ul>
			<li><a href="https://wiki.archlinux.de/title/Spenden">Spenden</a></li>
			<li class="selected">Pakete</li>
			<li><a href="https://wiki.archlinux.de">Wiki</a></li>
			<li><a href="https://forum.archlinux.de/?page=Forums;id=20">Forum</a></li>
			<li><a href="?page=Start">Start</a></li>
		</ul>';
	}

protected function makeSubMenu()
	{
	return '<ul>
			<li><a href="?page=Packages">Suche</a></li>
			<li class="selected">Architekturen</li>
			<li><a href="?page=Packagers">Packer</a></li>
			<li><a href="?page=MirrorStatus">Server</a></li>
			<li><a href="?page=PackageStatistics">Statistiken</a></li>
			<li><a href="https://wiki.archlinux.de/title/AUR">AUR</a></li>
		</ul>';
	}

public function prepare()
	{
	$this->setValue('title', $this->L10n->getText('Architecture differences'));

	if (!($body = $this->PersistentCache->getObject('ArchitectureDifferences:'.($this->Input->Get->isInt('showminor') ? 1 : 0).':'.$this->L10n->getLocale())))
		{
		$this->Output->setStatus(Output::NOT_FOUND);
		$this->showFailure($this->L10n->getText('No data found!'));
		}

	$this->setValue('body', $body);
	}

private static function isMinorPackageRelease($ver1, $ver2)
	{
	return self::getPackageVersion($ver1) == self::getPackageVersion($ver2) && floor(self::getPackageRelease($ver1)) == floor(self::getPackageRelease($ver2));
	}

private static function getPackageVersion($version)
	{
	$temp = explode('-', $version);
	array_pop($temp);
	return implode('-', $temp);
	}

private static function getPackageRelease($version)
	{
	$temp = explode('-', $version);
	return array_pop($temp);
	}

private static function compareVersions($ver1, $ver2)
	{
	return version_compare($ver1, $ver2);
	}

public static function updateDBCache()
	{
	try
		{
		$packages = self::get('DB')->getRowSet
			('
			(
			SELECT
				i.id AS iid,
				i.name,
				i.version AS iversion,
				x.id AS xid,
				x.version AS xversion,
				repositories.id AS repoid,
				repositories.name AS reponame,
				GREATEST(i.builddate, x.builddate) AS builddate
			FROM
				packages i
					JOIN
						packages x
					ON
						i.id <> x.id
						AND i.name = x.name
						AND i.version <> x.version
						AND i.repository = x.repository
						AND i.arch = 1
						AND x.arch = 2
					JOIN
						repositories
					ON
						i.repository = repositories.id
			)
			UNION
			(
			SELECT
				i.id AS iid,
				i.name,
				i.version AS iversion,
				0 AS xid,
				0 AS xversion,
				repositories.id AS repoid,
				repositories.name AS reponame,
				i.builddate AS builddate
			FROM
				packages i
					JOIN
						repositories
					ON
						i.repository = repositories.id
			WHERE
				i.arch = 1
				AND NOT EXISTS
					(
					SELECT
						id
					FROM
						packages x
					WHERE
						i.name = x.name
						AND i.repository = x.repository
						AND x.arch = 2
					)
			)
			UNION
			(
			SELECT
				0 AS iid,
				x.name,
				0 AS iversion,
				x.id AS xid,
				x.version AS xversion,
				repositories.id AS repoid,
				repositories.name AS reponame,
				x.builddate AS builddate
			FROM
				packages x
					JOIN
						repositories
					ON
						x.repository = repositories.id
			WHERE
				x.arch = 2
				AND NOT EXISTS
					(
					SELECT
						id
					FROM
						packages i
					WHERE
						i.name = x.name
						AND i.repository = x.repository
						AND i.arch = 1
					)
			)
			ORDER BY
				repoid ASC,
				builddate DESC
			')->toArray();
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	foreach (array(true, false) as $showminor)
		{
		$body = '
			<div class="greybox" id="searchbox">
				<h4 style="text-align: right">'.self::get('L10n')->getText('Architecture differences').'</h4>
				<div style="font-size:10px; text-align:right;padding-bottom:10px;">
				'.($showminor ? '<a href="?page=ArchitectureDifferences">'.self::get('L10n')->getText('Hide architecture specific differences').'</a>' : '<a href="?page=ArchitectureDifferences;showminor=1">'.self::get('L10n')->getText('Show architecture specific differences').'</a>').'
				</div>
			</div>
			<table id="packages">
				<tr>
					<th>'.self::get('L10n')->getText('Package name').'</th>
					<th>i686</th>
					<th>x86_64</th>
					<th>'.self::get('L10n')->getText('Last update').'</th>
				</tr>';

		$repo = 0;

		foreach ($packages as $package)
			{
			if (self::isMinorPackageRelease($package['iversion'], $package['xversion']) && !$showminor)
				{
				continue;
				}

			# hide lib32 packages in [community] and [community-testing]
			if (!$showminor 
				&& ( $package['reponame'] == 'community' || $package['reponame'] == 'community-testing' )
				&& empty($package['iid']) 
				&& strpos($package['name'], 'lib32-') === 0)
				{
				continue;
				}

			$style = ($package['reponame'] == 'testing' || $package['reponame'] == 'community-testing') ? ' testingpackage' : '';
			if ($repo != $package['repoid'])
				{
				$body .= '<tr>
						<th colspan="4" class="pages" style="background-color:#1793d1;text-align:center;">['.$package['reponame'].']</th>
					</tr>';
				}
			$minor = $showminor && self::isMinorPackageRelease($package['iversion'], $package['xversion']) ? ' style="color:green;"' : '';

			if (self::compareVersions($package['iversion'], $package['xversion']) < 0)
				{
				$iold = ' style="color:red;"';
				$xold = '';
				}
			else
				{
				$iold = '';
				$xold = ' style="color:red;"';
				}

			$body .= '<tr class="packageline'.$style.'"'.$minor.'>
					<td>'.$package['name'].'</td>
					<td>'.(empty($package['iid']) ? '' : '<a href="?page=PackageDetails;repo='.$package['reponame'].';arch=i686;pkgname='.$package['name'].'"'.$iold.'>'.$package['iversion'].'</a>').'</td>
					<td>'.(empty($package['xid']) ? '' : '<a href="?page=PackageDetails;repo='.$package['reponame'].';arch=x86_64;pkgname='.$package['name'].'"'.$xold.'>'.$package['xversion'].'</a>').'</td>
					<td>'.self::get('L10n')->getDateTime($package['builddate']).'</td>
				</tr>';

			$repo = $package['repoid'];
			}

		$body .= '</table>';

		self::get('PersistentCache')->addObject('ArchitectureDifferences:'.($showminor ? 1 : 0).':'.self::get('L10n')->getLocale(), $body);
		}
	}

}

?>
