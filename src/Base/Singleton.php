<?php
/***************************************************************************
 *   Copyright (C) 2004-2007 by Sveta A. Smirnova                          *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 **************************************************************************/

namespace DBD\Base;

use Exception;

abstract class Singleton
{
	private static $instances = array();
	
	protected function __construct() {/* you can't create me */}
    final private function __clone() {/* do not clone me */}

	/// @example singleton.php
	final public static function getInstance(
		$class, $args = null /* , ... */
	)
	{
		// for Singleton::getInstance('class_name', $arg1, ...) calling
		if (2 < func_num_args())
		{
			$args = func_get_args();
			array_shift($args);
		}
		
		if (!isset(self::$instances[$class]))
		{
			$object =
				$args
					? new $class($args)
					: new $class();
					
			if (!($object instanceof Singleton))
			{
				throw new Exception(
					"Class '{$class}' is something not a Singleton's child"
				);
			}
			return self::$instances[$class] = $object;
		} 
		else
		{
			return self::$instances[$class];
		}
	}
	
	final public static function getAllInstances()
	{
		return self::$instances;
	}
	
}