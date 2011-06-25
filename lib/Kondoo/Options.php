<?php

namespace Kondoo;

use \Zend_Config_Xml as Xml;
use \Zend_Config_Yaml as Yaml;
use \Zend_Config_Ini as Ini;
use \Zend_Config_Json as Json;

/**
 * Class for storing the configuration and settings. Using the load function some basic
 * configuration files can be loaded.
 */
class Options {
	
	const SEPARATOR = '.';
	
	/**
	 * Array containing all values stored in the configuration
	 * @var array
	 */
	protected static $values = array();
	
	/**
	 * Load an ini file, insert its properties into the current configuration
	 * @param string $filename The file to load
	 */
	public static function load($filename, $env = null)
	{
		$fileParts = explode('.', $filename);
		$extension = $fileParts[count($fileParts) - 1];
		
		if($filename[0] === DIRECTORY_SEPARATOR) {
			$filename = static::get('app.location') . $filename;
		} else {
			$filename = static::get('app.location') . DIRECTORY_SEPARATOR . $filename;
		}
		
		if(is_null($env)) {
		    $env = static::get('app.env', 'production');
		}
		
		switch($extension) {
			case 'ini':  $config = new Xml($filename,  $env); break;
			case 'yml':
			case 'yaml': $config = new Yaml($filename, $env); break;
			case 'xml':  $config = new Ini($filename,  $env); break;
			case 'js':
			case 'json': $config = new Json($filename, $env); break;
			default:
			    throw new Exception(sprintf(_("Extension %s is not a valid format."), $extension));
		}
		static::add($config->toArray());
	}
	
	/**
	 * Add the array of options to the current configuration at the position given via the
	 * second parameter. If the second parameter is omitted or set to null, it is set to the
	 */
	public static function add(array $config, $parent = null)
	{
	    $isIndexed = count(array_filter(array_keys($config), 'is_string')) === 0;
	    if($isIndexed && !is_null($parent)) {
	        $arr =& static::get((string) $parent, null);
	        $isIndexedArr = is_array($arr) && 
	            count(array_filter(array_keys($arr), 'is_string')) === 0;
	        if($isIndexedArr) {
	            $arr = array_merge($arr, $config);
	        } else if(!is_array($arr)) {
	            static::set((string) $parent, $config);
	        }
	    }
	    
	    foreach($config as $key => $value)
	    {
	        if(is_null($parent)) {
	            $fullKey = (string) $key;
	        } else {
	            $fullKey = (string) $parent . static::SEPARATOR . $key;
	        }
	        
	        if(is_array($value)) {
	            static::add($value, $fullKey);
	        } else {
	            static::set($fullKey, $value);
	        }
	    }
	}
	
	/**
	 * Set the given id to the given value
	 * @param string $id The identifier of the configuration option to set
	 * @param mixed $value The new value of the given identifier
	 */
	public static function set($id, $value)
	{
	    $parts = explode(static::SEPARATOR, $id);
	    $finalKey = array_pop($parts);
	    $current =& static::$values;
	    foreach($parts as $part) {
	        if(!isset($current[$part])) {
	            $current[$part] = array();
	        }
	        $current =& $current[$part];
	    }
		$current[$finalKey] = $value;
	}
	
	/**
	 * 
	 * @param string $id The identifier to retrieve a value for
	 * @param mixed $default The value to return if the identifier doesn't exist.
	 * @return mixed The value of the given identifier or the value of $default if it doesn't exist
	 */
	public static function get($id, $default = null)
	{
		$parts = explode(static::SEPARATOR, $id);
		$finalKey = array_pop($parts);
		$current =& static::$values;
		foreach($parts as $part) {
		    if(!isset($current[$part])) {
		        return $default;
		    }
		    $current =& $current[$part];
 		}
 		
 		if(isset($current[$finalKey])) {
 		    return $current[$finalKey];
 		}
 		return $default;
	}
	
	/**
	 * Increment the value of the given identifier with the amount given, only if the value is an
	 * integer. Returns the new value, even if the new value is unchanged because the value wasn't
	 * an integer.
	 * @param string $id
	 * @param int $amount
	 * @return mixed
	 */
	public static function increment($id, $amount = 1)
	{
		$var = self::get($id, 0);
		if(is_int($var)) {
			self::set($id, $var + $amount);
			return $var + $amount;	
		}
		return $var;	
	}
	
	/**
	 * Decrement the value of the given identifier with the amount given, only if the value is an
	 * integer. Returns the new value, even if the new value is unchanged because the value wasn't
	 * an integer.
	 * @param string $id
	 * @param int $amount
	 * @return mixed
	 */
	public static function decrement($id, $amount = 1)
	{
		return self::increment($id, -$amount);
	}
	
	/**
	 * Remove a value from the configuration given an identifier
	 * @param string $id
	 * @return mixed The previously stored value, or null if it didn't exist
	 */
	public static function remove($id)
	{
		if(static::has($id)) {
		    $parts = explode(static::SEPARATOR, $id);
    		$finalKey = array_pop($parts);
    		$current =& static::$values;
    		foreach($parts as $part) {
    		    $current =& $current[$part];
     		}
			unset($current[$finalKey]);
		}
	}
	
	/**
	 * Returns true if the given identifier has a value in the configuration, false otherwise.
	 * @param string $id The identifier to look up
	 * @return boolean
	 */
	public static function has($id)
	{
		$parts = explode(static::SEPARATOR, $id);
		$current =& static::$values;
		foreach($parts as $part) {
		    if(!isset($current[$part])) {
		        return false;
		    }
		    $current =& $current[$part];
 		}
 		return true;
	}
}