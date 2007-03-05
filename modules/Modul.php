<?php

abstract class Modul{

/** FIXME: Nur für Debugging public */
// public static $modules = array();
// 
// 
// public static function __get($name)
// 	{
// 	if (!isset(self::$modules[$name]))
// 		{
// 		$new = new $name();
// 		self::$modules[$name] = &$new;
// 		return $new;
// 		}
// 	else
// 		{
// 		return self::$modules[$name];
// 		}
// 	}
// 
// public static function __set($name, &$object)
// 	{
// 	if (!isset(self::$modules[$name]))
// 		{
// 		self::$modules[$name] = $object;
// 		return $object;
// 		}
// 	else
// 		{
// 		return self::$modules[$name];
// 		}
// 	}

protected function getName()
	{
	return get_class($this);
	}

}

?>