<?php

namespace Kondoo\Util;

class ArrayUtil {
    public static function firstKey($arr, $func, $default = null)
    {
        foreach($arr as $key => $value) {
            if($func($key)) {
                return $key;
            }
        }
        return $default;
    }
}