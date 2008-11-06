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

class PostPackageList extends Page {

private static $delay = 86400;	// 24 hours


public function prepare()
	{
	if ($this->Input->Get->isValid('getScript'))
		{
		$this->getScript();
		}

	try
		{
		$packages = $this->Input->Post->getString('packages');
		$arch = $this->Input->Post->getString('arch');
		}
	catch (RequestException $e)
		{
		$this->showFailure('No data received');
		}

	$this->checkIfAllreadySubmitted();

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
		$stm->bindString($arch);
		$archID = $stm->getColumn();
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		$this->showFailure('Unknown architecture: '.htmlspecialchars($arch));
		}
	catch (DBException $e)
		{
		$this->showFailure('Allan broke it!');
		}

	foreach (explode("\n", $packages) as $package)
		{
		$this->insertPackage($package, $arch, $archID);
		}
	}

protected function showWarning($text)
	{
	echo 'Warning: '.$text."\n";
	}

protected function showFailure($text)
	{
	echo 'Failure: '.$text."\n";
	exit;
	}

public function show()
	{
	echo 'Thanks for your submission. :-)'."\n";
	}

private function checkIfAllreadySubmitted()
	{
	try
		{
		$stm = $this->DB->prepare
			('
			DELETE FROM
				package_statistics_log
			WHERE
				visited < ?
			');
		$stm->bindInteger(time() - self::$delay);
		$stm->execute();
		$stm->close();

		$stm = $this->DB->prepare
			('
			INSERT INTO
				package_statistics_log
			SET
				ip = ?,
				visited = ?
			');
		$stm->bindString($this->Input->Server->getString('REMOTE_ADDR'));
		$stm->bindInteger(time());
		$stm->execute();
		$stm->close();
		}
	catch (DBException $e)
		{
		$this->showFailure('You allready submitted your package list!');
		}
	}

private function insertPackage($package, $arch, $archID)
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				id
			FROM
				packages
			WHERE
				name = ?
				AND arch = ?
			');
		$stm->bindString(htmlspecialchars($package));
		$stm->bindInteger($archID);
		$stm->getColumn();
		$stm->close();

		$stm = $this->DB->prepare
			('
			INSERT INTO
				package_statistics
			SET
				name = ?,
				arch = ?,
				count = 1
			ON DUPLICATE KEY UPDATE
				count = count + 1
			');
		$stm->bindString(htmlspecialchars($package));
		$stm->bindString($arch);
		$stm->execute();
		$stm->close();
		}
	catch (DBNoDataException $e)
		{
		// $this->showWarning('Unknown package: '.$package);
		}
	catch (DBException $e)
		{
		$this->showFailure('Allan broke it!');
		}
	}

private function getScript()
	{
	$script =
'#!/bin/bash

pkglist=$(mktemp --tmpdir pkglist.XXXXXX)
pacman -Qq > ${pkglist}

curl -f -H "Expect: " \
	--data-urlencode "packages@${pkglist}" \
	--data-urlencode "arch=$(uname -m)" \
	"'.$this->Input->getURL().'/?page='.$this->getName().'" \
	|| echo "Sending data failed. Please come back tomorrow!"

rm -f ${pkglist}
';

	header('HTTP/1.1 200 OK');
	header('Content-Type: text/plain; charset=UTF-8');
	header('Content-Length: '.strlen($script));
	header('Content-Disposition: attachement; filename="postPackageList.sh"');
	echo $script;
	exit;
	}

}

?>