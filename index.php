<?php

/*
  Copyright 2002-2014 Pierre Schmitz <pierre@archlinux.de>

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

namespace archportal;

require (__DIR__ . '/lib/AutoLoad.php');

use archportal\lib\Input;
use archportal\lib\Page;
use archportal\lib\Routing;

spl_autoload_register('archportal\lib\AutoLoad::loadClass');
set_exception_handler('archportal\lib\Exceptions::ExceptionHandler');
set_error_handler('archportal\lib\Exceptions::ErrorHandler');

$page = Routing::getPageClass(Input::get()->getString('page', 'Start'));
/** @var Page $thisPage */
$thisPage = new $page();

$thisPage->prepare();
$thisPage->printPage();
