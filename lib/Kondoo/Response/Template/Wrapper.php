<?php

namespace Kondoo\Response\Template;

use \ArrayAccess;
use \Iterator;
use \DateTime;
use \DateTimeZone;

class Wrapper implements ArrayAccess, Iterator {
	private $variable;
	private $nextCalled;
	private $index;
	private $arrayIndex;
	
	public function __construct($variable, $num = 0)
	{
		$this->variable = $variable;
		$this->nextCalled = false;
		$this->index = $num;
		$this->arrayIndex = 0;
	}
	
	public function __get($variable)
	{
		if(is_object($this->variable) && isset($this->variable->$variable)) {
			return new static($this->variable->$variable);
		} else if(is_array($this->variable) && isset($this->variable[$variable])) {
			return new static($this->variable[$variable]);
		}
		return new static(null);
	}
	
	public function __set($variable, $value)
	{
		if(is_object($this->variable) && is_string($variable)) {
			$this->variable->$variable = $value;
		} else if(is_array($this->variable)) {
			$this->variable[$variable] = $value;
		}
	}
	
	public function __call($function, array $arguments)
	{
		return $this->call($function, $arguments);
	}
	
	public function __isset($variable)
	{
		return (is_object($this->variable) && isset($this->variable->$variable)) ||
				(is_array($this->variable) && isset($this->variable[$variable]));
	}
	
	public function __toString()
	{
		if(is_null($this->variable)) {
			return '';
		}
		return htmlspecialchars((string)$this->variable);
	}
	
	public function url()
	{
		if(is_null($this->variable)) {
			return '';
		}
		return urlescape((string)$this->variable);
	}
	
	public function raw()
	{
		return $this->variable;
	}
	
	public function num() 
	{
		return $this->index + 1;
	}
	
	public function index()
	{
		return $this->index;
	}
	
	public function translate()
	{
		$out = _($this->variable);
		return new Wrapper(vsprintf($out, func_get_args()));
	}
	/**
	 * Format a string, a number or a date. The required arguments depend on the type
	 * of variable:
	 * - Integers and one argument: the variable is treated as timestamp and
	 * the argument is used as format specifier as used with strftime {@link date()}
	 * - Integers and three arguments: the number_format function is called where
	 * the first argument is the number of decimals, the second the decimal separator
	 * and the third argument the thousands separator.
	 * - DateTime objects: first argument is format as used by strftime {@link date()}
	 */
	public function format()
	{
		if(is_int($this->variable)) {
			$args = func_get_args();
			if(count($args) === 1 || $this->variable instanceof DateTime) {
				return $this->date(func_get_arg(0));
			} else if(count($args) === 3) {
				return new static(number_format($this->variable, $args[0], 
						$args[1], $args[2]));
			}
		}
		return new static(vsprintf((string)$this->variable, func_get_args()));
	}
	
	/**
	 * Format a date according to the given format. The format is as used by
	 * strftime().
	 * @param string $format
	 */
	public function date($format)
	{
		$item = $this->variable;
		if(is_string($this->variable)) {
			$item = new DateTime($this->variable);
		}
		
		if($item instanceof DateTime) {
			// $tz = $this->variable->getTimezone();
			$this->variable->setTimezone(new DateTimeZone(date_default_timezone_get()));
			$tmp = new static(strftime(func_get_arg(0), $this->variable->format('U')));
			// BUG: cannot reliably reset timezone to previous, see PHP bug 45543
			// $this->variable->setTimezone($tz);
			return $tmp;
		} else if(is_int($item)) {
			return new static(strftime($format, $item));
		}
		return new static(strftime($format, time()));
	}
	
	/**
	 * If the variable is an object, use this function to call a method on the variable.
	 * The {@link __call()} magic method is also defined, but some functions may be
	 * uncallable because they are already defined in the wrapper.
	 * @param string $function Name of the function to call
	 * @param array $arguments Array of arguments with which the function should be called.
	 * @return Kondoo\Output\Wrapper The output of the function inside a Wrapper.
	 */
	public function call($function, array $arguments)
	{
		if(is_object($this->variable) && is_callable(array($this->variable, $function))) {
			return new static(call_user_func_array($function, $arguments));
		}
		return new static(null);
	}
	
	/**
	 * Return the current element as identified by the internal pointer
	 * @return Kondoo\Output\Wrapper
	 */
	public function current()
	{
		if(is_array($this->variable)) {
			return new static(current($this->variable), $this->arrayIndex);
		}
		return $this;
	}
	
	/**
	 * Return the key of the current element as identified by the internal pointer
	 * @return Kondoo\Output\Wrapper
	 */
	public function key()
	{
		if(is_array($this->variable)) {
			return new static(key($this->variable), $this->arrayIndex);
		}
		return new static(0);
	}
	
	/**
	 * Increment the internal pointer and return this next element inside a wrapper.
	 * @return Kondoo\Output\Wrapper
	 */
	public function next()
	{
		if(is_array($this->variable)) {
			$this->arrayIndex++;
			return new static(next($this->variable), $this->arrayIndex);
		}
		$this->nextCalled = true;
		return new static(false);
	}
	
	/**
	 * Reset the internal pointer to the begin of the array.
	 */
	public function rewind()
	{
		if(is_array($this->variable)) {
			$this->arrayIndex = 0;
			reset($this->variable);
		}
		$this->nextCalled = false;
	}
	
	/**
	 * Return true if the current element is set and false if it isn't
	 * @return boolean
	 */
	public function valid()
	{
		if(is_array($this->variable)) {
			return isset($this->variable[key($this->variable)]);
		}
		return !$this->nextCalled;
	}
	
	/**
	 * Return true if the given element exists in the internal array. If the internal
	 * variable isn't an array but an object, the offset is looked up as if it were
	 * a member of the object. In all other cases false is returned.
	 * @param mixed $offset Identifier of the element to check
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return (is_array($this->variable) && isset($this->variable[$offset])) ||
				(is_object($this->variable) && $this->__isset($offset));
	}
	
	/**
	 * Return the value of the element at the given offset in the internal array. If
	 * the internal variable isn't an array but the offset is zero, the internal variable
	 * is returned. If the internal variable is an object and offset is a string, the 
	 * offset is used as if it was the identifier of an object member.
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		if(is_array($this->variable) && isset($this->variable[$offset])) {
			if(is_int($offset)) {
				return new static($this->variable[$offset], $offset);
			}
			return new static($this->variable[$offset]);
		} else if(is_object($this->variable) && is_string($offset)) {
			if($this->__isset($offset)) {
				return new static($this->variable->$offset);
			}
		} else if(!is_array($this->variable) && $offset === 0) {
			return $this;
		}
		return new static(null);
	}
	
	public function offsetSet($offset, $value)
	{
		if(is_array($this->variable)) {
			if(is_null($offset)) {
				$this->variable[] = $value;
			} else {
				$this->variable[$offset] = $value;
			}
		} else if(is_object($this->variable) && is_string($offset)) {
			$this->variable->$offset = $value;
		}
	}
	
	public function offsetUnset($offset)
	{
		if(is_array($this->variable) && isset($this->variable[$offset])) {
			unset($this->variable[$offset]);
		} else if(is_object($this->variable) && $this->__isset($offset)) {
			unset($this->variable->$offset);
		}
	}
}