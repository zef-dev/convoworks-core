<?php declare(strict_types=1);

namespace Convo\Core\Expression;

use Zef\Zel\Symfony\ExpressionLanguage;

class EvaluationContext
{

	/**
	 * Expression language
	 *
	 * @var ExpressionLanguage
	 */
	private $_expLang;

	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	public function __construct( $logger, ExpressionFunctionProviderInterface $functionProvider)
	{
		$this->_logger	=	$logger;

		$this->_expLang	=	new ExpressionLanguage();
		$this->_expLang->registerProvider( $functionProvider);
	}

	public function evalArray( $array, $context=[])
	{
		foreach ( $array as $key=>$val) {
			$array[$key] = $this->evalParam( $val, $context);
		}

		return $array;
	}

	public function evalParam( $string, $context=[])
	{
		$this->_logger->debug( 'Currently evaluating param ['.$string.']');
	
		$expressions = $this->_extractExpressions( $string);
	
		if (empty($expressions)) {
			return $string;
		}
	
		$value	= $this->_expLang->evaluate( $expressions[0], $context);
// 			$this->_logger->debug( 'Got value ['.print_r( $value, true).']');
		if ( is_a( $value, 'Zef\Zel\IValueAdapter')) {
			$this->_logger->debug( 'Got IValueAdapter value');
			return $value->get();
		}

		$this->_logger->debug( 'Got value of type ['.gettype($value).']');

		return $value;
	}

	public function evalString( $string, $context=[], $skipEmpty=false)
	{
	    if ( !is_string( $string)) {
	        $this->_logger->info( 'Returning raw value for ['.gettype( $string).']');
	        return $string;
	    }
	    $this->_logger->debug( 'Evaluating string ['.$string.']');
	    
		$expressions	=	$this->_extractExpressions( $string);

		if ( count( $expressions) === 1) 
		{
		    $expression = $expressions[0];
		    $expression_full = '${'.$expression.'}';
		    if ( $expression_full === $string) 
		    {
		        try {
		            $value = $this->_expLang->evaluate( $expression, $context);
		            $this->_logger->debug( 'Got value type ['.gettype( $value).'] for a single expression string ['.$expression.']');
		            if ( is_a( $value, 'Zef\Zel\IValueAdapter')) {
		                return $value->get();
		            }
		            return $value;
		        } catch ( \Symfony\Component\ExpressionLanguage\SyntaxError $e) {
		            throw $e;
		        }
		    }
		}
		
		foreach ( $expressions as $expression)
		{
			try {
				$value = $this->_expLang->evaluate( $expression, $context);
			} catch ( \Symfony\Component\ExpressionLanguage\SyntaxError $e) {
				throw $e;
			}

			$this->_logger->debug( 'Got value type ['.gettype( $value).'] for expression ['.$expression.']');

			if ( is_string( $value) || is_numeric( $value) || is_null( $value) || is_bool( $value)) {

			    if  ( !$skipEmpty || $skipEmpty && !empty( $value)) {
					$quot_expr = preg_quote($expression, '/');

// 					$this->_logger->debug('preg_quoted expression ['.$quot_expr.']');

			        $pattern = '/\${\s*'.$quot_expr.'\s*}/';
			        $string = preg_replace( $pattern, strval( $value), $string);
			    }

// 				if ( $string === '') {
// 				    if ( is_null( $value) || is_int( $value) || is_float( $value)) {
// 				        $string =   $value;
// 				    }
// 				}
            } else {
				// not parsing, single value get
				if ( is_a( $value, 'Zef\Zel\IValueAdapter')) {
					return $value->get();
				}

				return $value;
			}
		}

		return $string;
	}

	private function _extractExpressions( $string)
	{
		$string = (string) $string;

		$matches = [];
		$expressions = [];

		preg_match_all('/\$(\{(?:[^{}]+|(?1))+\})/', $string, $matches);

		if (isset($matches[1])) {
			foreach ($matches[1] as $match) {
				$expression = trim($match);

				if (strpos($expression, "{") === 0) {
					$expression = substr_replace($expression, "", 0, 1);
				}

				$last_char = strlen($expression) - 1;

				if (strpos($expression, "}", $last_char) === $last_char) {
					$expression = substr_replace($expression, "", $last_char, 1);
				}

				$this->_logger->debug( 'Found expression ['.$match.']');
				$expressions[] = $expression;
			}
		}

		return $expressions;
	}

	/**
	 * @todo This is questionable method. It might be that it only requires strval().
	 * @param string $value
	 * @return string|number
	 * @deprecated 
	 */
	private function _castToAppropriateValueType($value)
	{
		$this->_logger->debug('Got value to cast ['.$value.']');

		if (is_null($value)) {
			$this->_logger->debug('Value ['.$value.'] is null.');
			return '';
		}

		if (is_numeric($value)) {
			$value += 0;

			if (is_float($value)) {
				$this->_logger->debug('Value ['.$value.'] is a float.');
				return floatval($value);
			}
	
			if (is_int($value)) {
				$this->_logger->debug('Value ['.$value.'] is an int.');
				return intval($value);
			}
		}

		$this->_logger->debug('Returning default string value for ['.$value.']');
		return strval($value);
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'';
	}
}
