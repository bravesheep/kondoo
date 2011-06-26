<?php

namespace Kondoo;

use \Exception;

class Loader {
    const PHP_EXT = '.php';
    
    private static $loaders = array();
    private static $defaultLoader = null;
    
    /**
     * Register this loader as the default loader for all classes.
     */
    public static function register()
    {
        spl_autoload_register(__CLASS__ . '::load');
    }
    
    /**
     * 
     */
    public static function registerLoader($namespace, $loader)
    {
        if(!is_callable($loader)) {
            throw new Exception(_("Given loader is not a callback."));
        }
        
        if(!is_string($namespace) || strlen($namespace) < 1) {
            throw new Exception(_("Given namespace is not a valid namespace identifier."));
        }
        
        self::$loaders[$namespace] = $loader;
    }
    
    public static function registerDefaultLoader($loader)
    {
        if(!is_callable($loader)) {
            throw new Exception(_("Given loader is not a callback."));
        }
        self::$defaultLoader = $loader;
    }
    
    public static function load($className)
    {
        if(class_exists($className)) {
            return;
        }
        
        $callBack = null;
        foreach(self::$loaders as $namespace => $loader)
        {
            if(strpos($className, $namespace) === 0) {
                $callBack = $loader;
                break;
            }
        }
        
        if($callBack === null) {
            if(is_callable(self::$defaultLoader)) {
                $callBack = self::$defaultLoader;
            } else {
                $callBack = __CLASS__ . '::defaultLoad';
            }
        }
        
        try {
            call_user_func($callBack, $className);
        } catch(\Exception $e) {
        
        }
    }
    
    private static function defaultLoad($className)
    {
        if(class_exists($className)) {
            return;
        }
        
        $defaultAppLoc = dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . DIRECTORY_SEPARATOR . 
            'application';
        if(class_exists('\\Kondoo\\Options')) {
            $appLocation = \Kondoo\Options::get('app.location', $defaultAppLoc);
        } else {
            $appLocation = $defaultAppLoc;
        }
        
        if(class_exists('\\Kondoo\\Util\\PathUtil') && class_exists('\\Kondoo\\Options')) {
            $modelsLocation = \Kondoo\Util\PathUtil::expand(
                \Kondoo\Options::get('app.dir.models', './models')
            );
        } else {
            $modelsLocation = $appLocation . DIRECTORY_SEPARATOR . 'models';
        }
        
        
        $classPath = $className;
        if(DIRECTORY_SEPARATOR !== '\\') {
            $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $classPath);
        }
        $classPath = str_replace('_', DIRECTORY_SEPARATOR, $classPath);
        $extraPaths = array($appLocation, $modelsLocation);
        $paths = array_merge($extraPaths, explode(PATH_SEPARATOR, get_include_path()));
        foreach($paths as $path) {
            if($path[strlen($path) - 1] !== DIRECTORY_SEPARATOR) {
                $path .= DIRECTORY_SEPARATOR;
            }
            
            if(file_exists($path . $classPath . self::PHP_EXT)) {
                require_once $path . $classPath . self::PHP_EXT;
                if(class_exists($className)) {
                    return;
                }
            }
        }        
    }
}