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

	private $delay = 86400; // 24 hours
	private $count = 10;
	private $quiet = false;

	public function prepare() {
		$this->Output->setContentType('text/plain; charset=UTF-8');
		$this->Output->setCompression(false);
		try {
			# Can be rewritten once 2.0 is no longer in use
			$pkgstatsver = $this->Input->Post->getString('pkgstatsver',
				str_replace('pkgstats/', '', $this->Input->Server->getString('HTTP_USER_AGENT')));
		} catch(RequestException $e) {
			$this->Output->setStatus(Output::BAD_REQUEST);
			$this->showFailure('Please make sure to use pkgstats to submit your data.');
		}
		try {
			$packages = array_unique(explode("\n", trim($this->Input->Post->getString('packages'))));
			$packageCount = count($packages);
			$arch = $this->Input->Post->getString('arch');
			# Can be rewritten once 1.0 is no longer in use
			$mirror = $this->Input->Post->getString('mirror', '');
			# Can be rewritten once 2.0 is no longer in use
			$this->quiet = ($this->Input->Post->getString('quiet', 'false') == 'true');
		} catch(RequestException $e) {
			$this->Output->setStatus(Output::BAD_REQUEST);
			$this->showFailure($e->getMessage());
		}
		if (!in_array($pkgstatsver, array(
			'1.0',
			'2.0',
			'2.1'
		))) {
			$this->Output->setStatus(Output::BAD_REQUEST);
			$this->showFailure('Sorry, your version of pkgstats is not supported.');
		}
		if (!empty($mirror) && !preg_match('#^(https?|ftp)://\S+/#', $mirror)) {
			$mirror = '';
		} elseif (!empty($mirror) && $this->Input->Post->getHtmlLength('mirror') > 255) {
			$this->Output->setStatus(Output::BAD_REQUEST);
			$this->showFailure(htmlspecialchars($mirror) . ' is too long.');
			$mirror = '';
		}
		if (!in_array($arch, array(
			'i686',
			'x86_64'
		))) {
			$this->Output->setStatus(Output::BAD_REQUEST);
			$this->showFailure(htmlspecialchars($arch) . ' is not a known architecture.');
		}
		if ($packageCount == 0) {
			$this->Output->setStatus(Output::BAD_REQUEST);
			$this->showFailure('Your package list is empty.');
		}
		if ($packageCount > 10000) {
			$this->Output->setStatus(Output::BAD_REQUEST);
			$this->showFailure('So, you have installed more than 10,000 packages?');
		}
		foreach ($packages as $package) {
			if (!preg_match('/^[^-]+\S{0,254}$/', htmlspecialchars($package))) {
				$this->Output->setStatus(Output::BAD_REQUEST);
				$this->showFailure(htmlspecialchars($package) . ' does not look like a valid package');
			}
		}
		$this->checkIfAlreadySubmitted();
		$country = $this->Input->getClientCountryName();
		try {
			$stm = $this->DB->prepare('
			INSERT INTO
				pkgstats_users
			SET
				ip = ?,
				time = ?,
				arch = ?,
				country = ' . (!empty($country) ? '?' : 'NULL') . ',
				mirror = ' . (!empty($mirror) ? '?' : 'NULL') . ',
				packages = ?
			');
			$stm->bindString(sha1($this->Input->getClientIP()));
			$stm->bindInteger($this->Input->getTime());
			$stm->bindString(htmlspecialchars($arch));
			!empty($country) && $stm->bindString(htmlspecialchars($country));
			!empty($mirror) && $stm->bindString(htmlspecialchars($mirror));
			$stm->bindInteger($packageCount);
			$stm->execute();
			$stm->close();
			$stm = $this->DB->prepare('
			INSERT INTO
				pkgstats_packages
			SET
				pkgname = ?,
				month = ?,
				count = 1
			ON DUPLICATE KEY UPDATE
				count = count + 1
			');
			foreach ($packages as $package) {
				$stm->bindString(htmlspecialchars($package));
				$stm->bindInteger(date('Ym', $this->Input->getTime()));
				$stm->execute();
			}
			$stm->close();
		} catch(DBException $e) {
			$this->Output->setStatus(Output::INTERNAL_SERVER_ERROR);
			$this->showFailure($e->getMessage());
		}
	}

	protected function showWarning($text) {
		$text = 'Warning: ' . $text . "\n";
		$this->Output->writeOutput($text);
	}

	protected function showFailure($text) {
		$text = 'Failure: ' . $text . "\n";
		$this->Output->writeOutput($text);
		exit;
	}

	public function show() {
		if ($this->quiet) {
			$text = '';
		} else {
			$text = 'Thanks for your submission. :-)' . "\n";
			$text.= 'See results at ' . $this->Output->createURL('Statistics', array() , true, false) . "\n";
		}
		$this->Output->writeOutput($text);
	}

	private function checkIfAlreadySubmitted() {
		try {
			$stm = $this->DB->prepare('
			SELECT
				COUNT(*) AS count,
				MIN(time) AS mintime
			FROM
				pkgstats_users
			WHERE
				time >= ?
				AND ip = ?
			GROUP BY
				ip
			');
			$stm->bindInteger($this->Input->getTime() - $this->delay);
			$stm->bindString(sha1($this->Input->getClientIP()));
			$log = $stm->getRow();
			$stm->close();
			if ($log['count'] >= $this->count) {
				$this->Output->setStatus(Output::BAD_REQUEST);
				$this->showFailure('You already submitted your data ' . $this->count . ' times since ' . $this->L10n->getGmDateTime($log['mintime']) . ' using the IP ' . $this->Input->getClientIP() . ".\n         You are blocked until " . $this->L10n->getGmDateTime($log['mintime'] + $this->delay));
			}
		} catch(DBNoDataException $e) {
			$stm->close();
		}
	}
}

?>
