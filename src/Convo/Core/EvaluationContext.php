<?php declare(strict_types=1);

namespace Convo\Core;

use Zef\Zel\Symfony\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

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

	public function evalArray( $array, $context=[], $useHashtagSign=false)
	{
		foreach ( $array as $key=>$val) {
			$array[$key] = $this->evalParam( $val, $context, $useHashtagSign);
		}

		return $array;
	}

	public function evalParam( $string, $context=[], $useHashtagSign=false)
	{
		$this->_logger->debug( 'Currently evaluating param ['.$string.']');
	
		$expressions = $this->_extractExpressions( $string, $useHashtagSign);
	
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

	public function evalString( $string, $context=[], $skipEmpty=false, $useHashtagSign=false)
	{
		$expressions	=	$this->_extractExpressions( $string, $useHashtagSign);

		foreach ( $expressions as $expression)
		{
			$this->_logger->debug( 'Currently handling expression ['.$expression.']');

			try {
				$value = $this->_expLang->evaluate( $expression, $context);
			} catch ( \Symfony\Component\ExpressionLanguage\SyntaxError $e) {
				throw $e;
			}

			$this->_logger->debug( 'Got value type ['.gettype( $value).'] for expression ['.$expression.']');

			if ( is_string( $value) || is_numeric( $value) || is_null( $value) || is_bool( $value)) {

			    if  ( !$skipEmpty || $skipEmpty && !empty( $value)) {
					$quot_expr = preg_quote($expression, '/');

					$this->_logger->debug('preg_quoted expression ['.$quot_expr.']');

					$pattern = '/\${\s*'.$quot_expr.'\s*}/';
			        if ($useHashtagSign === true) {
						$pattern = '/\#{\s*'.$quot_expr.'\s*}/';
					}

			        $string = preg_replace($pattern, strval($value), $string);
			    }

				if ( $string === '') {
				    if ( is_null( $value) || is_int( $value) || is_bool( $value) || is_float( $value)) {
				        $string =   $value;
				    }
				}
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

	private function _extractExpressions( $string, $useHashtagSign=false)
	{
		$string = (string) $string;

		$matches = [];
		$expressions = [];

		if ($useHashtagSign === true) {
			preg_match_all('/\#(\{(?:[^{}]+|(?1))+\})/', $string, $matches);
		} else {
			preg_match_all('/\$(\{(?:[^{}]+|(?1))+\})/', $string, $matches);
		}

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

	// UTIL
	public function __toString()
	{
		return get_class( $this).'';
	}
}
