<?php

namespace Kondoo;

class Router {
    
    private static $types = array(
        ':id'         => '(?P<id>\d+)',
        ':year'       => '(?P<year>\d{4})',
        ':month'      => '(?P<month>\d{1,2})',
        ':day'        => '(?P<day>\d{1,2})',
        ':slug'       => '(?P<slug>[\w-]+)',
        ':controller' => '(?P<controller>\w+)',
        ':action'     => '(?P<action>\w+)'
    );
    
    public function __construct($urls)
    {
        $this->urls = $urls;
        $this->names = array_keys($this::$types);
        $this->regs = array_values($this::$types);
    }
    
    public function map($url)
    {
        foreach($this->urls as $route => $target) {
            $regRoute = str_replace('\:', ':', preg_quote($route));
            $reg = str_replace($this->names, $this->regs, $regRoute);
            if(preg_match("#^$reg$#", $url, $result) === 1) {
                foreach($result as $key => $value) {
                    if(is_int($key)) {
                        unset($result[$key]);
                    }
                }
                $targetArray = explode('/', $target);
                if(!isset($result['controller'])) {
                    $result['controller'] = $targetArray[0];
                }
                if(!isset($result['action'])) {
                    $result['action'] = $targetArray[1];
                }
                return $result;
            }
        }
        return false;
    }
    
    
    public function reverseMap($params)
    {
        $dest = implode('/', array($params['controller'], $params['action']));
        foreach($this->urls as $route => $target) {
            $search = array('\:controller', '\:action');
            $reg = str_replace($search, '\w+', preg_quote($target));
            if(preg_match("#^$reg$#", $dest, $result) === 1) {
                foreach($params as $key => $value) {
                    $route = str_replace(':' . $key, $value, $route, $count);
                    if($count === 0) {
                        continue;
                    }
                }
                return $route;
            }
        }
        return false;
    }
}

// tests

ob_start();

$urls = array (
    '/article/:id.html'     => 'article/view',
    '/'                     => 'home/index',
    '/:controller'          => ':controller/index',
    '/:controller/:action'  => ':controller/:action',
);

$router = new Router($urls);

print_r($router->map('article/25/34.html'));
print_r($router->map('blaat/schep'));
print_r($router->map('woeikie'));

print_r($router->reverseMap(array('controller' => 'home', 'action' => 'index')));

$debug = ob_get_contents();
ob_end_clean();
print '<pre>' . htmlspecialchars($debug) . '</pre>';
