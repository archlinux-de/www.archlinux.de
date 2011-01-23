<?php
/*
	Copyright 2002-2011 Pierre Schmitz <pierre@archlinux.de>

	This file is part of archlinux.de.

	archlinux.de is free software: you can redistribute it and/or modify
	it under the terms of the GNU Affero General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	archlinux.de is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU Affero General Public License for more details.

	You should have received a copy of the GNU Affero General Public License
	along with archlinux.de.  If not, see <http://www.gnu.org/licenses/>.
*/

class L10n extends Modul {

	private $localeInfo = array();
	private $locale = '';

	public function __construct() {
		$this->locale = $this->getAcceptLanguage();
		$this->initLocale();
	}

	private function initLocale() {
		putenv('LC_ALL=' . $this->locale);
		putenv('LANGUAGE=' . $this->locale);
		setlocale(LC_ALL, $this->locale);
		date_default_timezone_set(Config::get('L10n', 'timezone'));
		$this->localeInfo = localeconv();
		if (function_exists('bindtextdomain')) {
			bindtextdomain('messages', 'l10n');
			textdomain('messages');
		}
	}

	private function getAcceptLanguage() {
		$locales = Config::get('L10n', 'locales');
		try {
			foreach (explode(',', $this->Input->Server->getString('HTTP_ACCEPT_LANGUAGE')) as $lang) {
				$lang = substr(strtolower(trim($lang)) , 0, 2);
				if (isset($locales[$lang])) {
					return $locales[$lang];
				}
			}
		} catch(RequestException $e) {
		}
		return array_shift($locales);
	}

	public function getLocale() {
		return $this->locale;
	}

	public function setLocale($locale) {
		$this->locale = $locale;
		$this->initLocale();
	}

	public function getText($text) {
		if (function_exists('gettext')) {
			return gettext($text);
		} else {
			return $text;
		}
	}

	public function getDate($timestamp = null) {
		return date($this->getText('Y-m-d') , $timestamp);
	}

	public function getTime($timestamp = null) {
		return date($this->getText('H:i') , $timestamp);
	}

	public function getDateTime($timestamp = null) {
		return date($this->getText('Y-m-d H:i') , $timestamp);
	}

	public function getGmDate($timestamp = null) {
		return gmdate('Y-m-d', $timestamp);
	}

	public function getGmTime($timestamp = null) {
		return gmdate('H:i', $timestamp);
	}

	public function getGmDateTime($timestamp = null) {
		return gmdate('Y-m-d H:i', $timestamp);
	}

	// @TODO: add singular
	public function getEpoch($seconds) {
		$minutes = 60;
		$hours = 60 * $minutes;
		$days = 24 * $hours;
		$weeks = 7 * $days;
		$months = 4 * $weeks;
		$years = 12 * $months;
		if ($seconds >= $years) {
			$result = round($seconds / $years, 2);
			$postfix = '&nbsp;' . $this->getText('years');
		} elseif ($seconds >= $months) {
			$result = round($seconds / $months, 2);
			$postfix = '&nbsp;' . $this->getText('months');
		} elseif ($seconds >= $weeks) {
			$result = round($seconds / $weeks, 2);
			$postfix = '&nbsp;' . $this->getText('weeks');
		} elseif ($seconds >= $days) {
			$result = round($seconds / $days, 2);
			$postfix = '&nbsp;' . $this->getText('days');
		} elseif ($seconds >= $hours) {
			$result = round($seconds / $hours, 2);
			$postfix = '&nbsp;' . $this->getText('hours');
		} elseif ($seconds >= $minutes) {
			$result = round($seconds / $minutes, 2);
			$postfix = '&nbsp;' . $this->getText('minutes');
		} else {
			$result = round($seconds, 2);
			$postfix = '&nbsp;' . $this->getText('seconds');
		}
		return $result . $postfix;
	}

	public function getNumber($number, $decimals = 0) {
		return number_format($number, $decimals, $this->localeInfo['decimal_point'], $this->localeInfo['thousands_sep']);
	}
}

?>
