<?php


class Settings{


private $config = array();


public function __construct()
	{
	$this->config['locale']			= 'de_DE.utf8';
	$this->config['timezone']		= 'Europe/Berlin';

	$this->config['domain']			= 'localhost';
	$this->config['log_dir']		= '';

	$this->config['ll_database'] 		= 'current';
	$this->config['ll_user']		= 'root';
	$this->config['ll_password']		= '';

	if (file_exists(PATH.'LocalSettings.php'))
		{
		include (PATH.'LocalSettings.php');
		}

	setlocale(LC_ALL, $this->config['locale']);
	date_default_timezone_set($this->config['timezone']);
	}


public function getValue($key)
	{
	return $this->config[$key];
	}


}

?>