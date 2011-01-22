#!/usr/bin/php
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

ini_set('max_execution_time', 0);
ini_set('include_path', ini_get('include_path') . ':../');
require ('modules/Modul.php');
require ('modules/Settings.php');
require ('modules/Exceptions.php');
require ('modules/DB.php');
require ('PackageDB.php');

class UpdatePKGDB extends Modul {
	// Cache for id-name mapping
	private $arches = array();
	private $packagers = array();
	private $repos = array();
	private $groups = array();
	private $licenses = array();
	private $changed = false;

	private function getTmpDir() {
		$tmp = ini_get('upload_tmp_dir');
		return empty($tmp) ? '/tmp' : $tmp;
	}

	private function getLockFile() {
		return $this->getTmpDir() . '/updateRunning.lock';
	}

	public function runUpdate() {
		if (file_exists($this->getLockFile())) {
			die('update still in progress');
		} else {
			touch($this->getLockFile());
			chmod($this->getLockFile() , 0600);
		}
		DB::query('
		CREATE TEMPORARY TABLE
			temp_depends
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			depends LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');
		DB::query('
		CREATE TEMPORARY TABLE
			temp_optdepends
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			optdepends LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');
		DB::query('
		CREATE TEMPORARY TABLE
			temp_provides
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			provides LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');
		DB::query('
		CREATE TEMPORARY TABLE
			temp_conflicts
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			conflicts LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');
		DB::query('
		CREATE TEMPORARY TABLE
			temp_replaces
			(
			package INT( 11 ) UNSIGNED NOT NULL,
			replaces LONGTEXT NOT NULL,
			PRIMARY KEY (package)
			)
		');
		foreach ($this->Settings->getValue('pkgdb_repositories') as $repo) {
			foreach ($this->Settings->getValue('pkgdb_architectures') as $arch) {
				$this->updateRepository($repo, $arch);
			}
		}
		if ($this->changed) {
			$this->updateDependencies();
			$this->updateOptionalDependencies();
			$this->updateProvides();
			$this->updateConflicts();
			$this->updateReplaces();
			$this->removeUnusedEntries();
		}
		unlink($this->getLockFile());
	}

	private function getRecentDate($repo, $arch) {
		$stm = DB::prepare('
		SELECT
			MAX(mtime)
		FROM
			packages
		WHERE
			arch = :arch
			AND repository = :repository
		');
		$stm->bindValue('arch', $this->getArchitectureID($arch), PDO::PARAM_INT);
		$stm->bindValue('repository', $this->getRepositoryID($repo), PDO::PARAM_INT);
		$stm->execute();
		return $stm->fetchColumn() ?: 0;
	}

	private function updateRepository($repo, $arch) {
		$lastpkgdbmtime = $this->getPKGDBMTime($repo, $arch);
		$pkgdb = new PackageDB($this->Settings->getValue('pkgdb_mirror') , $repo, $arch, $lastpkgdbmtime);
		$mtime = $pkgdb->getMTime();
		if ($mtime > $lastpkgdbmtime) {
			$this->changed = true;
			$lastrun = $this->getRecentDate($repo, $arch);
			$this->updatePackages($pkgdb->getUpdatedPackages($lastrun) , $repo, $arch);
			$this->setPKGDBMTime($repo, $arch, $mtime);
			$this->removeDeletedPackages($repo, $arch, $pkgdb->getPackageNames());
		}
	}

	private function setPKGDBMTime($repo, $arch, $time) {
		if ($time > 0) {
			$stm = DB::prepare('
			REPLACE INTO
				log
			SET
				name = :name,
				time = :time
			');
			$stm->bindValue('name', 'UpdatePKGDB-'.$repo.'-'.$arch, PDO::PARAM_STR);
			$stm->bindParam('time', $time, PDO::PARAM_INT);
			$stm->execute();
		}
	}

	private function getPKGDBMTime($repo, $arch) {
		$stm = DB::prepare('
		SELECT
			time
		FROM
			log
		WHERE
			name = :name
		');
		$stm->bindValue('name', 'UpdatePKGDB-'.$repo.'-'.$arch, PDO::PARAM_STR);
		$stm->execute();
		return $stm->fetchColumn() ?: 0;
	}

	private function updatePackages($packages, $repo, $arch) {
		$testSTM = DB::prepare('
		SELECT
			packages.id
		FROM
			packages,
			repositories,
			architectures
		WHERE
			packages.repository = repositories.id
			AND packages.arch = architectures.id
			AND packages.name = :package
			AND repositories.name = :repository
			AND architectures.name = :architecture
		');
		$updateSTM = DB::prepare('
		UPDATE
			packages
		SET
			filename = :filename,
			name = :name,
			base = :base,
			`version` = :version,
			`desc` = :desc,
			csize = :csize,
			isize = :isize,
			md5sum = :md5sum,
			url = :url,
			arch = :arch,
			builddate = :builddate,
			mtime = :mtime,
			packager = :packager,
			`force` = :force,
			epoch = :epoch,
			repository = :repository
		WHERE
			id = :id
		');
		$insertSTM = DB::prepare('
		INSERT INTO
			packages
		SET
			filename = :filename,
			name = :name,
			base = :base,
			`version` = :version,
			`desc` = :desc,
			csize = :csize,
			isize = :isize,
			md5sum = :md5sum,
			url = :url,
			arch = :arch,
			builddate = :builddate,
			mtime = :mtime,
			packager = :packager,
			`force` = :force,
			epoch = :epoch,
			repository = :repository
		');
		$dependsSTM = DB::prepare('
		INSERT INTO
			temp_depends
		SET
			package = :package,
			depends = :depends
		');
		$optdependsSTM = DB::prepare('
		INSERT INTO
			temp_optdepends
		SET
			package = :package,
			optdepends = :optdepends
		');
		$providesSTM = DB::prepare('
		INSERT INTO
			temp_provides
		SET
			package = :package,
			provides = :provides
		');
		$conflictsSTM = DB::prepare('
		INSERT INTO
			temp_conflicts
		SET
			package = :package,
			conflicts = :conflicts
		');
		$replacesSTM = DB::prepare('
		INSERT INTO
			temp_replaces
		SET
			package = :package,
			replaces = :replaces
		');
		foreach ($packages as $package) {
			$testSTM->bindValue('package', htmlspecialchars($package->getName()), PDO::PARAM_STR);
			$testSTM->bindValue('repository', htmlspecialchars($repo), PDO::PARAM_STR);
			$testSTM->bindValue('architecture', htmlspecialchars($arch), PDO::PARAM_STR);
			$testSTM->execute();
			$packageID = $testSTM->fetchColumn();

			if ($packageID !== false) {
				$updateSTM->bindValue('filename', htmlspecialchars($package->getFileName()), PDO::PARAM_STR);
				$updateSTM->bindValue('name', htmlspecialchars($package->getName()), PDO::PARAM_STR);
				$updateSTM->bindValue('base', htmlspecialchars($package->getBase()), PDO::PARAM_STR);
				$updateSTM->bindValue('version', htmlspecialchars($package->getVersion()), PDO::PARAM_STR);
				$updateSTM->bindValue('desc', htmlspecialchars($package->getDescription()), PDO::PARAM_STR);
				$updateSTM->bindValue('csize', $package->getCompressedSize(), PDO::PARAM_INT);
				$updateSTM->bindValue('isize', $package->getInstalledSize(), PDO::PARAM_INT);
				$updateSTM->bindValue('md5sum', htmlspecialchars($package->getMD5SUM()), PDO::PARAM_STR);
				$updateSTM->bindValue('url', htmlspecialchars($package->getURL()), PDO::PARAM_STR);
				$updateSTM->bindValue('arch', $this->getArchitectureID($arch), PDO::PARAM_INT);
				$updateSTM->bindValue('builddate', $package->getBuildDate(), PDO::PARAM_INT);
				$updateSTM->bindValue('mtime', $package->getMTime(), PDO::PARAM_INT);
				$updateSTM->bindValue('packager', $this->getPackagerID($package->getPackager()), PDO::PARAM_INT);
				$updateSTM->bindValue('force', ($package->isForced() ? 1 : 0), PDO::PARAM_INT);
				$updateSTM->bindValue('epoch', $package->getEpoch(), PDO::PARAM_INT);
				$updateSTM->bindValue('repository', $this->getRepositoryID($repo), PDO::PARAM_INT);
				$updateSTM->bindParam('id', $packageID, PDO::PARAM_INT);
				$updateSTM->execute();
			} else {
				$insertSTM->bindValue('filename', htmlspecialchars($package->getFileName()), PDO::PARAM_STR);
				$insertSTM->bindValue('name', htmlspecialchars($package->getName()), PDO::PARAM_STR);
				$insertSTM->bindValue('base', htmlspecialchars($package->getBase()), PDO::PARAM_STR);
				$insertSTM->bindValue('version', htmlspecialchars($package->getVersion()), PDO::PARAM_STR);
				$insertSTM->bindValue('desc', htmlspecialchars($package->getDescription()), PDO::PARAM_STR);
				$insertSTM->bindValue('csize', $package->getCompressedSize(), PDO::PARAM_INT);
				$insertSTM->bindValue('isize', $package->getInstalledSize(), PDO::PARAM_INT);
				$insertSTM->bindValue('md5sum', htmlspecialchars($package->getMD5SUM()), PDO::PARAM_STR);
				$insertSTM->bindValue('url', htmlspecialchars($package->getURL()), PDO::PARAM_STR);
				$insertSTM->bindValue('arch', $this->getArchitectureID($arch), PDO::PARAM_INT);
				$insertSTM->bindValue('builddate', $package->getBuildDate(), PDO::PARAM_INT);
				$insertSTM->bindValue('mtime', $package->getMTime(), PDO::PARAM_INT);
				$insertSTM->bindValue('packager', $this->getPackagerID($package->getPackager()), PDO::PARAM_INT);
				$insertSTM->bindValue('force', ($package->isForced() ? 1 : 0), PDO::PARAM_INT);
				$insertSTM->bindValue('epoch', $package->getEpoch(), PDO::PARAM_INT);
				$insertSTM->bindValue('repository', $this->getRepositoryID($repo), PDO::PARAM_INT);
				$insertSTM->execute();
				$packageID = DB::lastInsertId();
			}
			// depends
			if (count($package->getDepends()) > 0) {
				$dependsSTM->bindParam('package', $packageID, PDO::PARAM_INT);
				$dependsSTM->bindValue('depends', implode("\n", $package->getDepends()), PDO::PARAM_STR);
				$dependsSTM->execute();
			}
			// optdepends
			if (count($package->getOptDepends()) > 0) {
				$optdependsSTM->bindParam('package', $packageID, PDO::PARAM_INT);
				$optdependsSTM->bindValue('optdepends', implode("\n", $package->getOptDepends()), PDO::PARAM_STR);
				$optdependsSTM->execute();
			}
			// provides
			if (count($package->getProvides()) > 0) {
				$providesSTM->bindParam('package', $packageID, PDO::PARAM_INT);
				$providesSTM->bindValue('provides', implode("\n", $package->getProvides()), PDO::PARAM_STR);
				$providesSTM->execute();
			}
			// conflicts
			if (count($package->getConflicts()) > 0) {
				$conflictsSTM->bindParam('package', $packageID, PDO::PARAM_INT);
				$conflictsSTM->bindValue('conflicts', implode("\n", $package->getConflicts()), PDO::PARAM_STR);
				$conflictsSTM->execute();
			}
			// replaces
			if (count($package->getReplaces()) > 0) {
				$replacesSTM->bindParam('package', $packageID, PDO::PARAM_INT);
				$replacesSTM->bindValue('replaces', implode("\n", $package->getReplaces()), PDO::PARAM_STR);
				$replacesSTM->execute();
			}
			// groups
			if (count($package->getGroups()) > 0) {
				$this->addPackageToGroups($packageID, $package->getGroups());
			}
			// licenses
			if (count($package->getLicenses()) > 0) {
				$this->addPackageToLicenses($packageID, $package->getLicenses());
			}
		}
	}

	private function addPackageToGroups($package, $groups) {
		$stm = DB::prepare('
		DELETE FROM
			package_group
		WHERE
			package = :package
		');
		$stm->bindParam('package', $package, PDO::PARAM_INT);
		$stm->execute();
		$stm = DB::prepare('
		INSERT INTO
			package_group
		SET
			package = :package,
			`group` = :group
		');
		foreach ($groups as $group) {
			$stm->bindParam('package', $package, PDO::PARAM_INT);
			$stm->bindValue('group', $this->getGroupID($group), PDO::PARAM_INT);
			$stm->execute();
		}
	}

	private function getGroupID($group) {
		if (isset($this->groups[$group])) {
			return $this->groups[$group];
		} else {
			$stm = DB::prepare('
			SELECT
				id
			FROM
				groups
			WHERE
				name = :name
			');
			$stm->bindValue('name', htmlspecialchars($group), PDO::PARAM_STR);
			$stm->execute();
			$id = $stm->fetchColumn();
			if ($id === false) {
				$stm = DB::prepare('
				INSERT INTO
					groups
				SET
					name = :name
				');
				$stm->bindValue('name', htmlspecialchars($group), PDO::PARAM_STR);
				$stm->execute();
				$id = DB::lastInsertId();
			}
			$this->groups[$group] = $id;
			return $id;
		}
	}

	private function addPackageToLicenses($package, $licenses) {
		$stm = DB::prepare('
		DELETE FROM
			package_license
		WHERE
			package = :package
		');
		$stm->bindParam('package', $package, PDO::PARAM_INT);
		$stm->execute();
		$stm = DB::prepare('
		INSERT INTO
			package_license
		SET
			package = :package,
			license = :license
		');
		foreach ($licenses as $license) {
			$stm->bindParam('package', $package, PDO::PARAM_INT);
			$stm->bindValue('license', $this->getLicenseID($license), PDO::PARAM_INT);
			$stm->execute();
		}
	}

	private function getLicenseID($license) {
		if (isset($this->licenses[$license])) {
			return $this->licenses[$license];
		} else {
			$stm = DB::prepare('
			SELECT
				id
			FROM
				licenses
			WHERE
				name = :name
			');
			$stm->bindValue('name', htmlspecialchars($license), PDO::PARAM_STR);
			$stm->execute();
			$id = $stm->fetchColumn();
			if ($id === false) {
				$stm = DB::prepare('
				INSERT INTO
					licenses
				SET
					name = :name
				');
				$stm->bindValue('name', htmlspecialchars($license), PDO::PARAM_STR);
				$stm->execute();
				$id = DB::lastInsertId();
			}
			$this->licenses[$license] = $id;
			return $id;
		}
	}

	private function getArchitectureID($arch) {
		if (isset($this->arches[$arch])) {
			return $this->arches[$arch];
		} else {
			$stm = DB::prepare('
			SELECT
				id
			FROM
				architectures
			WHERE
				name = :name
			');
			$stm->bindValue('name', htmlspecialchars($arch), PDO::PARAM_STR);
			$stm->execute();
			$id = $stm->fetchColumn();
			if ($id === false) {
				$stm = DB::prepare('
				INSERT INTO
					architectures
				SET
					name = :name
				');
				$stm->bindValue('name', htmlspecialchars($arch), PDO::PARAM_STR);
				$stm->execute();
				$id = DB::lastInsertId();
			}
			$this->arches[$arch] = $id;
			return $id;
		}
	}

	private function getPackagerID($packager) {
		if (isset($this->packagers[$packager])) {
			return $this->packagers[$packager];
		} else {
			preg_match('/([^<>]+)(?:<(.+?)>)?/', $packager, $matches);
			$name = !empty($matches[1]) ? $matches[1] : $packager;
			$email = isset($matches[2]) ? $matches[2] : '';
			$stm = DB::prepare('
			SELECT
				id
			FROM
				packagers
			WHERE
				name = :name
				AND email = :email
			');
			$stm->bindValue('name', htmlspecialchars(trim($name)), PDO::PARAM_STR);
			$stm->bindValue('email', htmlspecialchars(trim($email)), PDO::PARAM_STR);
			$stm->execute();
			$id = $stm->fetchColumn();
			if ($id === false) {
				$stm = DB::prepare('
				INSERT INTO
					packagers
				SET
					name = :name,
					email = :email
				');
				$stm->bindValue('name', htmlspecialchars(trim($name)), PDO::PARAM_STR);
				$stm->bindValue('email', htmlspecialchars(trim($email)), PDO::PARAM_STR);
				$stm->execute();
				$id = DB::lastInsertId();
			}
			$this->packagers[$packager] = $id;
			return $id;
		}
	}

	private function getRepositoryID($repo) {
		if (isset($this->repos[$repo])) {
			return $this->repos[$repo];
		} else {
			$stm = DB::prepare('
			SELECT
				id
			FROM
				repositories
			WHERE
				name = :name
			');
			$stm->bindValue('name', htmlspecialchars($repo), PDO::PARAM_STR);
			$stm->execute();
			$id = $stm->fetchColumn();
			if ($id === false) {
				$stm = DB::prepare('
				INSERT INTO
					repositories
				SET
					name = :name
				');
				$stm->bindValue('name', htmlspecialchars($repo), PDO::PARAM_STR);
				$stm->execute();
				$id = DB::lastInsertId();
			}
			$this->repos[$repo] = $id;
			return $id;
		}
	}

	private function removeDeletedPackages($repo, $arch, $packages) {
		// $packages is empty if there are no new packages!
		if (is_array($packages)) {
			$delstm1 = DB::prepare('
			DELETE FROM
				packages
			WHERE
				id = :package
			');
			$delstm2 = DB::prepare('
			DELETE FROM
				conflicts
			WHERE
				package = :package
				OR conflicts = :package
			');
			$delstm3 = DB::prepare('
			DELETE FROM
				depends
			WHERE
				package = :package
				OR depends = :package
			');
			$delstm3a = DB::prepare('
			DELETE FROM
				optdepends
			WHERE
				package = :package
				OR optdepends = :package
			');
			$delstm4 = DB::prepare('
			DELETE FROM
				provides
			WHERE
				package = :package
				OR provides = :package
			');
			$delstm5 = DB::prepare('
			DELETE FROM
				replaces
			WHERE
				package = :package
				OR replaces = :package
			');
			$delstm6 = DB::prepare('
			DELETE FROM
				files
			WHERE
				package = :package
			');
			$delstm6b = DB::prepare('
			DELETE FROM
				package_file_index
			WHERE
				package = :package
			');
			$delstm7 = DB::prepare('
			DELETE FROM
				package_group
			WHERE
				package = :package
			');
			$delstm8 = DB::prepare('
			DELETE FROM
				package_license
			WHERE
				package = :package
			');
			$pkgs = DB::prepare('
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
				AND architectures.name = :architecture
				AND repositories.name = :repository
			');
			$pkgs->bindValue('architecture', htmlspecialchars($arch), PDO::PARAM_STR);
			$pkgs->bindValue('repository', htmlspecialchars($repo), PDO::PARAM_STR);
			$pkgs->execute();
			foreach ($pkgs as $pkg) {
				if (!in_array($pkg['name'], $packages)) {
					$delstm1->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm1->execute();
					$delstm2->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm2->execute();
					$delstm3->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm3->execute();
					$delstm3a->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm3a->execute();
					$delstm4->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm4->execute();
					$delstm5->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm5->execute();
					$delstm6->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm6->execute();
					$delstm6b->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm6b->execute();
					$delstm7->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm7->execute();
					$delstm8->bindValue('package', $pkg['id'], PDO::PARAM_INT);
					$delstm8->execute();
				}
			}
		}
	}

	private function removeUnusedEntries() {
		DB::query('
		DELETE FROM
			groups
		WHERE
			id NOT IN (SELECT package_group.group FROM package_group)
		');
		DB::query('
		DELETE FROM
			licenses
		WHERE
			id NOT IN (SELECT license FROM package_license)
		');
		DB::query('
		DELETE FROM
			packagers
		WHERE
			id NOT IN (SELECT packager FROM packages)
		');
		DB::query('
		DELETE FROM
			architectures
		WHERE
			id NOT IN (SELECT arch FROM packages)
		');
		DB::query('
		DELETE FROM
			repositories
		WHERE
			id NOT IN (SELECT repository FROM packages)
		');
	}

	private function updateDependencies() {
		$packages = DB::query('
		SELECT
			package,
			depends
		FROM
			temp_depends
		');
		$cleanSTM = DB::prepare('
		DELETE FROM
			depends
		WHERE
			package = :package
		');
		$stm = DB::prepare('
		INSERT INTO
			depends
		SET
			package = :package,
			depends = :depends,
			comment = :comment
		');
		$stm2 = DB::prepare('
		(
		SELECT
			target.id
		FROM
			packages AS source,
			packages AS target
		WHERE
			target.name = :target
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = :source
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			packages AS source2,
			packages AS target2
		WHERE
			target2.name = :target
			AND target2.arch = source2.arch
			AND source2.id = :source
		)
		LIMIT 1
		');
		foreach ($packages as $package) {
			$cleanSTM->bindValue('package', $package['package'], PDO::PARAM_INT);
			$cleanSTM->execute();
			foreach (explode("\n", $package['depends']) as $depends) {
				$depends = trim($depends);
				if (empty($depends)) {
					continue;
				}
				if (preg_match('/([<>=]+.*)/', $depends, $matches)) {
					$depname = preg_replace('/([<>=]+.*)/', '', $depends);
					$depcomment = $matches[1];
				} else {
					$depname = $depends;
					$depcomment = '';
				}
				$stm2->bindValue('target', htmlspecialchars($depname), PDO::PARAM_STR);
				$stm2->bindParam('source', $package['package'], PDO::PARAM_INT);
				$stm2->execute();
				$depid = $stm2->fetchColumn() ?: 0;
				$stm->bindParam('package', $package['package'], PDO::PARAM_INT);
				$stm->bindParam('depends', $depid, PDO::PARAM_INT);
				$stm->bindValue('comment', htmlspecialchars($depcomment), PDO::PARAM_STR);
				$stm->execute();
			}
		}
	}

	private function updateOptionalDependencies() {
		$packages = DB::query('
		SELECT
			package,
			optdepends
		FROM
			temp_optdepends
		');
		$cleanSTM = DB::prepare('
		DELETE FROM
			optdepends
		WHERE
			package = :package
		');
		$stm = DB::prepare('
		INSERT INTO
			optdepends
		SET
			package = :package,
			optdepends = :optdepends,
			comment = :comment
		');
		$stm2 = DB::prepare('
		(
		SELECT
			target.id
		FROM
			packages AS source,
			packages AS target
		WHERE
			target.name = :target
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = :source
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			packages AS source2,
			packages AS target2
		WHERE
			target2.name = :target
			AND target2.arch = source2.arch
			AND source2.id = :source
		)
		LIMIT 1
		');
		foreach ($packages as $package) {
			$cleanSTM->bindParam('package', $package['package'], PDO::PARAM_INT);
			$cleanSTM->execute();
			foreach (explode("\n", $package['optdepends']) as $optdepends) {
				$optdepends = trim($optdepends);
				if (empty($optdepends)) {
					continue;
				}
				if (preg_match('/(?::+(.*))/', $optdepends, $matches)) {
					$optdepname = preg_replace('/\s*(:+.*)/', '', $optdepends);
					$optdepcomment = $matches[1];
				} else {
					$optdepname = $optdepends;
					$optdepcomment = '';
				}
				$stm2->bindValue('target', htmlspecialchars($optdepname), PDO::PARAM_STR);
				$stm2->bindValue('source', $package['package'], PDO::PARAM_INT);
				$stm2->execute();
				$optdepid = $stm2->fetchColumn() ?: 0;
				$stm->bindValue('package', $package['package'], PDO::PARAM_INT);
				$stm->bindValue('optdepends', $optdepid, PDO::PARAM_INT);
				$stm->bindValue('comment', htmlspecialchars($optdepcomment), PDO::PARAM_STR);
				$stm->execute();
			}
		}
	}

	private function updateProvides() {
		$packages = DB::query('
		SELECT
			package,
			provides
		FROM
			temp_provides
		');
		$cleanSTM = DB::prepare('
		DELETE FROM
			provides
		WHERE
			package = :package
		');
		$stm = DB::prepare('
		INSERT INTO
			provides
		SET
			package = :package,
			provides = :provides,
			comment = :comment
		');
		$stm2 = DB::prepare('
		(
		SELECT
			target.id
		FROM
			packages AS source,
			packages AS target
		WHERE
			target.name = :target
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = :source
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			packages AS source2,
			packages AS target2
		WHERE
			target2.name = :target
			AND target2.arch = source2.arch
			AND source2.id = :source
		)
		LIMIT 1
		');
		foreach ($packages as $package) {
			$cleanSTM->bindParam('package', $package['package'], PDO::PARAM_INT);
			$cleanSTM->execute();
			foreach (explode("\n", $package['provides']) as $provides) {
				$provides = trim($provides);
				if (empty($provides)) {
					continue;
				}
				if (preg_match('/([<>=]+.*)/', $provides, $matches)) {
					$depname = preg_replace('/([<>=]+.*)/', '', $provides);
					$depcomment = $matches[1];
				} else {
					$depname = $provides;
					$depcomment = '';
				}
				$stm2->bindValue('target', htmlspecialchars($depname), PDO::PARAM_STR);
				$stm2->bindParam('source', $package['package'], PDO::PARAM_INT);
				$stm2->execute();
				$depid = $stm2->fetchColumn() ?: 0;
				$stm->bindParam('package', $package['package'], PDO::PARAM_INT);
				$stm->bindParam('provides', $depid, PDO::PARAM_INT);
				$stm->bindValue('comment', htmlspecialchars($depcomment), PDO::PARAM_STR);
				$stm->execute();
			}
		}
	}

	private function updateConflicts() {
		$packages = DB::query('
		SELECT
			package,
			conflicts
		FROM
			temp_conflicts
		');
		$cleanSTM = DB::prepare('
		DELETE FROM
			conflicts
		WHERE
			package = :package
		');
		$stm = DB::prepare('
		INSERT INTO
			conflicts
		SET
			package = :package,
			conflicts = :conflicts,
			comment = :comment
		');
		$stm2 = DB::prepare('
		(
		SELECT
			target.id
		FROM
			packages AS source,
			packages AS target
		WHERE
			target.name = :target
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = :source
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			packages AS source2,
			packages AS target2
		WHERE
			target2.name = :target
			AND target2.arch = source2.arch
			AND source2.id = :source
		)
		LIMIT 1
		');
		foreach ($packages as $package) {
			$cleanSTM->bindParam('package', $package['package'], PDO::PARAM_INT);
			$cleanSTM->execute();
			foreach (explode("\n", $package['conflicts']) as $depends) {
				$depends = trim($depends);
				if (empty($depends)) {
					continue;
				}
				if (preg_match('/([<>=]+.*)/', $depends, $matches)) {
					$depname = preg_replace('/([<>=]+.*)/', '', $depends);
					$depcomment = $matches[1];
				} else {
					$depname = $depends;
					$depcomment = '';
				}
				$stm2->bindValue('target', htmlspecialchars($depname), PDO::PARAM_STR);
				$stm2->bindParam('source', $package['package'], PDO::PARAM_INT);
				$stm2->execute();
				$depid = $stm2->fetchColumn() ?: 0;
				$stm->bindParam('package', $package['package'], PDO::PARAM_INT);
				$stm->bindParam('conflicts', $depid, PDO::PARAM_INT);
				$stm->bindValue('comment', htmlspecialchars($depcomment), PDO::PARAM_STR);
				$stm->execute();
			}
		}
	}

	private function updateReplaces() {
		$packages = DB::query('
		SELECT
			package,
			replaces
		FROM
			temp_replaces
		');
		$cleanSTM = DB::prepare('
		DELETE FROM
			replaces
		WHERE
			package = :package
		');
		$stm = DB::prepare('
		INSERT INTO
			replaces
		SET
			package = :package,
			replaces = :replaces,
			comment = :comment
		');
		$stm2 = DB::prepare('
		(
		SELECT
			target.id
		FROM
			packages AS source,
			packages AS target
		WHERE
			target.name = :target
			AND target.repository = source.repository
			AND target.arch = source.arch
			AND source.id = :source
		)
		UNION
		(
		SELECT
			target2.id
		FROM
			packages AS source2,
			packages AS target2
		WHERE
			target2.name = :target
			AND target2.arch = source2.arch
			AND source2.id = :source
		)
		LIMIT 1
		');
		foreach ($packages as $package) {
			$cleanSTM->bindParam('package', $package['package'], PDO::PARAM_INT);
			$cleanSTM->execute();
			foreach (explode("\n", $package['replaces']) as $depends) {
				$depends = trim($depends);
				if (empty($depends)) {
					continue;
				}
				if (preg_match('/([<>=]+.*)/', $depends, $matches)) {
					$depname = preg_replace('/([<>=]+.*)/', '', $depends);
					$depcomment = $matches[1];
				} else {
					$depname = $depends;
					$depcomment = '';
				}
				$stm2->bindValue('target', htmlspecialchars($depname), PDO::PARAM_STR);
				$stm2->bindParam('source', $package['package'], PDO::PARAM_INT);
				$stm2->execute();
				$depid = $stm2->fetchColumn() ?: 0;
				$stm->bindParam('package', $package['package'], PDO::PARAM_INT);
				$stm->bindParam('replaces', $depid, PDO::PARAM_INT);
				$stm->bindValue('comment', htmlspecialchars($depcomment), PDO::PARAM_STR);
				$stm->execute();
			}
		}
	}
}

$upd = new UpdatePKGDB();
$upd->runUpdate();

?>
