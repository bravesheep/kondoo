<?php

namespace Kondoo;

use \Exception;
use \Kondoo\Resource\Provider as ResourceProvider;

/**
 * The router class uses routing information to transform a given url to a controller/action pair,
 * including parameters if any were found.
 */
class Router implements ResourceProvider {
	/**
	 * Start of a variable in an url pattern
	 * @var char
	 */
	const VARIABLE_START = ':';
	
	/**
	 * In case the end of a variable isn't natural, the IDENTIFIER_START and IDENTIFIER_END
	 * indicate the start and end of a variable.
	 * @var char
	 */
	const IDENTIFIER_START = '{';
	
	/**
	 * In case the end of a variable isn't natural, the IDENTIFIER_START and IDENTIFIER_END
	 * indicate the start and end of a variable.
	 * @var char
	 */
	const IDENTIFIER_END = '}';
    
	/**
	 * List of types to their regex match pattern. 
	 * @var array
	 */
    private static $types = array(
        'id'         => '\d+',
        'year'       => '\d{4}',
        'month'      => '(1[0-2])|(0?[1-9])',
        'day'        => '(0?[1-9])|([1-2][0-9])|(3[0-1])',
        'slug'       => '[\w-]+',
        'controller' => '\w+',
        'action'     => '\w+'
    );
    
    /**
     * List of url patterns to controller/action methods
     * @var array
     */
    private $routes;
    
    /**
     * Create a router with the given routes already placed.
     * @param array $routes Routes to add using @see{addRoutes}
     */
    public function __construct(array $routes = array()) 
    {
    	$this->routes = array();
    	$this->addRoutes($routes);
    }
    
    public function setOptions(array $options)
    {
        if(isset($options['routes'])) {
            $this->addRoutes($options['routes']);
        }
    }
    
    /**
     * Add a type to the list of types
     * @param string $type The name of the type to register
     * @param string $regex The regex this type translates to
     */
    public static function addType($type, $regex)
    {
    	self::$types[$type] = $regex;
    }
    
    /**
     * Add the given combination of url pattern and target controller/action combination to
     * the list of routes to be used for routing and reverse routing.
     * @param string $pattern The url to be matched against
     * @param string $target The target controller/action, in the format "$controller/$action".
     */
    public function addRoute($pattern, $target)
    {
    	$this->routes[$pattern] = $target;
    }
    
    /**
     * Add each of the routes in the given array to the list of routes to be used by the router.
     * @param array $routes
     */
    public function addRoutes(array $routes)
    {
    	foreach($routes as $pattern => $target) {
    		$this->addRoute($pattern, $target);
    	}
    }
    
    /**
     * Check if the given character is a valid character for a variable identifier inside an
     * url pattern.
     * @param char $char The character to check
     * @return boolean True if the character is a valid identifier, false otherwise.
     */
 	private static function isValidIdentifier($char) 
    {
    	return ctype_alpha($char) | $char == '_';
    }
    
    /**
     * Convert the given identifier to a regex pattern, to be used in the url pattern of a route.
     * This function uses the @see{$types} array for converting to regular expressions.
     * @param string $identifier The identifier to translate to a regex pattern.
     * @return string The identifier converted to a regular expression pattern.
     */
    private function regexForIdentifier($identifier) 
    {
    	$parts = explode('_', strtolower($identifier));
    	$regex = self::$types[$parts[0]];
    	return "(?P<$identifier>$regex)";
    }
    
    /**
     * Create a regex pattern from the given url pattern, to be used for routing requests.
     * @param string $pattern
     * @return string The complete regex pattern, ready to be used for matching urls
     */
    private function createPatternRegex($pattern)
    {
    	$patternData = str_split($pattern, 1);
    	$patternRegex = "";
    	for($i = 0; $i < count($patternData); $i++) {
    		if($patternData[$i] == self::VARIABLE_START) {
    			$identifier = "";
    			if($patternData[$i + 1] == self::IDENTIFIER_START) {
    				$i += 2;
    				while($patternData[$i] != self::IDENTIFIER_END) {
    					$identifier .= $patternData[++$i];
    				}
    			} else {
    				while(isset($patternData[$i + 1]) && 
    						self::isValidIdentifier($patternData[$i + 1])) {
    					$identifier .= $patternData[++$i];
    				}
    			}
    			$patternRegex .= $this->regexForIdentifier($identifier);
    		} else {
    			$patternRegex .= preg_quote($patternData[$i], '#');
    		}
    	}
    	return $patternRegex;
    }
    
    /**
     * Convert the given url to a combination of parameters, a controller and action name.
     * @param string $url The url to route to a controller/action.
     * @return array|boolean False if no route could be found, or the routing array if one was found
     */
    public function route($url)
    {
    	if(strlen($url) > 1 && $url[strlen($url) - 1] === '/') {
    		$url = substr($url, 0, strlen($url) - 1);
    	}
    	
    	foreach($this->routes as $pattern => $target)
    	{
    		$regex = $this->createPatternRegex($pattern);
    		if(preg_match("#^$regex$#", $url, $matches) === 1) {
    			list($controller, $action) = explode('/', $target);
    			$params = array();
    			foreach($matches as $key => $value) {
    				if(is_string($key) && $key !== 'controller' && $key !== 'action') {
    					$params[$key] = $value;
    				} else if($key === 'controller' && $controller === ':controller') {
    					$controller = $value;
    				} else if($key === 'action' && $action === ':action') {
    					$action = $value;
    				}
    			}
    			return array(
    				'controller' => $controller,
    				'action' => $action,
    				'params' => $params
    			);
    		}
    	}
    	throw new Exception("No route for '$url'");
    }
    
    /**
     * Given a set of parameters, reverse the routing to an url.
     * @param string $controller The controller for which an url mapping is to be found
     * @param string $action The action for which an url mapping is to be found
     * @param mixed $params Positions to be filled in, in an array. No array needed for single items
     */
    public function reverse($controller, $action, $params = null)
    {
        $dest = implode('/', array($controller, $action));
        foreach($this->routes as $route => $target) {
        	list($routeController, $routeAction) = explode('/', $target);
        	$controllerOk = $routeController === $controller || $routeController === ':controller';
        	$actionOk = $routeAction === $action || $routeAction === ':action';
        	if($controllerOk && $actionOk) {
		    	if(!is_array($params) && !is_null($params)) {
		    		$param = $params;
		    		$params = self::singleToArray($param, $route);
		    		if(count($params) === 0) {
		    			$params = $param;
		    			continue;
		    		}
		    	} else if(!is_array($params)) {
		    		$params = array();
		    	}
		    	
        		if(!self::canMatch($params, $route)) {
        			continue;
        		}
		    	
        		$params['controller'] = $controller;
        		$params['action'] = $action;
        		
        		foreach($params as $param => $value) {
        			$route = str_replace(array(":$param", ":\{$param\}"), $value, $route, $count);
        			
        			if($count !== 1 && $param !== 'action' && $param !== 'controller') {
        				continue 2;
        			}
        		}
        		
        		unset($params['controller']);
        		unset($params['action']);
        		
        		return $route;
        	}
        }
        return false;
    }
    
    private static function singleToArray($param, $route)
    {
    	$params = array();
    	$matches = array();
    	if(preg_match_all('#:([a-z_]+)#i', $route, $matches) > 0) {
    		$matches = array_filter($matches[1], function($item) {
    			return $item !== 'action' && $item !== 'controller';
    		});
    		if(count($matches) === 1) {
    			list($match) = $matches;
    			$params[$match] = $param; 
    		}
    	}
    	return $params;
    }
    
    private static function canMatch($params, $route) 
    {
    	$matches = array();
    	if(preg_match_all('#:([a-z_]+)#i', $route, $matches) > 0) {
    		foreach($matches[1] as $match) {
    			if(!isset($params[$match])) {
    				return false;
    			}
    		}
    	}
    	return true;
    }
}

