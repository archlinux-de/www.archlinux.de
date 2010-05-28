#!/usr/bin/php
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

ini_set('max_execution_time', 0);

require ('modules/Functions.php');
require ('modules/Modul.php');
require ('modules/Settings.php');
require ('modules/Exceptions.php');
require ('PackageDB.php');
require ('pages/abstract/Page.php');
require ('pages/ArchitectureDifferences.php');
require ('pages/PackageStatistics.php');


class UpdatePKGDB extends Modul {

/** Cache for id-name mapping */
private $arches 	= array();
private $packagers 	= array();
private $repos 		= array();
private $groups 	= array();
private $licenses 	= array();

private $changed	= false;


private function getTmpDir()
	{
	$tmp = ini_get('upload_tmp_dir');
	return empty($tmp) ? '/tmp' : $tmp;
	}

private function getLockFile()
	{
	return $this->getTmpDir().'/updateRunning.lock';
	}

public function runUpdate()
	{
	if (file_exists($this->getLockFile()))
		{
		die('update still in progress');
		}
	else
		{
		touch($this->getLockFile());
		chmod($this->getLockFile(), 0600);
		}

	$this->DB->connect(
		$this->Settings->getValue('sql_host'),
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database'));

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			temp_depends
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			depends LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			temp_optdepends
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			optdepends LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			temp_provides
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			provides LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			temp_conflicts
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			conflicts LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	$this->DB->execute
		('
		CREATE TEMPORARY TABLE
			temp_replaces
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			replaces LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');

	foreach ($this->Settings->getValue('pkgdb_repositories') as $repo)
		{
		foreach ($this->Settings->getValue('pkgdb_architectures') as $arch)
			{
			$this->updateRepository($repo, $arch);
			}
		}

	if ($this->changed)
		{
		$this->updateDependencies();
		$this->updateOptionalDependencies();
		$this->updateProvides();
		$this->updateConflicts();
		$this->updateReplaces();
		$this->removeUnusedEntries();

		foreach ($this->Settings->getValue('locales') as $locale)
			{
			$this->L10n->setLocale($locale);
			ArchitectureDifferences::updateDBCache();
			PackageStatistics::updateDBCache();
			}
		}

	unlink($this->getLockFile());
	}

private function getRecentDate($repo, $arch)
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				MAX(mtime)
			FROM
				packages
			WHERE
				arch = ?
				AND repository = ?
			');
		$stm->bindInteger($this->getArchitectureID($arch));
		$stm->bindInteger($this->getRepositoryID($repo));
		$date = $stm->getColumn();
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$date = 0;
		$stm->close();
		}

	return $date;
	}

private function updateRepository($repo, $arch)
	{
	$lastpkgdbmtime = $this->getPKGDBMTime($repo, $arch);

	$pkgdb = new PackageDB($this->Settings->getValue('pkgdb_mirror'), $repo, $arch, $lastpkgdbmtime);
	$mtime = $pkgdb->getMTime();
	if ($mtime > $lastpkgdbmtime)
		{
		$this->changed = true;
		$lastrun = $this->getRecentDate($repo, $arch);
		$this->updatePackages($pkgdb->getUpdatedPackages($lastrun), $repo, $arch);
		$this->setPKGDBMTime($repo, $arch, $mtime);

		$this->removeDeletedPackages($repo, $arch, $pkgdb->getPackageNames());
		}
	}

private function setPKGDBMTime($repo, $arch, $time)
	{
	if ($time > 0)
		{
		$stm = $this->DB->prepare
			('
			REPLACE INTO
				log
			SET
				name = ?,
				time = ?
			');
		$stm->bindString('UpdatePKGDB-'.$repo.'-'.$arch);
		$stm->bindInteger($time);
		$stm->execute();
		$stm->close();
		}
	}

private function getPKGDBMTime($repo, $arch)
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				time
			FROM
				log
			WHERE
				name = ?
			');
		$stm->bindString('UpdatePKGDB-'.$repo.'-'.$arch);
		$time = $stm->GetColumn();
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		$time = 0;
		}

	return $time;
	}

private function updatePackages($packages, $repo, $arch)
	{
	$testSTM = $this->DB->prepare
		('
		SELECT
			packages.id
		FROM
			packages,
			repositories,
			architectures
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
			packages
		SET
			filename = ?,
			name = ?,
			base = ?,
			`version` = ?,
			`desc` = ?,
			csize = ?,
			isize = ?,
			md5sum = ?,
			url = ?,
			arch = ?,
			builddate = ?,
			mtime = ?,
			packager = ?,
			`force` = ?,
			repository = ?
		WHERE
			id = ?
		');

	$insertSTM = $this->DB->prepare
		('
		INSERT INTO
			packages
		SET
			filename = ?,
			name = ?,
			base = ?,
			`version` = ?,
			`desc` = ?,
			csize = ?,
			isize = ?,
			md5sum = ?,
			url = ?,
			arch = ?,
			builddate = ?,
			mtime = ?,
			packager = ?,
			`force` = ?,
			repository = ?
		');

	$dependsSTM = $this->DB->prepare
		('
		INSERT INTO
			temp_depends
		SET
			package = ?,
			depends = ?
		');

	$optdependsSTM = $this->DB->prepare
		('
		INSERT INTO
			temp_optdepends
		SET
			package = ?,
			optdepends = ?
		');

	$providesSTM = $this->DB->prepare
		('
		INSERT INTO
			temp_provides
		SET
			package = ?,
			provides = ?
		');

	$conflictsSTM = $this->DB->prepare
		('
		INSERT INTO
			temp_conflicts
		SET
			package = ?,
			conflicts = ?
		');

	$replacesSTM = $this->DB->prepare
		('
		INSERT INTO
			temp_replaces
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
			$updateSTM->bindString(htmlspecialchars($package->getBase()));
			$updateSTM->bindString(htmlspecialchars($package->getVersion()));
			$updateSTM->bindString(htmlspecialchars($package->getDescription()));
			$updateSTM->bindInteger($package->getCompressedSize());
			$updateSTM->bindInteger($package->getInstalledSize());
			$updateSTM->bindString(htmlspecialchars($package->getMD5SUM()));
			$updateSTM->bindString(htmlspecialchars($package->getURL()));
			$updateSTM->bindInteger($this->getArchitectureID($arch));
			$updateSTM->bindInteger($package->getBuildDate());
			$updateSTM->bindInteger($package->getMTime());
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
			$insertSTM->bindString(htmlspecialchars($package->getBase()));
			$insertSTM->bindString(htmlspecialchars($package->getVersion()));
			$insertSTM->bindString(htmlspecialchars($package->getDescription()));
			$insertSTM->bindInteger($package->getCompressedSize());
			$insertSTM->bindInteger($package->getInstalledSize());
			$insertSTM->bindString(htmlspecialchars($package->getMD5SUM()));
			$insertSTM->bindString(htmlspecialchars($package->getURL()));
			$insertSTM->bindInteger($this->getArchitectureID($arch));
			$insertSTM->bindInteger($package->getBuildDate());
			$insertSTM->bindInteger($package->getMTime());
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
			$dependsSTM->bindString(implode("\n", $package->getDepends()));
			$dependsSTM->execute();
			}

		// optdepends
		if (count($package->getOptDepends()) > 0)
			{
			$optdependsSTM->bindInteger($packageID);
			$optdependsSTM->bindString(implode("\n", $package->getOptDepends()));
			$optdependsSTM->execute();
			}

		// provides
		if (count($package->getProvides()) > 0)
			{
			$providesSTM->bindInteger($packageID);
			$providesSTM->bindString(implode("\n", $package->getProvides()));
			$providesSTM->execute();
			}

		// conflicts
		if (count($package->getConflicts()) > 0)
			{
			$conflictsSTM->bindInteger($packageID);
			$conflictsSTM->bindString(implode("\n", $package->getConflicts()));
			$conflictsSTM->execute();
			}

		// replaces
		if (count($package->getReplaces()) > 0)
			{
			$replacesSTM->bindInteger($packageID);
			$replacesSTM->bindString(implode("\n", $package->getReplaces()));
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
	$optdependsSTM->close();
	$providesSTM->close();
	$conflictsSTM->close();
	$replacesSTM->close();
	$testSTM->close();
	$updateSTM->close();
	$insertSTM->close();
	}

private function addPackageToGroups($package, $groups)
	{
	$stm = $this->DB->prepare
		('
		DELETE FROM
			package_group
		WHERE
			package = ?
		');
	$stm->bindInteger($package);
	$stm->execute();
	$stm->close();

	$stm = $this->DB->prepare
		('
		INSERT INTO
			package_group
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
					groups
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
					groups
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
			package_license
		WHERE
			package = ?
		');
	$stm->bindInteger($package);
	$stm->execute();
	$stm->close();

	$stm = $this->DB->prepare
		('
		INSERT INTO
			package_license
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
					licenses
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
					licenses
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
					architectures
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
					architectures
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
					packagers
				WHERE
					name = ?
					AND email = ?
				');
			$stm->bindString(htmlspecialchars(trim($name)));
			$stm->bindString(htmlspecialchars(trim($email)));
			$id = $stm->getColumn();
			$stm->close();
			}
		catch (DBNoDataException $e)
			{
			$stm->close();

			$stm = $this->DB->prepare
				('
				INSERT INTO
					packagers
				SET
					name = ?,
					email = ?
				');
			$stm->bindString(htmlspecialchars(trim($name)));
			$stm->bindString(htmlspecialchars(trim($email)));
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
					repositories
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
					repositories
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
	// $packages is empty if there are no new packages!
	if (count($packages) > 0)
		{
		$delstm1 = $this->DB->prepare
			('
			DELETE FROM
				packages
			WHERE
				id = ?
			');

		$delstm2 = $this->DB->prepare
			('
			DELETE FROM
				conflicts
			WHERE
				package = ?
				OR conflicts = ?
			');

		$delstm3 = $this->DB->prepare
			('
			DELETE FROM
				depends
			WHERE
				package = ?
				OR depends = ?
			');

		$delstm3a = $this->DB->prepare
			('
			DELETE FROM
				optdepends
			WHERE
				package = ?
				OR optdepends = ?
			');

		$delstm4 = $this->DB->prepare
			('
			DELETE FROM
				provides
			WHERE
				package = ?
				OR provides = ?
			');

		$delstm5 = $this->DB->prepare
			('
			DELETE FROM
				replaces
			WHERE
				package = ?
				OR replaces = ?
			');

		$delstm6 = $this->DB->prepare
			('
			DELETE FROM
				files
			WHERE
				package = ?
			');

		$delstm6b = $this->DB->prepare
			('
			DELETE FROM
				package_file_index
			WHERE
				package = ?
			');

		$delstm7 = $this->DB->prepare
			('
			DELETE FROM
				package_group
			WHERE
				package = ?
			');

		$delstm8 = $this->DB->prepare
			('
			DELETE FROM
				package_license
			WHERE
				package = ?
			');

		$stm = $this->DB->prepare
			('
			SELECT
				packages.id,
				packages.name
			FROM
				packages,
				architectures,
				repositories
			WHERE
				packages.arch = architectures.id
				AND packages.repository = repositories.id
				AND architectures.name = ?
				AND repositories.name = ?
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

					$delstm3a->bindInteger($pkg['id']);
					$delstm3a->bindInteger($pkg['id']);
					$delstm3a->execute();

					$delstm4->bindInteger($pkg['id']);
					$delstm4->bindInteger($pkg['id']);
					$delstm4->execute();

					$delstm5->bindInteger($pkg['id']);
					$delstm5->bindInteger($pkg['id']);
					$delstm5->execute();

					$delstm6->bindInteger($pkg['id']);
					$delstm6->execute();

					$delstm6b->bindInteger($pkg['id']);
					$delstm6b->execute();

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
		$delstm6b->close();
		$delstm7->close();
		$delstm8->close();
		$stm->close();
		}
	}

private function removeUnusedEntries()
	{
	$this->DB->execute
		('
		DELETE FROM
			groups
		WHERE
			id NOT IN (SELECT package_group.group FROM package_group)
		');

	$this->DB->execute
		('
		DELETE FROM
			licenses
		WHERE
			id NOT IN (SELECT license FROM package_license)
		');

	$this->DB->execute
		('
		DELETE FROM
			packagers
		WHERE
			id NOT IN (SELECT packager FROM packages)
		');

	$this->DB->execute
		('
		DELETE FROM
			architectures
		WHERE
			id NOT IN (SELECT arch FROM packages)
		');

	$this->DB->execute
		('
		DELETE FROM
			repositories
		WHERE
			id NOT IN (SELECT repository FROM packages)
		');
	}

private function updateDependencies()
	{
	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				depends
			FROM
				temp_depends
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			depends
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			depends
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
			packages AS source,
			packages AS target
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
			packages AS source2,
			packages AS target2
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

		foreach (explode("\n", $package['depends']) as $depends)
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
	}

private function updateOptionalDependencies()
	{
	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				optdepends
			FROM
				temp_optdepends
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			optdepends
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			optdepends
		SET
			package = ?,
			optdepends = ?,
			comment = ?
		');

	$stm2 = $this->DB->prepare
		('
		(
		SELECT
			target.id
		FROM
			packages AS source,
			packages AS target
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
			packages AS source2,
			packages AS target2
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

		foreach (explode("\n", $package['optdepends']) as $optdepends)
			{
			$optdepends = trim($optdepends);
			if (empty($optdepends))
				{
				continue;
				}

			if (preg_match('/(?::+(.*))/', $optdepends, $matches))
				{
				$optdepname = preg_replace('/\s*(:+.*)/', '', $optdepends);
				$optdepcomment = $matches[1];
				}
			else
				{
				$optdepname = $optdepends;
				$optdepcomment = '';
				}

			try
				{
				$stm2->bindString(htmlspecialchars($optdepname));
				$stm2->bindInteger($package['package']);
				$stm2->bindString(htmlspecialchars($optdepname));
				$stm2->bindInteger($package['package']);
				$optdepid = $stm2->getColumn();

				$stm->bindInteger($package['package']);
				$stm->bindInteger($optdepid);
				$stm->bindString(htmlspecialchars($optdepcomment));
				}
			catch (DBNoDataException $e)
				{
				$stm->bindInteger($package['package']);
				$stm->bindInteger(0);
				$stm->bindString(htmlspecialchars($optdepends));
				}
			$stm->execute();
			}
		}

	$cleanSTM->close();
	$stm2->close();
	$stm->close();
	}

private function updateProvides()
	{
	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				provides
			FROM
				temp_provides
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			provides
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			provides
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
			packages AS source,
			packages AS target
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
			packages AS source2,
			packages AS target2
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

		foreach (explode("\n", $package['provides']) as $provides)
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
	}

private function updateConflicts()
	{
	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				conflicts
			FROM
				temp_conflicts
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			conflicts
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			conflicts
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
			packages AS source,
			packages AS target
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
			packages AS source2,
			packages AS target2
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

		foreach (explode("\n", $package['conflicts']) as $depends)
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
	}

private function updateReplaces()
	{
	try
		{
		$packages = $this->DB->getRowSet
			('
			SELECT
				package,
				replaces
			FROM
				temp_replaces
			');
		}
	catch (DBNoDataException $e)
		{
		$packages = array();
		}

	$cleanSTM = $this->DB->prepare
		('
		DELETE FROM
			replaces
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			replaces
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
			packages AS source,
			packages AS target
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
			packages AS source2,
			packages AS target2
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

		foreach (explode("\n", $package['replaces']) as $depends)
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
	}


}

$upd = new UpdatePKGDB();
$upd->runUpdate();

?>
