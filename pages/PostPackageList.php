<?php
/*
	Copyright 2002-2012 Pierre Schmitz <pierre@archlinux.de>

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
		$this->setContentType('text/plain; charset=UTF-8');
		try {
			# Can be rewritten once 2.0 is no longer in use
			$pkgstatsver = Input::post()->getString('pkgstatsver',
				str_replace('pkgstats/', '', Input::server()->getString('HTTP_USER_AGENT')));
		} catch(RequestException $e) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure('Please make sure to use pkgstats to submit your data.');
		}
		try {
			$packages = array_unique(explode("\n", trim(Input::post()->getString('packages'))));
			$packageCount = count($packages);
			$arch = Input::post()->getString('arch');
			# Can be rewritten once 1.0 is no longer in use
			$mirror = Input::post()->getString('mirror', '');
			# Can be rewritten once 2.0 is no longer in use
			$this->quiet = (Input::post()->getString('quiet', 'false') == 'true');
		} catch(RequestException $e) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure($e->getMessage());
		}
		if (!in_array($pkgstatsver, array(
			'1.0',
			'2.0',
			'2.1'
		))) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure('Sorry, your version of pkgstats is not supported.');
		}
		if (!empty($mirror) && !preg_match('#^(https?|ftp)://\S+/#', $mirror)) {
			$mirror = '';
		} elseif (!empty($mirror) && Input::post()->getHtmlLength('mirror') > 255) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure(htmlspecialchars($mirror) . ' is too long.');
			$mirror = '';
		}
		if (!in_array($arch, array(
			'i686',
			'x86_64'
		))) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure(htmlspecialchars($arch) . ' is not a known architecture.');
		}
		if ($packageCount == 0) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure('Your package list is empty.');
		}
		if ($packageCount > 10000) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure('So, you have installed more than 10,000 packages?');
		}
		foreach ($packages as $package) {
			if (!preg_match('/^[^-]+\S{0,254}$/', htmlspecialchars($package))) {
				$this->setStatus(Output::BAD_REQUEST);
				$this->showFailure(htmlspecialchars($package) . ' does not look like a valid package');
			}
		}
		$this->checkIfAlreadySubmitted();
		$countryCode = Input::getClientCountryCode();
		try {
			Database::beginTransaction();
			$stm = Database::prepare('
			INSERT INTO
				pkgstats_users
			SET
				ip = :ip,
				time = :time,
				arch = :arch,
				countryCode = ' . (!empty($countryCode) ? ':countryCode' : 'NULL') . ',
				mirror = ' . (!empty($mirror) ? ':mirror' : 'NULL') . ',
				packages = :packages
			');
			$stm->bindValue('ip', sha1(Input::getClientIP()), PDO::PARAM_STR);
			$stm->bindValue('time', Input::getTime(), PDO::PARAM_INT);
			$stm->bindValue('arch', htmlspecialchars($arch), PDO::PARAM_STR);
			!empty($countryCode) && $stm->bindValue('countryCode', htmlspecialchars($countryCode), PDO::PARAM_STR);
			!empty($mirror) && $stm->bindValue('mirror', htmlspecialchars($mirror), PDO::PARAM_STR);
			$stm->bindParam('packages', $packageCount, PDO::PARAM_INT);
			$stm->execute();
			$stm = Database::prepare('
			INSERT INTO
				pkgstats_packages
			SET
				pkgname = :pkgname,
				month = :month,
				count = 1
			ON DUPLICATE KEY UPDATE
				count = count + 1
			');
			foreach ($packages as $package) {
				$stm->bindValue('pkgname', htmlspecialchars($package), PDO::PARAM_STR);
				$stm->bindValue('month', date('Ym', Input::getTime()), PDO::PARAM_INT);
				$stm->execute();
			}
			Database::commit();
		} catch(PDOException $e) {
			Database::rollBack();
			$this->setStatus(Output::INTERNAL_SERVER_ERROR);
			$this->showFailure($e->getMessage());
		}
	}

	protected function showWarning($text) {
		echo 'Warning: ' . $text . "\n";
		exit();
	}

	protected function showFailure($text) {
		echo 'Failure: ' . $text . "\n";
		exit();
	}

	public function printPage() {
		if (!$this->quiet) {
			echo 'Thanks for your submission. :-)' . "\n";
			echo 'See results at ' . $this->createURL('Statistics', array() , true, false) . "\n";
		}
	}

	private function checkIfAlreadySubmitted() {
		$stm = Database::prepare('
		SELECT
			COUNT(*) AS count,
			MIN(time) AS mintime
		FROM
			pkgstats_users
		WHERE
			time >= :time
			AND ip = :ip
		GROUP BY
			ip
		');
		$stm->bindValue('time', Input::getTime() - $this->delay, PDO::PARAM_INT);
		$stm->bindValue('ip', sha1(Input::getClientIP()), PDO::PARAM_STR);
		$stm->execute();
		$log = $stm->fetch();
		if ($log !== false && $log['count'] >= $this->count) {
			$this->setStatus(Output::BAD_REQUEST);
			$this->showFailure('You already submitted your data ' . $this->count . ' times since ' . $this->l10n->getGmDateTime($log['mintime']) . ' using the IP ' . Input::getClientIP() . ".\n         You are blocked until " . $this->l10n->getGmDateTime($log['mintime'] + $this->delay));
		}
	}
}

?>
