<?php

namespace Kondoo\Output;

use Kondoo\Config;
use Kondoo\Application;

class Template {
	const TEMPLATE_EXT = '.php';
	
	private $data = array();
	private $template;
	private $previousErrorReporting = null;
	
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
	
	public function render()
	{
		$this->previousErrorReporting = error_reporting();
		error_reporting($this->previousErrorReporting & ~E_NOTICE);
		
		extract($this->data, EXTR_OVERWRITE);
		require $this->template;
		
		error_reporting($this->previousErrorReporting);
		$this->previousErrorReporting = null;
	}
	
	private function url($action, $params = null)
	{
		$request = Application::get()->request;
		if(strpos($action, '/') === false) {
			$controller = strtolower($request->getController());
		} else {
			list($controller, $action) = explode('/', $action, 2);
		}
		$router = Application::get()->router;
		$reverse = $router->reverse($controller, $action, $params);
		if($reverse === false) {
			return '#';
		} else {
			return Config::get('app.prefix') . $reverse;
		}
	}
	
	private function config($id)
	{
		return new Wrapper(Config::get($id));
	}
	
	private function using($template, $params = null)
	{
		$templateFile = self::templateToPath($template);
		if(file_exists($templateFile) && is_readable($templateFile)) {
			if(is_array($params)) {
				$templ = new Template($templateFile, $params);
			} else {
				$templ = new Template($templateFile, $this->data);
			}
			return $templ->render();
		} else {
			throw new Exception("Could not use template '$template', file not found or unreadable");
		}
	}
	
	public static function templateToPath($template) 
	{
		$templateDir = Config::get('app.templates');
		if($templateDir[0] === '.') {
			$appLoc = Config::get('app.location');
			$templateDir = realpath($appLoc . $templateDir) . DIRECTORY_SEPARATOR;
		}
		return $templateDir . strtolower($template) . self::TEMPLATE_EXT;
	}
}