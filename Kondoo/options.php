<?php

namespace Kondoo;

/**
 * Class for storing the configuration and settings. Using the load function some basic
 * configuration files can be loaded.
 */
class Config {
	
	const CONFIG_SEPARATOR = '.';
	
	/**
	 * Array containing all values stored in the configuration
	 * @var array
	 */
	protected static $values = array();
	
	/**
	 * Load an ini file, insert its properties into the current configuration
	 * @param string $filename The file to load
	 */
	public static function load($filename)
	{
		$fileParts = explode('.', $filename);
		$extension = $fileParts[count($fileParts) - 1];
		
		if($filename[0] === DIRECTORY_SEPARATOR) {
			$filename = Config::get('app.location') . $filename;
		} else {
			$filename = Config::get('app.location') . DIRECTORY_SEPARATOR . $filename;
		}
		
		switch($extension) {
			case 'ini': static::loadIni($filename); break;
		}
	}
	
	/**
	 * Load all values from the given ini file and insert them in the config.
	 * @param string $filename Path to the ini file to be loaded
	 */
	public static function loadIni($filename)
	{
		$fileContent = parse_ini_file($filename, true, INI_SCANNER_NORMAL);
		foreach($fileContent as $section => $sectionContent) {
			foreach($sectionContent as $key => $value) {
				self::set($section . self::CONFIG_SEPARATOR . $key, $value);
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
		static::$values[$id] = $value;
	}
	
	/**
	 * Return the value of a given identifier as stored in the config. If the identifier ends with
	 * a @see{CONFIG_SEPARATOR} the result of the @see{getAll} function for the string excluding 
	 * the ending @see{CONFIG_SEPARATOR} is returned and the default value is ignored.
	 * @param string $id The identifier to retrieve a value for
	 * @param mixed $default The value to return if the identifier doesn't exist.
	 * @return mixed The value of the given identifier or the value of $default if it doesn't exist
	 */
	public static function get($id, $default = null)
	{
		if($id[strlen($id) - 1] === static::CONFIG_SEPARATOR) {
			return static::getAll(substr($id, 0, strlen($id) - 1));
		}
		if(static::exists($id)) {
			return static::$values[$id];
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
	 * Returns an array of values that start with the given id followed by at least a
	 * @see{CONFIG_SEPARATOR}. If no values can be found, an empty array is returned.
	 * @param string $id Identifier search value
	 * @return array
	 */	
	public static function getAll($id)
	{
		if($id[strlen($id) - 1] !== self::CONFIG_SEPARATOR) {
			$id .= self::CONFIG_SEPARATOR;
		}
		
		$values = array();
		foreach(static::$values as $key => $value) {
			if(strpos($key, $id) === 0) {
				$values[$key] = $value;
			}
		}
		return $values;
	}
	
	/**
	 * Remove a value from the configuration given an identifier
	 * @param string $id
	 * @return mixed The previously stored value, or null if it didn't exist
	 */
	public static function remove($id)
	{
		$value = static::get($id, null);
		if(static::exists($id)) {
			unset(static::$values[$id]);
		}
	}
	
	/**
	 * Returns true if the given identifier has a value in the configuration, false otherwise.
	 * @param string $id The identifier to look up
	 * @return boolean
	 */
	public static function exists($id)
	{
		return isset(static::$values[$id]);
	}
}