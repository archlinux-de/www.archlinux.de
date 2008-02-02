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

class GetUpdates extends Modul implements IOutput {

private function getLockFile()
	{
	return ini_get('session.save_path').'/updateRunning.lock';
	}

public function prepare()
	{
	echo 'Client: Got request...', "\n";
	try
		{
		$sha1sum = $this->Io->getHex('sha1sum');
		$seed = $this->Io->getString('seed');
		$sum = $this->Io->getString('sum');
		}
	catch (IoRequestException $e)
		{
		$this->showFailure('Client: Did not receive all data!');
		}

	if (sha1($seed.$this->Settings->getValue('update_secret')) != $sum)
		{
		$this->showFailure('Client: Authentication failed!');
		}

	if (getenv('REMOTE_ADDR') != gethostbyname($this->Settings->getValue('update_host')))
		{
		$this->showFailure('Client: Connection denied!');
		}

	if (file_exists($this->getLockFile()))
		{
		$this->showFailure('Update allready in progress!');
		}
	else
		{
		touch($this->getLockFile());
		}

	echo 'Client: Fetching updates...';
	ini_set('max_execution_time', 0);

	$tempFile = tempnam(ini_get('upload_tmp_dir').'/', $this->getName());
	$fh = fopen($tempFile, 'w');
	flock($fh, LOCK_EX);
	$curl = curl_init($this->Settings->getValue('update_url'));
	curl_setopt($curl, CURLOPT_FILE, $fh);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_exec($curl);
	curl_close($curl);
	flock($fh, LOCK_UN);
	fclose($fh);
	echo 'done', "\n";

	echo 'Client: Checking updates...';
	if (sha1_file($tempFile) != $sha1sum)
		{
		$this->showFailure('Client: Updates are corrupted!');
		}
	echo 'done', "\n";

	$fh = fopen($tempFile, 'r');
	flock($fh, LOCK_SH);

	echo 'Client: Updating Database...', "\n";
	$this->updatePackages($fh);

	flock($fh, LOCK_UN);
	fclose($fh);
	unlink($tempFile);
	unlink($this->getLockFile());
	}

public function show()
	{
	echo 'Client: All done. Good Bye!', "\n";
	}

private function showFailure($message)
	{
	unlink($this->getLockFile());
	die($message);
	}

private function updatePackages($fh)
	{
	self::__set('DB', new DB(
		$this->Settings->getValue('sql_user'),
		$this->Settings->getValue('sql_password'),
		$this->Settings->getValue('sql_database')
		));

	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages WRITE,
			pkgdb.repositories READ,
			pkgdb.categories READ,
			pkgdb.maintainers READ,
			pkgdb.files WRITE,
			pkgdb.sources WRITE
		');

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

	$testSTM = $this->DB->prepare
		('
		SELECT
			packages.id
		FROM
			pkgdb.packages,
			pkgdb.repositories
		WHERE
			packages.repository = repositories.id
			AND packages.pkgname = ?
			AND repositories.name = ?
		');

	$updateSTM = $this->DB->prepare
		('
		UPDATE
			pkgdb.packages
		SET
			repository = (SELECT id FROM pkgdb.repositories WHERE name = ?),
			category = (SELECT id FROM pkgdb.categories WHERE name = ?),
			maintainer = (SELECT id FROM pkgdb.maintainers WHERE username = ?),
			needupdate = ?,
			pkgname = ?,
			pkgver = ?,
			pkgrel = ?,
			pkgdesc = ?,
			url = ?,
			lastupdate = ?,
			origid = ?
		WHERE
			id = ?
		');

	$insertSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.packages
		SET
			repository = (SELECT id FROM pkgdb.repositories WHERE name = ?),
			category = (SELECT id FROM pkgdb.categories WHERE name = ?),
			maintainer = (SELECT id FROM pkgdb.maintainers WHERE username = ?),
			needupdate = ?,
			pkgname = ?,
			pkgver = ?,
			pkgrel = ?,
			pkgdesc = ?,
			url = ?,
			lastupdate = ?,
			origid = ?
		');

	$fileDeleteSTM = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.files
		WHERE
			package = ?
		');

	$fileInsertSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.files
		SET
			package = ?,
			path = ?,
			file = ?
		');

	$dependsSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.temp_depends
		SET
			package = ?,
			depends = ?
		');

	$sourceDeleteSTM = $this->DB->prepare
		('
		DELETE FROM
			pkgdb.sources
		WHERE
			package = ?
		');

	$sourceInsertSTM = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.sources
		SET
			package = ?,
			url = ?
		');

	echo 'Client: Reading updates...';

	$packageIDList = unserialize(gzuncompress(base64_decode(fgets($fh))));

	while ($line = fgets($fh))
		{
		$package = unserialize(gzuncompress(base64_decode($line)));

		try
			{
			$testSTM->bindString(htmlspecialchars($package['pkgname']));
			$testSTM->bindString(htmlspecialchars($package['repository']));
			$packageID = $testSTM->getColumn();

			$updateSTM->bindString(htmlspecialchars($package['repository']));
			$updateSTM->bindString(htmlspecialchars($package['category']));
			$updateSTM->bindString(htmlspecialchars($package['maintainer']));
			$updateSTM->bindInteger($package['needupdate']);
			$updateSTM->bindString(htmlspecialchars($package['pkgname']));
			$updateSTM->bindString(htmlspecialchars($package['pkgver']));
			$updateSTM->bindString(htmlspecialchars($package['pkgrel']));
			$updateSTM->bindString(htmlspecialchars($package['pkgdesc']));
			$updateSTM->bindString(htmlspecialchars($package['url']));
			$updateSTM->bindInteger($package['lastupdate']);
			$updateSTM->bindInteger($package['id']);
			$updateSTM->bindInteger($packageID);
			$updateSTM->execute();
			}
		catch (DBNoDataException $e)
			{
			$insertSTM->bindString(htmlspecialchars($package['repository']));
			$insertSTM->bindString(htmlspecialchars($package['category']));
			$insertSTM->bindString(htmlspecialchars($package['maintainer']));
			$insertSTM->bindInteger($package['needupdate']);
			$insertSTM->bindString(htmlspecialchars($package['pkgname']));
			$insertSTM->bindString(htmlspecialchars($package['pkgver']));
			$insertSTM->bindString(htmlspecialchars($package['pkgrel']));
			$insertSTM->bindString(htmlspecialchars($package['pkgdesc']));
			$insertSTM->bindString(htmlspecialchars($package['url']));
			$insertSTM->bindInteger($package['lastupdate']);
			$insertSTM->bindInteger($package['id']);

			$insertSTM->execute();
			$packageID = $this->DB->getInsertId();
			}

		// dependencies
		trim($package['depends']);
		if (!empty($package['depends']))
			{
			$dependsSTM->bindInteger($packageID);
			$dependsSTM->bindString($package['depends']);
			$dependsSTM->execute();
			}

		// sources
		$sourceDeleteSTM->bindInteger($packageID);
		$sourceDeleteSTM->execute();
		foreach (explode(' ', $package['sources']) as $source)
			{
			$sourceInsertSTM->bindInteger($packageID);
			$sourceInsertSTM->bindString(htmlspecialchars($source));
			$sourceInsertSTM->execute();
			}

		// files
		$fileDeleteSTM->bindInteger($packageID);
		$fileDeleteSTM->execute();
		foreach ($package['files'] as $file)
			{
			$fileInsertSTM->bindInteger($packageID);
			$fileInsertSTM->bindString(htmlspecialchars($file));
			$fileInsertSTM->bindString(htmlspecialchars(basename($file)));
			$fileInsertSTM->execute();
			}
		}
	echo 'done', "\n";

	$sourceDeleteSTM->close();
	$sourceInsertSTM->close();
	$dependsSTM->close();
	$fileInsertSTM->close();
	$fileDeleteSTM->close();
	$testSTM->close();
	$updateSTM->close();
	$insertSTM->close();
	$this->DB->execute('UNLOCK TABLES ');

	$this->updateDependencies();
	$this->collectGarbage($packageIDList);

// 	$this->DB->execute('OPTIMIZE TABLE pkgdb.dependencies, pkgdb.files, pkgdb.packages, pkgdb.sources');
	echo 'done', "\n";
	}

private function collectGarbage($packageIDList)
	{
	if (count($packageIDList) != $this->DB->getColumn('SELECT COUNT(*) FROM pkgdb.packages'))
		{
		echo 'Client: Removing allready deleted packages...';

		$this->DB->execute
			('
			LOCK TABLES
				pkgdb.packages WRITE,
				pkgdb.files WRITE,
				pkgdb.sources WRITE,
				pkgdb.dependencies WRITE
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
				pkgdb.files
			WHERE
				package = ?
			');

		$delstm3 = $this->DB->prepare
			('
			DELETE FROM
				pkgdb.sources
			WHERE
				package = ?
			');

		$delstm4 = $this->DB->prepare
			('
			DELETE FROM
				pkgdb.dependencies
			WHERE
				package = ?
				OR dependency = ?
			');

		foreach ($this->DB->getRowSet('SELECT id, origid FROM pkgdb.packages') as $pkgid)
			{
			if (!in_array($pkgid['origid'], $packageIDList))
				{
				$delstm1->bindInteger($pkgid['id']);
				$delstm1->execute();

				$delstm2->bindInteger($pkgid['id']);
				$delstm2->execute();

				$delstm3->bindInteger($pkgid['id']);
				$delstm3->execute();

				$delstm4->bindInteger($pkgid['id']);
				$delstm4->bindInteger($pkgid['id']);
				$delstm4->execute();
				}
			}

		$delstm1->close();
		$delstm2->close();
		$delstm3->close();
		$delstm4->close();
		$this->DB->execute('UNLOCK TABLES ');
		echo 'done', "\n";
		}
	}

private function updateDependencies()
	{
	echo 'Client: Updating dependencies...';
	$this->DB->execute
		('
		LOCK TABLES
			pkgdb.packages READ,
			pkgdb.repositories READ,
			pkgdb.dependencies WRITE
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
			pkgdb.dependencies
		WHERE
			package = ?
		');

	$stm = $this->DB->prepare
		('
		INSERT INTO
			pkgdb.dependencies
		SET
			package = ?,
			dependency = ?,
			comment = ?
		');

	$stm2 = $this->DB->prepare
		('
		SELECT
			id
		FROM
			pkgdb.packages
		WHERE
			pkgname = ?
			AND repository <> (SELECT id FROM pkgdb.repositories WHERE name = \'Testing\')
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
				$stm2->bindString($depname);
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

	$this->DB->execute('UNLOCK TABLES ');
	echo 'done', "\n";
	}

}

?>