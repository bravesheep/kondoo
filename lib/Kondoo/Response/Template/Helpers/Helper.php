<?php

namespace Kondoo\Response\Template\Helpers;

use Kondoo\Output\Template;

interface Helper {
	public function printRaw();
	public function call(Template $template, array $args);
}