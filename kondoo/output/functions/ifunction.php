<?php

namespace Kondoo\Output\Functions;

use Kondoo\Output\Template;

interface IFunction {
	public function printRaw();
	public function call(Template $template, array $args);
}