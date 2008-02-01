#!/usr/bin/php
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


ini_set('max_execution_time', 0);

$secret 		= '';
$pubPackagesFile 	= '';
$targetHost		= '';
$dbuser			= '';
$dbpw			= '';

$pwd = dirname(getenv('_')).'/';
require ($pwd.'LocalSettings.php');

if (file_exists($pwd.'updateRunning.lock'))
	{
	die(1);
	}
else
	{
	touch($pwd.'updateRunning.lock');
	}

if (!file_exists($pwd.'lastrun.log'))
	{
	die(1);
	}
else
	{
	$lastrun = trim(file_get_contents($pwd.'lastrun.log'));
	file_put_contents($pwd.'lastrun.log', time());
	}

if (!extension_loaded('mysqli'))
	{
	dl('mysqli.so');
	}

if (!extension_loaded('curl'))
	{
	dl('curl.so');
	}

require ($pwd.'Exceptions.php');
require ($pwd.'DB.php');

$DB = new DB($dbuser, $dbpw, 'archlinux');

try
	{
	$DB->execute('LOCK TABLES packages READ, packages_files READ, categories READ, auth_user READ, repos READ');
	}
catch (DBException $e)
	{
	echo 'Server: Warning: Could not lock tables!';
	}


$packageIDList = $DB->getColumnSet('SELECT id FROM packages');

$stm = $DB->prepare
	('
	SELECT
		packages.id,
		repos.name AS repository,
		auth_user.username AS maintainer,
		categories.category,
		packages.needupdate,
		packages.pkgname,
		packages.pkgver,
		packages.pkgrel,
		packages.pkgdesc,
		packages.url,
		packages.sources,
		packages.depends,
		UNIX_TIMESTAMP(packages.last_update) AS lastupdate
	FROM
		packages
			LEFT JOIN auth_user ON packages.maintainer_id = auth_user.id,
		categories,
		repos
	WHERE
		packages.category_id = categories.id
		AND packages.repo_id = repos.id
		AND UNIX_TIMESTAMP(last_update) > ?
	');
/** make sure to get all chagnes which were commited during the last run */
$stm->bindInteger($lastrun-360);

$stm2 = $DB->prepare
	('
	SELECT
		path
	FROM
		packages_files
	WHERE
		pkg_id = ?
	');

try
	{
	$filename = sha1($secret.rand());
	$fh = fopen($pubPackagesFile, 'w');
	flock($fh, LOCK_EX);
	fwrite($fh, base64_encode(gzcompress(serialize($packageIDList)))."\n");

	echo 'Server: Getting Updates...';
	foreach ($stm->getRowSet() as $package)
		{
		$stm2->bindInteger($package['id']);
		$package['files'] = array();
		try
			{
			foreach ($stm2->getColumnSet() as $file)
				{
				$package['files'][] = $file;
				}
			}
		catch (DBNoDataException $e)
			{
			/** Some old packages do not have any filelist */
			}
		/** this is a funny file format... */
		fwrite($fh, base64_encode(gzcompress(serialize($package)))."\n");
		}

	flock($fh, LOCK_UN);
	fclose($fh);
	echo 'done', "\n";
	$sha1sum = sha1_file($pubPackagesFile);
	}
catch (DBNoDataException $e)
	{
	echo 'No Updates available. Good Bye!', "\n";
	$stm2->close();
	$stm->close();
	unlink($pwd.'updateRunning.lock');
	exit(0);
	}

$stm2->close();
$stm->close();

try
	{
	$DB->execute('UNLOCK TABLES');
	}
catch (DBException $e)
	{
	echo 'Server: Warning: Could not unlock tables!';
	}

echo 'Server: Sending request to client...', "\n";
/** notify archlinux.de to grab the updates via http */
$seed = sha1(rand());
$sum = sha1($seed.$secret);
$curl = curl_init($targetHost);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_POSTFIELDS, 'page=GetUpdates&sha1sum='.$sha1sum.'&seed='.$seed.'&sum='.$sum);
curl_exec($curl);
echo curl_error($curl);
curl_close($curl);

unlink($pubPackagesFile);
unlink($pwd.'updateRunning.lock');

echo 'Server: done', "\n";

?>
