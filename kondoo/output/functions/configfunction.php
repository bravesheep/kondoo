<?php

namespace Kondoo\Output\Functions;

use Kondoo\Output\Template;
use Kondoo\Config;

class ConfigFunction implements IFunction {
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