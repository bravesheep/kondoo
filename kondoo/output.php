<?php

namespace Kondoo;

use \Exception;
use Kondoo\Output\Wrapper;

class Output {
	
	const TEMPLATE_EXT = '.php';
	
	private static $showTemplate = true;
	
	private static $template = null;
	
	private static $headers = array();
	
	private static $output = "";
	
	private static $data = array();
	
	/**
	 * Output an header directly, or if late printing is enabled, send it after the output function
	 * is called.
	 * @param unknown_type $header
	 * @param unknown_type $content
	 * @throws Exception
	 */
	public static function h($header, $content = null)
	{
		if(!headers_sent()) {
			if(!is_null($content)) {
				$header = "$header: $content";
			}
			
			if(Config::get('output.late', true)) {
				self::$headers[] = $header;
			} else {
				header($header);
			}		
		} else {
			throw new Exception("Cannot set header '$header', headers already sent");
		}
	}
	
	/**
	 * Print a variable directly, or if late printing is enabled, send it after the output function
	 * is called. The p function can be used as if it was sprintf.
	 * @param string $var
	 */
	public static function p($var)
	{
		$argCount = func_num_args();
		if($argCount > 1) {
			$args = func_get_args();
			$out = call_user_func_array('sprintf', $args);
		} else {
			$out = $var;
		}
		
		if(Config::get('output.late', true)) {
			self::$output .= $out;
		} else {
			print $out;
		}
	}
	
	/**
	 * Translate the string given using gettext and send its output with the rest of the parameters 
	 * given to the print function.
	 * @param string $var The string to translate.
	 */
	public static function t($var)
	{
		$arguments = func_get_args();
		$arguments[0] = _($arguments[0]);
		call_user_func_array(array('Kondoo\\Output', 'p'), $arguments);
	}
	
	/**
	 * Set the template to a given template. The template name does not need to include an extension
	 * or the directory where templates are stored. After this function is called, templates will 
	 * not be auto-selected. 
	 * @param string $template
	 * @throws Exception
	 */
	public static function template($template)
	{
		$template = strtolower($template);
		$templateDir = self::getTemplateDir();
		$templateFile = $templateDir . $template . self::TEMPLATE_EXT;
		if(file_exists($templateFile) && is_readable($templateFile)) {
			self::$template = $templateFile;
		} else {
			throw new Exception("Template '$template' does not exist or cannot be read");
		}
	}
	
	/**
	 * Return the absolute path to the directory where templates are stored.
	 */
	public static function getTemplateDir()
	{
		$templateDir = Config::get('app.templates');
		if($templateDir[0] === '.') {
			$appLoc = Config::get('app.location');
			$templateDir = realpath($appLoc . $templateDir) . DIRECTORY_SEPARATOR;
		}
		return $templateDir;
	}
	
	/**
	 * Output anything that isn't yet outputted to the browser. If the preventTemplate function
	 * wasn't called then a template view is parsed and outputted to the browser.
	 */
	public static function output()
	{
		$data = array(
			'headers' => self::$headers,
			'content' => self::$output
		);
		Application::get()->trigger('output', $_data);
		foreach($data['headers'] as $header) {
			header($header);
		}
		print $data['content'];

		if(self::$showTemplate) {
			if(is_null(self::$template)) {
				$request = Application::get()->request;
				self::template($request->getController() . DIRECTORY_SEPARATOR . 
					$request->getAction());
			}
			unset($data);
			unset($request);
			
			$_error_reporting = error_reporting();
			error_reporting(($_error_reporting & E_NOTICE) ^ E_NOTICE);
			
			// TODO: what array to extract?
			extract(self::$data, EXTR_OVERWRITE);
			require self::$template;
			error_reporting($_error_reporting);
		}
	}
	
	/**
	 * Set the given variable to the given value.
	 * @param string $variable
	 * @param mixed $value
	 */
	public static function set($variable, $value)
	{
		self::$data[$variable] = new Wrapper($value);
	}
	
	/**
	 * Retrieve the variable with the given name or return the default value if it doesn't exist.
	 * @param string $variable
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($variable, $default = null)
	{
		if(isset(self::$data[$variable])) {
			return self::$data[$variable]->raw();
		}
		return $default;
	}
	
	/**
	 * Any old not yet outputted data is removed and all settings are reset to their default values
	 */
	public static function reset() 
	{
		self::$headers = array();
		self::$output = "";
		self::$data = array();
		self::$showTemplate = true;
	}
	
	/**
	 * Prevent the template from being called and outputted on calling the output function.
	 */
	public static function preventTemplate()
	{
		self::$showTemplate = false;
	}
	
	/**
	 * Output some json to the browser directly, or if late printing is enabled: print it when the
	 * output function is called. This function calls preventTemplate so no templates will be
	 * outputted after this function is called.
	 * @param mixed $data
	 */
	public static function json($data)
	{
		self::preventTemplate();
		self::h('Content-Type', 'application/json');
		
		$value = json_encode($data);
		if(Config::get('output.late', true)) {
			self::$output .= $value;
		} else {
			print $value;
		}
	}
}