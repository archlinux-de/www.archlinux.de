<?php

use archportal\lib\Config;

Config::set('common', 'statistics', true);
Config::set('common', 'legacysites', true);
Config::set('common', 'debug', true);
Config::set('common', 'sitename', 'archlinux.de');

Config::set('Database', 'host', 'db');
Config::set('Database', 'password', 'pw');

Config::set('L10n', 'locale', 'de_DE.utf8');
Config::set('L10n', 'timezone', 'Europe/Berlin');

Config::set('mirrors', 'default', 'http://mirror.de.leaseweb.net/archlinux/');
Config::set('mirrors', 'country', 'DE');

Config::set('packages', 'repositories', array(
    'core' => array('x86_64'),
));
Config::set('packages', 'cgit', 'https://projects.archlinux.de/svntogit/');

Config::set('news', 'feed', 'https://bbs.archlinux.de/extern.php?action=feed&fid=257&type=atom&order=posted&show=15');
Config::set('news', 'archive', 'https://bbs.archlinux.de/viewforum.php?id=257');
