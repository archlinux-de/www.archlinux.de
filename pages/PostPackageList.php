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

class PostPackageList extends Page {

private $delay = 86400;	// 24 hours


public function prepare()
	{
	try
		{
		$packages = array_unique(explode("\n", trim($this->Input->Post->getString('packages'))));
		$arch = $this->Input->Post->getString('arch');
		$pkgstatsver = $this->Input->Post->getString('pkgstatsver');
		$mirror = $this->Input->Post->getString('mirror', '');
		}
	catch (RequestException $e)
		{
		$this->showFailure('No data received');
		}

	if ($pkgstatsver == '1.0')
		{
		$this->showWarning('Please update pkgstats.');
		}
	elseif ($pkgstatsver != '2.0')
		{
		$this->showFailure('Sorry, your version of pkgstats is not supported.');
		}

	if (!empty($mirror) && !preg_match('#^(https?|ftp)://\S+/#', $mirror))
		{
		$mirror = '';
		}
	elseif ($this->Input->getHtmlLength('mirror') > 255)
		{
		$this->showWarning(htmlspecialchars($mirror).' is too long.');
		$mirror = '';
		}

	if (!in_array($arch, array('i686', 'x86_64')))
		{
		$this->showFailure(htmlspecialchars($arch).' is not a known architecture.');
		}

	if (empty($packages))
		{
		$this->showFailure('Your package list is empty.');
		}

	foreach ($packages as $package)
		{
		if (!preg_match('/^[^-]+\S{0,254}$/', htmlspecialchars($package)))
			{
			$this->showFailure(htmlspecialchars($package).' does not look like a valid package');
			}
		}

	$this->checkIfAlreadySubmitted();

	try
		{
		$stm = $this->DB->prepare
			('
			INSERT INTO
				pkgstats_users
			SET
				ip = ?,
				time = ?,
				arch = ?,
				country = NULLIF(CHAR_LENGTH(?), 0),
				mirror = NULLIF(CHAR_LENGTH(?), 0)
			');
		$stm->bindString(sha1($this->Input->getClientIP()));
		$stm->bindInteger($this->Input->getTime());
		$stm->bindString(htmlspecialchars($arch));
		$stm->bindString(htmlspecialchars($this->Input->getClientCountryName()));
		$stm->bindString(htmlspecialchars($mirror));
		$stm->execute();
		$stm->close();

		$stm = $this->DB->prepare
			('
			INSERT INTO
				pkgstats_packages
			SET
				user_id = LAST_INSERT_ID(),
				pkgname = ?
			');
		foreach ($packages as $package)
			{
			$stm->bindString(htmlspecialchars($package));
			$stm->execute();
			}
		$stm->close();
		}
	catch (DBException $e)
		{
		$this->showFailure($e->getMessage());
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
				time
			FROM
				pkgstats_users
			WHERE
				time >= ?
				AND ip = ?
			');
		$stm->bindInteger($this->Input->getTime() - $this->delay);
		$stm->bindString(sha1($this->Input->getClientIP()));
		$lastVisit = $stm->getColumn();
		$stm->close();

		$this->showFailure('You already submitted your data via '.$this->Input->getClientIP()
			.' at '.$this->L10n->getGmDateTime($lastVisit)
			.".\n         You are blocked until ".$this->L10n->getGmDateTime($lastVisit + $this->delay));
		}
	catch (DBNoDataException $e)
		{
		$stm->close();
		}
	}

}

?>
