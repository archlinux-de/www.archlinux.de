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

abstract class Page extends Output {

	protected $variables = array();
	protected $l10n = null;

	public function __construct() {
		$this->variables['body'] = '';
		$this->variables['title'] = '';
		$this->variables['meta.robots'] = 'index,follow';
		$this->l10n = new L10n();
		parent::__construct();
	}

	protected function setValue($key, $value) {
		$this->variables[$key] = $value;
	}

	protected function getValue($key) {
		return $this->variables[$key];
	}

	protected function getName() {
		return get_class($this);
	}

	protected function showWarning($text) {
		$this->setValue('meta.robots', 'noindex,nofollow');
		$this->setValue('title', $this->l10n->getText('Warning'));
		$this->setValue('body', '<div id="warning">' . $text . '</div>');
		require (__DIR__.'/../templates/PageTemplate.php');
		exit();
	}

	protected function showFailure($text) {
		$this->setValue('meta.robots', 'noindex,nofollow');
		$this->setValue('title', $this->l10n->getText('Error'));
		$this->setValue('body', '<div id="warning">' . $text . '</div>');
		require (__DIR__.'/../templates/PageTemplate.php');
		exit();
	}

	public function prepare() {
		$this->setValue('title', $this->l10n->getText('Warning'));
		$this->setValue('body', $this->l10n->getText('no text'));
	}

	protected function cutString($string, $length) {
		// Verhindere das Abschneiden im Entity
		$string = htmlspecialchars_decode(trim($string));
		$string = (mb_strlen($string, 'UTF-8') > $length ? mb_substr($string, 0, ($length - 3) , 'UTF-8') . '...' : $string);
		return htmlspecialchars($string);
	}

	public function printPage() {
		require (__DIR__.'/../templates/PageTemplate.php');
	}
}

?>
