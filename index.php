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

// require ('modules/Config.php');
require ('modules/Modul.php');
require ('modules/Exceptions.php');
// require ('modules/Input.php');
// require ('modules/Output.php');
// require ('modules/L10n.php');

$Input = Modul::set('Input', new Input());
Modul::set('L10n', new L10n());
$Output = Modul::set('Output', new Output());

function __autoload($class) {
	Modul::loadModul($class);
}

$page = $Input->Get->getString('page', 'Start');
try {
	Page::loadPage($page);
} catch(RuntimeException $e) {
	$page = 'NotFound';
	Page::loadPage($page);
}
$class = new $page();
$class->prepare();
$class->printPage();

?>
