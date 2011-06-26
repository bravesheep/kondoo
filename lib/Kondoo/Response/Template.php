<?php

namespace Kondoo\Response;

use \Kondoo\Options;
use \Kondoo\Application;
use \Kondoo\Response\Template\Wrapper;
use \Kondoo\Request;
use \Kondoo\Util\PathUtil;

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
		return Application::get()->response->call($this, $name, $params);
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
	public static function templateToPath($template = "", Request $request) 
	{
		$templateDir = Options::get('app.dir.templates');
		$needles = array(
		    '%MODULE%' => strtolower($request->getModule()),
		    '%CONTROLLER%' => strtolower($request->getController()),
		    '%ACTION%' => strtolower($request->getAction())
		);
		
		$parts = explode('/', $template);
		if(count($parts) === 3) {
		    $needles['%MODULE%'] = strtolower($parts[0]);
    		$needles['%CONTROLLER%'] = strtolower($parts[1]);
    		$needles['%ACTION%'] = strtolower($parts[2]);
		} else if(count($parts) === 2) {
		    $needles['%CONTROLLER%'] = strtolower($parts[0]);
    		$needles['%ACTION%'] = strtolower($parts[1]);
		} else if(count($parts) === 1 && strlen($parts[0]) > 0) {
    		$needles['%ACTION%'] = strtolower($parts[0]);
		}
		
		
		$template = str_replace(array_keys($needles), $needles, $templateDir) . self::TEMPLATE_EXT;
		return PathUtil::expand($template);
	}
}