<?php

namespace Kondoo\Response\Template\Helpers;

use Kondoo\Output\Template;
use Kondoo\Application;
use Kondoo\Config;

class UrlHelper implements Helper {
	public function printRaw()
	{
		return true;
	}
	
	public function call(Template $template, array $args)
	{
		if(count($args) === 2) {
			list($action, $params) = $args;
		} else {
			list($action) = $args;
			$params = null;
		}
		
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
}