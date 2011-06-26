<?php

namespace Kondoo\Util;

use \Kondoo\Options;

class PathUtil {
    public static function expand($path, $base = null)
    {
        if($base === null) {
            $base = Options::get('app.location', null);
        }
        
        if(is_string($path) && is_string($base)) {
            $baseLen = strlen($base);
            $pathLen = strlen($path);
            if($baseLen > 0 && $base[$baseLen - 1] === DIRECTORY_SEPARATOR) {
                $baseLen -= strlen(DIRECTORY_SEPARATOR);
                $base = substr($base, 0, $baseLen);
            }
            
            if($pathLen === 0) {
                return $base;
            } else if($path[0] === '.') {
                if($pathLen > 1 && $path[1] === '.') {
                    $path = substr($path, 2);
                    $base = explode(DIRECTORY_SEPARATOR, $base);
                    array_pop($base); array_pop($base);
                    $base = implode(DIRECTORY_SEPARATOR, $base);
                } else {
                    $path = substr($path, 1);
                }
                return $base . $path;
            }
        }
        return $path;
    }
}