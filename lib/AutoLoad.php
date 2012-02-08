<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

function __autoload($class) {
	$availableClasses = array(
		'CronJob' => '/CronJob.php',
		'Database' => '/Database.php',
		'Download' => '/Download.php',
		'Exceptions' => '/Exceptions.php',
		'IDatabaseCachable' => '/IDatabaseCachable.php',
		'Input' => '/Input.php',
		'L10n' => '/L10n.php',
		'ObjectCache' => '/ObjectCache.php',
		'ObjectStore' => '/ObjectStore.php',
		'Output' => '/Output.php',
		'Package' => '/Package.php',
		'PackageDatabase' => '/PackageDatabase.php',
		'Page' => '/Page.php',
		'Request' => '/Request.php',
		'StatisticsPage' => '/StatisticsPage.php',

		'GetFileFromMirror' => '/../pages/GetFileFromMirror.php',
		'GetOpenSearch' => '/../pages/GetOpenSearch.php',
		'GetRecentNews' => '/../pages/GetRecentNews.php',
		'GetRecentPackages' => '/../pages/GetRecentPackages.php',
		'MirrorStatus' => '/../pages/MirrorStatus.php',
		'NotFound' => '/../pages/NotFound.php',
		'PackageDetails' => '/../pages/PackageDetails.php',
		'Packagers' => '/../pages/Packagers.php',
		'Packages' => '/../pages/Packages.php',
		'PackagesSuggest' => '/../pages/PackagesSuggest.php',
		'Start' => '/../pages/Start.php',
		'UnsignedPackages' => '/../pages/UnsignedPackages.php'
	);

	if (Config::get('common', 'statistics')) {
		$availableClasses = array_merge($availableClasses, array(
			'PostPackageList' => '/../pages/PostPackageList.php',
			'Statistics' => '/../pages/Statistics.php',
			'PackageStatistics' => '/../pages/PackageStatistics.php',
			'UserStatistics' => '/../pages/UserStatistics.php',
			'FunStatistics' => '/../pages/FunStatistics.php',
			'RepositoryStatistics' => '/../pages/RepositoryStatistics.php'
		));
	}

	if (Config::get('common', 'legacysites')) {
		$availableClasses = array_merge($availableClasses, array(
			'MirrorStatusReflector' => '/../pages/MirrorStatusReflector.php',
			'MirrorStatusJSON' => '/../pages/MirrorStatusJSON.php',
			'MirrorProblems' => '/../pages/MirrorProblems.php',
			'ArchitectureDifferences' => '/../pages/ArchitectureDifferences.php'
		));
	}

	if (isset($availableClasses[$class])) {
		require (__DIR__.$availableClasses[$class]);
	} else {
		throw new AutoLoadException('Class '.$class.' could not be found');
	}
}

class AutoLoadException extends RuntimeException {}

?>
