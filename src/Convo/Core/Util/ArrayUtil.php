<?php declare(strict_types=1);

namespace Convo\Core\Util;

abstract class ArrayUtil
{
    /**
     * Will walk through all array values recursively and apply function to each value (not array values)
     *
     * @param array $arr
     * @param callback $function
     * @return array
     */
    public static function arrayWalk( $arr, $function) {
        $ret = [];

        if ( self::isArrayIndexed( $arr)) {
            foreach ( $arr as $val) {
                if ( is_array( $val)) {
                    $ret[] = self::arrayWalk( $val, $function);
                } else {
                    $ret[] = $function( $val);
                }
            }
        } else {
            foreach ( $arr as $key=>$val) {
                if ( is_array( $val)) {
                    $ret[$key] = self::arrayWalk( $val, $function);
                } else {
                    $ret[$key] = $function( $val);
                }
            }
        }

        return $ret;
    }

    /**
     * Returns true if array is indexed (starting with index 0). Will return true for empty arrays too.
     * @param array $arr
     * @return boolean
     */
    public static function isArrayIndexed( $arr)
    {
        if ( empty( $arr)) {
            return true;
        }
        $keys =   array_keys( $arr);
        foreach ( $keys as $key) {
            if ( $key === 0) {
                return true;
            }
            return false;
        }
    }

	public static function areArraysEqual( $arr1, $arr2, $igonerOrder=true)
	{
	    if ( empty( $arr1) && empty( $arr2)) {
	        return true;
	    }
	    if ( count( $arr1) != count( $arr2)) {
	        return false;
	    }

	    $keys1 =   array_keys( $arr1);
	    $keys2 =   array_keys( $arr2);

	    if ( $igonerOrder) {
	        sort( $keys1);
	        sort( $keys2);
	    }

	    for ( $i=0; $i<count($keys1); $i++) {
	        $key1  =   $keys1[$i];
	        $key2  =   $keys2[$i];
	        $val1  =   $arr1[$key1];
	        $val2  =   $arr2[$key2];
	        if ( gettype( $val1) !== gettype( $val2)) {
	            return false;
	        }
	        if ( is_array( $val1) && !self::areArraysEqual( $val1, $val2, $igonerOrder)) {
	           return false;
	        }
	        if ( $val1 !== $val2) {
	            return false;
	        }
	    }
	    return true;
	}

	public static function isComplexKey($key)
    {
        return strpos($key, '.') !== false || strpos($key, '[') !== false;
    }

    public static function getRootOfKey($key)
    {
        if (!self::isComplexKey($key)) {
            return $key;
        }

        $periodpos = strpos($key, '.');
        $bracketpos = strpos($key, '[');

        $len = min($periodpos, $bracketpos);
        if ($periodpos === false) {
            $len = $bracketpos;
        }

        if ($bracketpos === false) {
            $len = $periodpos;
        }

        return substr($key, 0, $len);
    }

    public static function setDeepObject($key, $value, $base = [])
    {
        // replace [] with .
        $key = preg_replace('/[\[\]]/', '.', $key);
        // remove consecutive . (.. => .)
        $key = preg_replace('/(\.)\1+/','$1', $key);
        // remove trailing .
        $key = preg_replace('/(\.)$/', '', $key);

        $parts = explode('.', $key);

        array_shift($parts);

        $array = $base;
        $current = &$array;

        foreach($parts as $part)
        {
            if (is_numeric($part)) {
                $part = intval($part);
            }

            $part = str_replace('"', '', $part);

            $current = &$current[$part];
        }
        $current = $value;
        return $array;
    }
}
