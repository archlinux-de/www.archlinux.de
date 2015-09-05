<?php

use archportal\lib\Config;

Config::set('common', 'statistics', true);
Config::set('common', 'legacysites', true);

Config::set('common', 'debug', true);

Config::set('Database', 'host', 'db');
Config::set('Database', 'password', 'pw');

Config::set('packages', 'repositories', array(
    'core' => array('x86_64')
));
