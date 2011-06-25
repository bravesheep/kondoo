<?php

namespace Kondoo\Output;

use Kondoo\Config;
use Kondoo\Application;
use Kondoo\Output;

class Template {
	const TEMPLATE_EXT = '.php';
	
	private $data = array();
	private $template;
	private $previousErrorReporting = null;
	
	/**
	 * Create a new Template and assign the given data to be used.
	 * @param string $template
	 * @param array $data
	 */
	public function __construct($template, array $data = array())
	{
		foreach($data as $name => $item) {
			if($item instanceof Wrapper) {
				$this->data[$name] = $item;
			} else {
				$this->data[$name] = new Wrapper($item);
			}
		}
		$this->template = $template;
	}
	
	/**
	 * Ready the template and output its result.
	 */
	public function render()
	{
		$this->previousErrorReporting = error_reporting();
		error_reporting($this->previousErrorReporting & ~E_NOTICE);
		
		extract($this->data, EXTR_OVERWRITE);
		require $this->template;
		
		error_reporting($this->previousErrorReporting);
		$this->previousErrorReporting = null;
	}
	
	public function __call($name, array $params = array()) 
	{
		return Output::call($this, $name, $params);
	}
	
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * Convert the name of a template to an absolute path of a template
	 * @param string $template Name of a template
	 * @return The absolute path to a template
	 */
	public static function templateToPath($template) 
	{
		$templateDir = Config::get('app.templates');
		if($templateDir[0] === '.') {
			$appLoc = Config::get('app.location');
			$templateDir = realpath($appLoc . DIRECTORY_SEPARATOR . 
					$templateDir) . DIRECTORY_SEPARATOR;
		}
		return $templateDir . strtolower($template) . self::TEMPLATE_EXT;
	}
}