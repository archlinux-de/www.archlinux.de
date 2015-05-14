<?php

/*
  Copyright 2002-2015 Pierre Schmitz <pierre@archlinux.de>

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

use archportal\lib\Config;

Config::set('common', 'statistics', false);
Config::set('common', 'legacysites', false);
Config::set('common', 'debug', false);
Config::set('common', 'email', 'webmaster@localhost');
Config::set('common', 'tmpdir', '/tmp');
Config::set('common', 'sitename', 'archportal');

Config::set('Database', 'database', 'archportal');
Config::set('Database', 'user', 'root');
Config::set('Database', 'password', '');

Config::set('L10n', 'locale', 'en_US.utf8');
Config::set('L10n', 'timezone', 'UTC');

Config::set('packages', 'mirror', 'http://mirror.de.leaseweb.net/archlinux/');
Config::set('packages', 'cgit', 'https://projects.archlinux.org/svntogit/');
Config::set('packages', 'repositories', array(
    'core' => array('x86_64', 'i686'),
    'extra' => array('x86_64', 'i686'),
    'testing' => array('x86_64', 'i686'),
    'community' => array('x86_64', 'i686'),
    'community-testing' => array('x86_64', 'i686'),
    'multilib' => array('x86_64'),
    'multilib-testing' => array('x86_64')
));
Config::set('packages', 'default_architecture', Config::get('packages', 'repositories')['core'][0]);
Config::set('packages', 'files', true);
Config::set('packages', 'delay', 120);

Config::set('mirrors', 'status', 'https://www.archlinux.org/mirrors/status/json/');
Config::set('mirrors', 'default', Config::get('packages', 'mirror'));
Config::set('mirrors', 'country', 'DE');

Config::set('news', 'feed', '');
Config::set('news', 'archive', '');

Config::set('releng', 'releases', 'https://www.archlinux.org/releng/releases/json/');
