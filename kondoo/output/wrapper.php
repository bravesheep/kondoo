<?php

namespace Kondoo\Output;

class Wrapper {
	private $variable;
	
	public function __construct($variable)
	{
		$this->variable = $variable;
	}
	
	public function __get($variable)
	{
		if(is_object($this->variable) && isset($this->variable->$variable)) {
			return new Wrapper($this->variable->$variable);
		}
		return new Wrapper(null);
	}
	
	public function __call($function, array $arguments)
	{
		return $this->call($function, $arguments);
	}
	
	public function __isset($variable)
	{
		return isset($this->variable->$variable);
	}
	
	public function __toString()
	{
		if(is_null($this->variable)) {
			return "";
		}
		return htmlspecialchars((string)$this->variable);
	}
	
	public function url()
	{
		if(is_null($this->variable)) {
			return "";
		}
		return urlescape((string)$this->variable);
	}
	
	public function raw()
	{
		return $this->variable;
	}
	
	public function call($function, array $arguments)
	{
		if(is_object($this->variable) && is_callable(array($this->variable, $function))) {
			return new Wrapper(call_user_func_array($function, $arguments));
		}
		return new Wrapper(null);
	}
}