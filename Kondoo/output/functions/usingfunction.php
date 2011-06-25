<?php

namespace Kondoo\Output\Functions;

use Kondoo\Output\Template;

class UsingFunction implements IFunction {
	public function printRaw()
	{
		return true;
	}
	
	public function call(Template $template, array $params = array()) 
	{
		if(count($params) == 1) {
			list($templateName) = $params;
			$templateData = null;
		} else if(count($params) == 2) {
			list($templateName, $templateData) = $params;
		} else {
			return "";
		}
		
		$templateName = strtolower($templateName);
		$templateFile = Template::templateToPath($templateName);
		if(file_exists($templateFile) && is_readable($templateFile)) {
			if(is_array($templateData)) {
				$templ = new Template($templateFile, $templateData);
			} else {
				$templ = new Template($templateFile, $template->getData());
			}
			ob_start();
			$templ->render();
			return ob_get_clean();
		} else {
			throw new Exception("Could not use template '$templateName', file not found or unreadable");
		}
	}
}