<?php declare(strict_types=1);

namespace Convo\Core\Util;

abstract class StrUtil
{
    const EMAIL_PATTERN         =   '(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])';
    public static $ends = array('th','st','nd','rd','th','th','th','th','th','th');
	
	public static function stripTagsAndKeepNewLines( $value)
	{
		$value	=	str_ireplace( "</p>" , "\n", $value);
		$value	=	str_ireplace( "<p>" , "\n", $value);
		$value	=	str_ireplace( "<div>" , "\n", $value);
		$value	=	str_ireplace( "</div>" , "\n", $value);
		$value	=	str_ireplace( "<br />" , "\n", $value);
		$value	=	str_ireplace( "<br/>" , "\n", $value);
		$value	=	str_ireplace( "<br>" , "\n", $value);
		$value	=	strip_tags( $value);
		return trim($value);
	}
	
	public static function slugify($str) {
		$search = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
		$replace = array('s', 't', 's', 't', 's', 't', 's', 't', 'i', 'a', 'a', 'i', 'a', 'a', 'e', 'E');
		$str = str_ireplace($search, $replace, strtolower(trim($str)));
		$str = preg_replace("/[^\w\d\-\ ]/", '', $str);
		$str = str_replace(' ', '-', $str);
		return preg_replace("/\-{2,}/", '-', $str);
	}
	
	public static function appendNumber( $id) 
	{
		$parts	=	explode( '-', $id);
		
		if ( empty( $parts)) {
			return $id.'-1';
		}
		
		$last_index	=	count( $parts)-1;
		if ( !is_numeric( $parts[$last_index])) {
			return $id.'-1';
		}
		
		$parts[$last_index] =	$parts[$last_index] + 1;
		return implode( '-', $parts);
	}
	
	public static function trimSlashes( $string)
	{
		return self::removeStartingSlashes( self::removeTrailingSlashes($string));
	}
	
	public static function removeTrailingSlashes( $string)
	{
		if (self::endsWith( $string, '/'))
			return self::removeTrailingSlashes( substr( $string, 0, strlen($string) - 1));
		return $string;
	}
	
	public static function removeStartingSlashes( $string)
	{
		if (self::startsWith( $string, '/'))
			return self::removeStartingSlashes( substr( $string, 1));
		return $string;
	}
	
	
	public static function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
	
	public static function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}
	
		return (substr($haystack, -$length) === $needle);
	}
	
	public static function ordinalSuffix($number)
	{
		
		if (($number %100) >= 11 && ($number%100) <= 13)
		   $abbreviation = $number. 'th';
		else
		   $abbreviation = $number. self::$ends[$number % 10];
		
		return $abbreviation;
	}
	
	public static function parseBoolean( $value)
	{
		if ( empty(  $value)) {
			return false;
		}
			
		if ( is_bool( $value)) {
			return $value;
		}
		
		if ( is_numeric( $value) && $value > 0) {
			return true;
		}
			
		
		$value = strtolower( $value);
		if ( $value == 'true') {
			return true;
		}
			
		if ( $value == 'yes') {
			return true;
		}
			
		if ( $value == 'y') {
			return true;
		}
	
		return false;
		
		//if (!isset($value)) {
		//	return null;
		//}
		
		// return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	}

	public static function uuidV4()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	public static function concenateWithHumanTouch($items, $lastSep = ' or ')
	{
		if (empty( $items)) {
			return '';
		}

		if (count($items) == 1) {
			return array_pop($items);
		}

		$last = array_pop($items);
		return implode(', ', $items) . $lastSep . $last;
	}

    public static function getTextSimilarityPercentageBetweenTwoStrings($string1, $string2) {
        $percentage = 0;
        similar_text(strtolower($string1), strtolower($string2), $percentage);
        return $percentage;
    }
}
