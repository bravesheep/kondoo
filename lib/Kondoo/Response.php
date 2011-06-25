<?php

namespace Kondoo;

use \Exception;
use \Kondoo\Response\Template\Wrapper;
use \Kondoo\Response\Template;
use \Kondoo\Listener\Event;
use \Kondoo\Response\Template\Helpers\Helper;

class Response {
	
	const TEMPLATE_EXT = '.php';
	
	private $showTemplate = true;
	
	private $template = null;
	
	private $headers = array();
	
	private $output = "";
	
	private $data = array();
	
	private $functions = array();
	
	public function __construct()
	{
	    $this->registerDefaults();
	}
	
	/**
	 * Output an header directly, or if late printing is enabled, send it after the output function
	 * is called.
	 * @param unknown_type $header
	 * @param unknown_type $content
	 * @throws Exception
	 */
	public function header($header, $content = null)
	{
		if(!headers_sent()) {
			if(!is_null($content)) {
				$header = "$header: $content";
			}
			
			if(Options::get('output.late', true)) {
				$this->headers[] = $header;
			} else {
				header($header);
			}		
		} else {
			throw new Exception("Cannot set header '$header', headers already sent");
		}
	}
	
	/**
	 * Print a variable directly, or if late printing is enabled, send it after the output function
	 * is called. The p function can be used as if it was sprintf.
	 * @param string $var
	 */
	public function put($var)
	{
		$argCount = func_num_args();
		if($argCount > 1) {
			$args = func_get_args();
			$out = call_user_func_array('sprintf', $args);
		} else {
			$out = $var;
		}
		
		if(Options::get('output.late', true)) {
			$this->output .= $out;
		} else {
			print $out;
		}
	}
	
	/**
	 * Translate the string given using gettext and send its output with the rest of the parameters 
	 * given to the print function.
	 * @param string $var The string to translate.
	 */
	public function translate($var)
	{
		$arguments = func_get_args();
		$arguments[0] = _($arguments[0]);
		call_user_func_array(array($this, 'put'), $arguments);
	}
	
	/**
	 * Set the template to a given template. The template name does not need to include an extension
	 * or the directory where templates are stored. After this function is called, templates will 
	 * not be auto-selected. 
	 * @param string $template
	 * @throws Exception
	 */
	public function template($template)
	{
		$template = strtolower($template);
		$templateFile = Template::templateToPath($template);
		if(file_exists($templateFile) && is_readable($templateFile)) {
			$this->template = $templateFile;
		} else {
			throw new Exception("Template '$template' does not exist or cannot be read");
		}
	}
	
	/**
	 * Output anything that isn't yet outputted to the browser. If the preventTemplate function
	 * wasn't called then a template view is parsed and outputted to the browser.
	 */
	public function output()
	{
		$data = array(
			'headers' => $this->headers,
			'content' => $this->output
		);
		Event::trigger('output', $this);
		foreach($data['headers'] as $header) {
			header($header);
		}
		print $data['content'];

		if($this->showTemplate) {
			if(is_null($this->template)) {
				$request = Application::get()->request;
				$this->template($request->getController() . DIRECTORY_SEPARATOR . 
					$request->getAction());
			}
			
			$templ = new Template($this->template, $this->data);
			$templ->render();
		}
	}
	
	/**
	 * Set the given variable to the given value.
	 * @param string $variable
	 * @param mixed $value
	 */
	public function set($variable, $value)
	{
		$this->data[$variable] = $value;
	}
	
	/**
	 * Retrieve the variable with the given name or return the default value if it doesn't exist.
	 * @param string $variable
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($variable, $default = null)
	{
		if(isset($this->data[$variable])) {
			return $this->data[$variable];
		}
		return $default;
	}
	
	/**
	 * Prevent the template from being called and outputted on calling the output function.
	 */
	public function preventTemplate()
	{
		$this->showTemplate = false;
	}
	
	/**
	 * Output some json to the browser directly, or if late printing is enabled: print it when the
	 * output function is called. This function calls preventTemplate so no templates will be
	 * outputted after this function is called.
	 * @param mixed $data
	 */
	public function json($data)
	{
		$this->preventTemplate();
		$this->header('Content-Type', 'application/json');
		
		$value = json_encode($data);
		if(Options::get('output.late', true)) {
			$this->output .= $value;
		} else {
			print $value;
		}
	}
	
	/**
	 * Register a function to be used in templates. If the first argument is an IFunction
	 * object then the name will be extracted from the name of the class. Otherwise name has to be
	 * a string and func has to be a callable or a class implementing IFunction
	 * @param mixed $name
	 * @param mixed $func
	 * @throws Exception
	 */
	public function register($name, $func = null) 
	{
		if($name instanceof Helper) {
			$func = $name;
			$name = strtolower(get_class($func));
			if(substr($name, strlen($name) - strlen('function')) === 'function') {
				$name = substr($name, 0, strlen($name) - strlen('function'));
			}
			$splitPos = strrpos($name, '\\');
			if($splitPos !== false) {
				$name = substr($name, $splitPos + 1);
			}
		} else {
			$name = strtolower($name);
		}
		
		if($func instanceof Helper || is_callable($func)) {
			$this->functions[$name] = $func;
		} else {
			throw new Exception('No callable or TemplateFunction given');
		}
	}
	
	/**
	 * Register some default functions that should be usable in any template.
	 */
	public function registerDefaults()
	{
		$this->register(new \Kondoo\Response\Template\Helpers\UrlHelper);
		$this->register(new \Kondoo\Response\Template\Helpers\ConfigHelper);
		$this->register(new \Kondoo\Response\Template\Helpers\UsingHelper);
	}
	
	/**
	 * Call a template function and return its ouput. Will return an empty
	 * string if the function could not be called.
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function call(Template $template, $name, array $args)
	{
		if(isset($this->functions[$name])) {
			$function = $this->functions[$name];
			if($function instanceof Helper) {
				if($function->printRaw()) {
					return $function->call($template, $args);
				} else {
					return new Wrapper($function->call($template, $args));
				}
			} else if(is_callable($function)) {
				return new Wrapper(call_user_func_array($function, $args));
			}
		}
		return "";
	}
}