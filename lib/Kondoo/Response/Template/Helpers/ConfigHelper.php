<?php

namespace Kondoo\Response\Template\Helpers;

use Kondoo\Output\Template;
use Kondoo\Config;

class ConfigHelper implements Helper {
	public function printRaw()
	{
		return false;
	}
	
	public function call(Template $template, array $args)
	{
		if(count($args) < 1) {
			return "";
		} 
		return Config::get($args[0]);
	}
}