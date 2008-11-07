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
	try
		{
		$packages = explode("\n", $this->Input->Post->getString('packages'));
		$arch = $this->Input->Post->getString('arch');
		$pkgstatsver = $this->Input->Post->getString('pkgstatsver');
		}
	catch (RequestException $e)
		{
		$this->showFailure('No data received');
		}

	if ($pkgstatsver != '1.0')
		{
		$this->showFailure('Sorry, your version of pkgstats is not supported.');
		}

	$this->checkIfAlreadySubmitted();

	try
		{
		$this->insertLogEntry($arch, count($packages));

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

		foreach ($packages as $package)
			{
			$stm->bindString(htmlspecialchars($package));
			$stm->bindString(htmlspecialchars($arch));
			$stm->execute();
			}

		$stm->close();
		}
	catch (DBException $e)
		{
		$this->showFailure('Allan broke it!');
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

private function checkIfAlreadySubmitted()
	{
	try
		{
		$stm = $this->DB->prepare
			('
			SELECT
				ip,
				visited
			FROM
				package_statistics_log
			WHERE
				visited >= ?
				AND ip = ?
			');
		$stm->bindInteger(time() - self::$delay);
		$stm->bindString($this->Input->Server->getString('REMOTE_ADDR'));
		$lastVisit = $stm->getRow();
		$stm->close();

		$this->showFailure('You already submitted your package list via '.$lastVisit['ip'].' at '.date('r', $lastVisit['visited']).".\n         You are blocked until ".date('r', $lastVisit['visited'] + self::$delay));
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		}
	}

private function insertLogEntry($arch, $packageCount)
	{
	$stm = $this->DB->prepare
		('
		INSERT INTO
			package_statistics_log
		SET
			ip = ?,
			visited = ?,
			arch = ?,
			count = ?
		');
	$stm->bindString($this->Input->Server->getString('REMOTE_ADDR'));
	$stm->bindInteger(time());
	$stm->bindString(htmlspecialchars($arch));
	$stm->bindInteger($packageCount);
	$stm->execute();
	$stm->close();
	}

}

?>