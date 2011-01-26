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

class L10n {

	private $localeInfo = array();
	private $locale = '';

	public function __construct() {
		$this->locale = Config::get('L10n', 'locale');

		putenv('LC_ALL='.$this->locale);
		setlocale(LC_ALL, $this->locale);
		date_default_timezone_set(Config::get('L10n', 'timezone'));
		$this->localeInfo = localeconv();
		bindtextdomain('messages', __DIR__.'/../l10n');
		textdomain('messages');
	}

	public function getLocale() {
		return $this->locale;
	}

	private function getLocalePath() {
		return __DIR__.'/../l10n/'.strtok($this->locale, '.');
	}

	public function getText($text) {
		return gettext($text);
	}

	public function ngetText($singular, $plural, $count) {
		return ngettext($singular, $plural, $count);
	}

	public function getTextFile($name) {
		$key = 'L10n:'.$this->locale.':'.$name;
		if (!($text = ObjectCache::getObject($key))) {
			$text = file_get_contents ($this->getLocalePath().'/'.$name.'.html');
			ObjectCache::addObject($key, $text);
		}
		return $text;
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

	public function getEpoch($seconds) {
		$minutes = 60;
		$hours = 60 * $minutes;
		$days = 24 * $hours;
		$weeks = 7 * $days;
		$months = 4 * $weeks;
		$years = 12 * $months;
		if ($seconds >= $years) {
			$result = round($seconds / $years);
			return sprintf($this->ngetText('%d year', '%d years', $result), $result);
		} elseif ($seconds >= $months) {
			$result = round($seconds / $months);
			return sprintf($this->ngetText('%d month', '%d months', $result), $result);
		} elseif ($seconds >= $weeks) {
			$result = round($seconds / $weeks);
			return sprintf($this->ngetText('%d week', '%d weeks', $result), $result);
		} elseif ($seconds >= $days) {
			$result = round($seconds / $days);
			return sprintf($this->ngetText('%d day', '%d days', $result), $result);
		} elseif ($seconds >= $hours) {
			$result = round($seconds / $hours, 2);
			return sprintf($this->getText('%.2f hours'), $result);
		} elseif ($seconds >= $minutes) {
			$result = round($seconds / $minutes, 2);
			return sprintf($this->getText('%.2f minutes'), $result);
		} else {
			$result = round($seconds, 2);
			return sprintf($this->getText('%.2f seconds'), $result);
		}
	}

	public function getNumber($number, $decimals = 0) {
		// FIXME: use sprintf %f instead?
		return number_format($number, $decimals, $this->localeInfo['decimal_point'], $this->localeInfo['thousands_sep']);
	}
}

?>
