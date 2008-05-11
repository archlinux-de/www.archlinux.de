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
ini_set('memory_limit', -1);

define('IN_LL', null);

require ('../LLPath.php');
require ('../modules/Modul.php');
require ('../modules/Settings.php');
require ('../modules/Exceptions.php');
require (LL_PATH.'modules/DB.php');
require ('PackageDB.php');


class UpdatePKGDB extends Modul {

/** Cache for id-name mapping */
private $arches 	= array();
private $packagers 	= array();
private $repos 		= array();
private $groups 	= array();
private $licenses 	= array();


public function __construct()
	{
	self::__set('Settings', new Settings());
	self::__set('DB', new DB(
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database')
		));
	}

private function getLockFile()
	{
	return ini_get('session.save_path').'/updateRunning.lock';
	}

private function getLastRunFile()
	{
	return 'lastrun.log';
	}

private function showFailure($message)
	{
	unlink($this->getLockFile());
	die($message);
	}

public function runUpdate()
	{
	$startTime = time();
	$lastrun = 0;

	if (file_exists($this->getLockFile()))
		{
		die('update still in progress');
		}
	else
		{
		touch($this->getLockFile());
		chmod($this->getLockFile(), 0600);
		}

	if (!file_exists($this->getLastRunFile()))
		{
		file_put_contents($this->getLastRunFile(), 0);
		}
	else
		{
		$lastrun = trim(file_get_contents($this->getLastRunFile()));
		}

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			pkgdb.temp_depends
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			depends LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			pkgdb.temp_provides
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			provides LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			pkgdb.temp_conflicts
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			conflicts LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			pkgdb.temp_replaces
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			replaces LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	echo 'Updating repos...', "\n";
	foreach ($this->Settings->getValue('pkgdb_repositories') as $repo)
		{
		foreach ($this->Settings->getValue('pkgdb_architectures') as $arch)
			{
			echo "\t$repo - $arch\n";
			$this->updateRepository($repo, $arch, $lastrun);
			}
		}

	echo 'Updating depends...', "\n";
	$this->updateDependencies();

	echo 'Updating provides...', "\n";
	$this->updateProvides();

	echo 'Updating conflicts...', "\n";
	$this->updateConflicts();

	echo 'Updating replaces...', "\n";
	$this->updateReplaces();

	echo 'Removing unused entires...', "\n";
	$this->removeUnusedEntries();
	echo 'done', "\n";

	file_put_contents($this->getLastRunFile(), $startTime);
	unlink($this->getLockFile());
	}

private function updateRepository($repo, $arch, $lastrun)
	{
	$pkgdb = new PackageDB($this->Settings->getValue('pkgdb_mirror'), $repo, $arch);

	$this->updatePackages($pkgdb->getUpdatedPackages($lastrun - 360), $repo, $arch);

	$this->removeDeletedPackages($repo, $arch, $pkgdb->getPackageNames());
	}

private function updatePackages($packages, $repo, $arch)
	{
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages WRITE,
			pkgdb.repositories WRITE,
			pkgdb.architectures WRITE,
			pkgdb.packagers WRITE,
			pkgdb.groups WRITE,
			pkgdb.package_group WRITE,
			pkgdb.licenses WRITE,
			pkgdb.package_license WRITE
		');

	$testSTM = $this->DB->prepare
		('
		SELECT
			packages.id
		FROM
			pkgdb.packages,
			pkgdb.repositories,
			pkgdb.architectures
		WHERE
			packages.repository = repositories.id
			AND packages.arch = architectures.id
			AND packages.name = ?
			AND repositories.name = ?
			AND architectures.name = ?
		');

	$updateSTM = $this->DB->prepare
		('
		UPDATE
			pkgdb.packages
		SET
			filename = ?,
			name = ?,
			`version` = ?,
			`desc` = ?,
			csize = ?,
			isize = ?,
			md5sum = ?,
			url = ?,
			arch = ?,
			builddate = ?,
			packager = ?,
			`force` = ?,
			repository = ?
		WHERE
			id = ?
		');

	$insertSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.packages
		SET
			filename = ?,
			name = ?,
			`version` = ?,
			`desc` = ?,
			csize = ?,
			isize = ?,
			md5sum = ?,
			url = ?,
			arch = ?,
			builddate = ?,
			packager = ?,
			`force` = ?,
			repository = ?
		');

	$dependsSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.temp_depends
		SET
			package = ?,
			depends = ?
		');

	$providesSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.temp_provides
		SET
			package = ?,
			provides = ?
		');

	$conflictsSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.temp_conflicts
		SET
			package = ?,
			conflicts = ?
		');

	$replacesSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.temp_replaces
		SET
			package = ?,
			replaces = ?
		');

	foreach ($packages as $package)
		{
		try
			{
			$testSTM->bindString(htmlspecialchars($package->getName()));
			$testSTM->bindString(htmlspecialchars($repo));
			$testSTM->bindString(htmlspecialchars($arch));
			$packageID = $testSTM->getColumn();

			$updateSTM->bindString(htmlspecialchars($package->getFileName()));
			$updateSTM->bindString(htmlspecialchars($package->getName()));
			$updateSTM->bindString(htmlspecialchars($package->getVersion()));
			$updateSTM->bindString(htmlspecialchars($package->getDescription()));
			$updateSTM->bindInteger($package->getCompressedSize());
			$updateSTM->bindInteger($package->getInstalledSize());
			$updateSTM->bindString(htmlspecialchars($package->getMD5SUM()));
			$updateSTM->bindString(htmlspecialchars($package->getURL()));
			$updateSTM->bindInteger($this->getArchitectureID($arch));
			$updateSTM->bindInteger($package->getBuildDate());
			$updateSTM->bindInteger($this->getPackagerID($package->getPackager()));
			$updateSTM->bindInteger(($package->isForced() ? 1 : 0));
			$updateSTM->bindInteger($this->getRepositoryID($repo));
			$updateSTM->bindInteger($packageID);
			$updateSTM->execute();
			}
		catch (DBNoDataException $e)
			{
			$insertSTM->bindString(htmlspecialchars($package->getFileName()));
			$insertSTM->bindString(htmlspecialchars($package->getName()));
			$insertSTM->bindString(htmlspecialchars($package->getVersion()));
			$insertSTM->bindString(htmlspecialchars($package->getDescription()));
			$insertSTM->bindInteger($package->getCompressedSize());
			$insertSTM->bindInteger($package->getInstalledSize());
			$insertSTM->bindString(htmlspecialchars($package->getMD5SUM()));
			$insertSTM->bindString(htmlspecialchars($package->getURL()));
			$insertSTM->bindInteger($this->getArchitectureID($arch));
			$insertSTM->bindInteger($package->getBuildDate());
			$insertSTM->bindInteger($this->getPackagerID($package->getPackager()));
			$insertSTM->bindInteger(($package->isForced() ? 1 : 0));
			$insertSTM->bindInteger($this->getRepositoryID($repo));

			$insertSTM->execute();
			$packageID = $this->DB->getInsertId();
			}

		// depends
		if (count($package->getDepends()) > 0)
			{
			$dependsSTM->bindInteger($packageID);
			$dependsSTM->bindString(implode(' ', $package->getDepends()));
			$dependsSTM->execute();
			}

		// provides
		if (count($package->getProvides()) > 0)
			{
			$providesSTM->bindInteger($packageID);
			$providesSTM->bindString(implode(' ', $package->getProvides()));
			$providesSTM->execute();
			}

		// conflicts
		if (count($package->getConflicts()) > 0)
			{
			$conflictsSTM->bindInteger($packageID);
			$conflictsSTM->bindString(implode(' ', $package->getConflicts()));
			$conflictsSTM->execute();
			}

		// replaces
		if (count($package->getReplaces()) > 0)
			{
			$replacesSTM->bindInteger($packageID);
			$replacesSTM->bindString(implode(' ', $package->getReplaces()));
			$replacesSTM->execute();
			}

		// groups
		if (count($package->getGroups()) > 0)
			{
			$this->addPackageToGroups($packageID, $package->getGroups());
			}

		// licenses
		if (count($package->getLicenses()) > 0)
			{
			$this->addPackageToLicenses($packageID, $package->getLicenses());
			}
		}

	$dependsSTM->close();
	$providesSTM->close();
	$conflictsSTM->close();
	$replacesSTM->close();
	$testSTM->close();
	$updateSTM->close();
	$insertSTM->close();
	$this->DB->execute('UNLOCK TABLES');
	}

private function addPackageToGroups($package, $groups)
	{
	$stm = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.package_group
		WHERE
			package = ?
		');
	$stm->bindInteger($package);
	$stm->execute();
	$stm->close();

	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.package_group
		SET
			package = ?,
			`group` = ?
		');

	foreach ($groups as $group)
		{
		$stm->bindInteger($package);
		$stm->bindInteger($this->getGroupID($group));
		$stm->execute();
		}
	}

private function getGroupID($group)
	{
	if (isset($this->groups[$group]))
		{
		return $this->groups[$group];
		}
	else
		{
		try
			{
			$stm = $this->DB->prepare
				('
				SELECT
					id
				FROM
					pkgdb.groups
				WHERE
					name = ?
				');
			$stm->bindString(htmlspecialchars($group));
			$id = $stm->getColumn();
			$stm->close();
			}
		catch (DBNoDataException $e)
			{
			$stm->close();

			$stm = $this->DB->prepare
				('
				INSERT INTO
					pkgdb.groups
				SET
					name = ?
				');
			$stm->bindString(htmlspecialchars($group));
			$stm->execute();
			$id = $this->DB->getInsertId();
			$stm->close();
			}

		$this->groups[$group] = $id;

		return $id;
		}
	}

private function addPackageToLicenses($package, $licenses)
	{
	$stm = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.package_license
		WHERE
			package = ?
		');
	$stm->bindInteger($package);
	$stm->execute();
	$stm->close();

	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.package_license
		SET
			package = ?,
			license = ?
		');

	foreach ($licenses as $license)
		{
		$stm->bindInteger($package);
		$stm->bindInteger($this->getLicenseID($license));
		$stm->execute();
		}
	}

private function getLicenseID($license)
	{
	if (isset($this->licenses[$license]))
		{
		return $this->licenses[$license];
		}
	else
		{
		try
			{
			$stm = $this->DB->prepare
				('
				SELECT
					id
				FROM
					pkgdb.licenses
				WHERE
					name = ?
				');
			$stm->bindString(htmlspecialchars($license));
			$id = $stm->getColumn();
			$stm->close();
			}
		catch (DBNoDataException $e)
			{
			$stm->close();

			$stm = $this->DB->prepare
				('
				INSERT INTO
					pkgdb.licenses
				SET
					name = ?
				');
			$stm->bindString(htmlspecialchars($license));
			$stm->execute();
			$id = $this->DB->getInsertId();
			$stm->close();
			}

		$this->licenses[$license] = $id;

		return $id;
		}
	}

private function getArchitectureID($arch)
	{
	if (isset($this->arches[$arch]))
		{
		return $this->arches[$arch];
		}
	else
		{
		try
			{
			$stm = $this->DB->prepare
				('
				SELECT
					id
				FROM
					pkgdb.architectures
				WHERE
					name = ?
				');
			$stm->bindString(htmlspecialchars($arch));
			$id = $stm->getColumn();
			$stm->close();
			}
		catch (DBNoDataException $e)
			{
			$stm->close();

			$stm = $this->DB->prepare
				('
				INSERT INTO
					pkgdb.architectures
				SET
					name = ?
				');
			$stm->bindString(htmlspecialchars($arch));
			$stm->execute();
			$id = $this->DB->getInsertId();
			$stm->close();
			}

		$this->arches[$arch] = $id;

		return $id;
		}
	}

private function getPackagerID($packager)
	{
	if (isset($this->packagers[$packager]))
		{
		return $this->packagers[$packager];
		}
	else
		{
		preg_match('/([^<>]+)(?:<(.+?)>)?/', $packager, $matches);

		$name = !empty($matches[1]) ? $matches[1] : $packager;
		$email = isset($matches[2]) ? $matches[2] : '';

		try
			{
			$stm = $this->DB->prepare
				('
				SELECT
					id
				FROM
					pkgdb.packagers
				WHERE
					name = ?
					AND email = ?
				');
			$stm->bindString(htmlspecialchars($name));
			$stm->bindString(htmlspecialchars($email));
			$id = $stm->getColumn();
			$stm->close();
			}
		catch (DBNoDataException $e)
			{
			$stm->close();

			$stm = $this->DB->prepare
				('
				INSERT INTO
					pkgdb.packagers
				SET
					name = ?,
					email = ?
				');
			$stm->bindString(htmlspecialchars($name));
			$stm->bindString(htmlspecialchars($email));
			$stm->execute();
			$id = $this->DB->getInsertId();
			$stm->close();
			}

		$this->packagers[$packager] = $id;

		return $id;
		}
	}

private function getRepositoryID($repo)
	{
	if (isset($this->repos[$repo]))
		{
		return $this->repos[$repo];
		}
	else
		{
		try
			{
			$stm = $this->DB->prepare
				('
				SELECT
					id
				FROM
					pkgdb.repositories
				WHERE
					name = ?
				');
			$stm->bindString(htmlspecialchars($repo));
			$id = $stm->getColumn();
			$stm->close();
			}
		catch (DBNoDataException $e)
			{
			$stm->close();

			$stm = $this->DB->prepare
				('
				INSERT INTO
					pkgdb.repositories
				SET
					name = ?
				');
			$stm->bindString(htmlspecialchars($repo));
			$stm->execute();
			$id = $this->DB->getInsertId();
			$stm->close();
			}

		$this->repos[$repo] = $id;

		return $id;
		}
	}

private function removeDeletedPackages($repo, $arch, $packages)
	{
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages WRITE,
			pkgdb.conflicts WRITE,
			pkgdb.depends WRITE,
			pkgdb.provides WRITE,
			pkgdb.replaces WRITE,
			pkgdb.files WRITE,
			pkgdb.package_group WRITE,
			pkgdb.package_license WRITE,
			pkgdb.architectures READ,
			pkgdb.repositories READ
		');

	$delstm1 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.packages
		WHERE
			id = ?
		');

	$delstm2 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.conflicts
		WHERE
			package = ?
			OR conflicts = ?
		');

	$delstm3 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.depends
		WHERE
			package = ?
			OR depends = ?
		');

	$delstm4 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.provides
		WHERE
			package = ?
			OR provides = ?
		');

	$delstm5 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.replaces
		WHERE
			package = ?
			OR replaces = ?
		');

	$delstm6 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.files
		WHERE
			package = ?
		');

	$delstm7 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.package_group
		WHERE
			package = ?
		');

	$delstm8 = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.package_license
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		SELECT
			pkgdb.packages.id,
			pkgdb.packages.name
		FROM
			pkgdb.packages,
			pkgdb.architectures,
			pkgdb.repositories
		WHERE
			pkgdb.packages.arch = pkgdb.architectures.id
			AND pkgdb.packages.repository = pkgdb.repositories.id
			AND pkgdb.architectures.name = ?
			AND pkgdb.repositories.name = ?
		');
	$stm->bindString(htmlspecialchars($arch));
	$stm->bindString(htmlspecialchars($repo));

	try
		{
		foreach ($stm->getRowSet() as $pkg)
			{
			if (!in_array($pkg['name'], $packages))
				{
				$delstm1->bindInteger($pkg['id']);
				$delstm1->execute();

				$delstm2->bindInteger($pkg['id']);
				$delstm2->bindInteger($pkg['id']);
				$delstm2->execute();

				$delstm3->bindInteger($pkg['id']);
				$delstm3->bindInteger($pkg['id']);
				$delstm3->execute();

				$delstm4->bindInteger($pkg['id']);
				$delstm4->bindInteger($pkg['id']);
				$delstm4->execute();

				$delstm5->bindInteger($pkg['id']);
				$delstm5->bindInteger($pkg['id']);
				$delstm5->execute();

				$delstm6->bindInteger($pkg['id']);
				$delstm6->execute();

				$delstm7->bindInteger($pkg['id']);
				$delstm7->execute();

				$delstm8->bindInteger($pkg['id']);
				$delstm8->execute();
				}
			}
		}
	catch (DBNoDataException $e)
		{
		}

	$delstm1->close();
	$delstm2->close();
	$delstm3->close();
	$delstm4->close();
	$delstm5->close();
	$delstm6->close();
	$delstm7->close();
	$delstm8->close();
	$stm->close();

	$this->DB->execute('UNLOCK TABLES');
	}

private function removeUnusedEntries()
	{
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages WRITE,
			pkgdb.package_group WRITE,
			pkgdb.package_license WRITE,
			pkgdb.packagers WRITE,
			pkgdb.groups WRITE,
			pkgdb.licenses WRITE,
			pkgdb.repositories WRITE,
			pkgdb.architectures WRITE
		');

	$this->DB->execute
		('
		DELETE FROM
			pkgdb.groups
		WHERE
			id NOT IN (SELECT pkgdb.package_group.group FROM pkgdb.package_group)
		');

	$this->DB->execute
		('
		DELETE FROM
			pkgdb.licenses
		WHERE
			id NOT IN (SELECT license FROM pkgdb.package_license)
		');

	$this->DB->execute
		('
		DELETE FROM
			pkgdb.packagers
		WHERE
			id NOT IN (SELECT packager FROM pkgdb.packages)
		');

	$this->DB->execute
		('
		DELETE FROM
			pkgdb.architectures
		WHERE
			id NOT IN (SELECT arch FROM pkgdb.packages)
		');

	$this->DB->execute
		('
		DELETE FROM
			pkgdb.repositories
		WHERE
			id NOT IN (SELECT repository FROM pkgdb.packages)
		');

	$this->DB->execute('UNLOCK TABLES');
	}

private function updateDependencies()
	{
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages READ,
			pkgdb.packages AS source READ,
			pkgdb.packages AS target READ,
			pkgdb.packages AS source2 READ,
			pkgdb.packages AS target2 READ,
			pkgdb.repositories READ,
			pkgdb.depends WRITE
		');

	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				depends
			FROM
				pkgdb.temp_depends
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.depends
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.depends
		SET
			package = ?,
			depends = ?,
			comment = ?
		');

	$stm2 = $this->DB->prepare
		('
		(
		SELECT
			target.id
		FROM
			pkgdb.packages AS source,
			pkgdb.packages AS target
		WHERE
			target.name = ?
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = ?
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			pkgdb.packages AS source2,
			pkgdb.packages AS target2
		WHERE
			target2.name = ?
			AND target2.arch = source2.arch
			AND source2.id = ?
		)
		LIMIT 1
		');

	foreach ($packages as $package)
		{
		$cleanSTM->bindInteger($package['package']);
		$cleanSTM->execute();

		foreach (explode(' ', $package['depends']) as $depends)
			{
			$depends = trim($depends);
			if (empty($depends))
				{
				continue;
				}

			if (preg_match('/([<>=]+.*)/', $depends, $matches))
				{
				$depname = preg_replace('/([<>=]+.*)/', '', $depends);
				$depcomment = $matches[1];
				}
			else
				{
				$depname = $depends;
				$depcomment = '';
				}

			try
				{
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$depid = $stm2->getColumn();

				$stm->bindInteger($package['package']);
				$stm->bindInteger($depid);
				$stm->bindString(htmlspecialchars($depcomment));
				}
			catch (DBNoDataException $e)
				{
				$stm->bindInteger($package['package']);
				$stm->bindInteger(0);
				$stm->bindString(htmlspecialchars($depends));
				}
			$stm->execute();
			}
		}

	$cleanSTM->close();
	$stm2->close();
	$stm->close();

	$this->DB->execute('UNLOCK TABLES');
	}

private function updateProvides()
	{
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages READ,
			pkgdb.packages AS source READ,
			pkgdb.packages AS target READ,
			pkgdb.packages AS source2 READ,
			pkgdb.packages AS target2 READ,
			pkgdb.repositories READ,
			pkgdb.provides WRITE
		');

	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				provides
			FROM
				pkgdb.temp_provides
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.provides
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.provides
		SET
			package = ?,
			provides = ?,
			comment = ?
		');

	$stm2 = $this->DB->prepare
		('
		(
		SELECT
			target.id
		FROM
			pkgdb.packages AS source,
			pkgdb.packages AS target
		WHERE
			target.name = ?
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = ?
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			pkgdb.packages AS source2,
			pkgdb.packages AS target2
		WHERE
			target2.name = ?
			AND target2.arch = source2.arch
			AND source2.id = ?
		)
		LIMIT 1
		');

	foreach ($packages as $package)
		{
		$cleanSTM->bindInteger($package['package']);
		$cleanSTM->execute();

		foreach (explode(' ', $package['provides']) as $provides)
			{
			$provides = trim($provides);
			if (empty($provides))
				{
				continue;
				}

			if (preg_match('/([<>=]+.*)/', $provides, $matches))
				{
				$depname = preg_replace('/([<>=]+.*)/', '', $provides);
				$depcomment = $matches[1];
				}
			else
				{
				$depname = $provides;
				$depcomment = '';
				}

			try
				{
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$depid = $stm2->getColumn();

				$stm->bindInteger($package['package']);
				$stm->bindInteger($depid);
				$stm->bindString(htmlspecialchars($depcomment));
				}
			catch (DBNoDataException $e)
				{
				$stm->bindInteger($package['package']);
				$stm->bindInteger(0);
				$stm->bindString(htmlspecialchars($provides));
				}
			$stm->execute();
			}
		}

	$cleanSTM->close();
	$stm2->close();
	$stm->close();

	$this->DB->execute('UNLOCK TABLES');
	}

private function updateConflicts()
	{
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages READ,
			pkgdb.packages AS source READ,
			pkgdb.packages AS target READ,
			pkgdb.packages AS source2 READ,
			pkgdb.packages AS target2 READ,
			pkgdb.repositories READ,
			pkgdb.conflicts WRITE
		');

	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				conflicts
			FROM
				pkgdb.temp_conflicts
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.conflicts
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.conflicts
		SET
			package = ?,
			conflicts = ?,
			comment = ?
		');

	$stm2 = $this->DB->prepare
		('
		(
		SELECT
			target.id
		FROM
			pkgdb.packages AS source,
			pkgdb.packages AS target
		WHERE
			target.name = ?
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = ?
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			pkgdb.packages AS source2,
			pkgdb.packages AS target2
		WHERE
			target2.name = ?
			AND target2.arch = source2.arch
			AND source2.id = ?
		)
		LIMIT 1
		');

	foreach ($packages as $package)
		{
		$cleanSTM->bindInteger($package['package']);
		$cleanSTM->execute();

		foreach (explode(' ', $package['conflicts']) as $depends)
			{
			$depends = trim($depends);
			if (empty($depends))
				{
				continue;
				}

			if (preg_match('/([<>=]+.*)/', $depends, $matches))
				{
				$depname = preg_replace('/([<>=]+.*)/', '', $depends);
				$depcomment = $matches[1];
				}
			else
				{
				$depname = $depends;
				$depcomment = '';
				}

			try
				{
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$depid = $stm2->getColumn();

				$stm->bindInteger($package['package']);
				$stm->bindInteger($depid);
				$stm->bindString(htmlspecialchars($depcomment));
				}
			catch (DBNoDataException $e)
				{
				$stm->bindInteger($package['package']);
				$stm->bindInteger(0);
				$stm->bindString(htmlspecialchars($depends));
				}
			$stm->execute();
			}
		}

	$cleanSTM->close();
	$stm2->close();
	$stm->close();

	$this->DB->execute('UNLOCK TABLES');
	}

private function updateReplaces()
	{
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages READ,
			pkgdb.packages AS source READ,
			pkgdb.packages AS target READ,
			pkgdb.packages AS source2 READ,
			pkgdb.packages AS target2 READ,
			pkgdb.repositories READ,
			pkgdb.replaces WRITE
		');

	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				replaces
			FROM
				pkgdb.temp_replaces
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.replaces
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.replaces
		SET
			package = ?,
			replaces = ?,
			comment = ?
		');

	$stm2 = $this->DB->prepare
		('
		(
		SELECT
			target.id
		FROM
			pkgdb.packages AS source,
			pkgdb.packages AS target
		WHERE
			target.name = ?
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = ?
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			pkgdb.packages AS source2,
			pkgdb.packages AS target2
		WHERE
			target2.name = ?
			AND target2.arch = source2.arch
			AND source2.id = ?
		)
		LIMIT 1
		');

	foreach ($packages as $package)
		{
		$cleanSTM->bindInteger($package['package']);
		$cleanSTM->execute();

		foreach (explode(' ', $package['replaces']) as $depends)
			{
			$depends = trim($depends);
			if (empty($depends))
				{
				continue;
				}

			if (preg_match('/([<>=]+.*)/', $depends, $matches))
				{
				$depname = preg_replace('/([<>=]+.*)/', '', $depends);
				$depcomment = $matches[1];
				}
			else
				{
				$depname = $depends;
				$depcomment = '';
				}

			try
				{
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$stm2->bindString(htmlspecialchars($depname));
				$stm2->bindInteger($package['package']);
				$depid = $stm2->getColumn();

				$stm->bindInteger($package['package']);
				$stm->bindInteger($depid);
				$stm->bindString(htmlspecialchars($depcomment));
				}
			catch (DBNoDataException $e)
				{
				$stm->bindInteger($package['package']);
				$stm->bindInteger(0);
				$stm->bindString(htmlspecialchars($depends));
				}
			$stm->execute();
			}
		}

	$cleanSTM->close();
	$stm2->close();
	$stm->close();

	$this->DB->execute('UNLOCK TABLES');
	}


}

$upd = new UpdatePKGDB();
$upd->runUpdate();

?>