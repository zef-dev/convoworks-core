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

	public function evalArray( $array, $context=[])
	{
		foreach ( $array as $key=>$val) {
			$array[$key] = $this->evalParam( $val, $context);
		}

		return $array;
	}

	public function evalParam( $string, $context=[])
	{
		try {
			$this->_logger->debug( 'Currently evaluating param ['.$string.']');
			$expressions	=	$this->_extractExpressions( $string);
			if ( empty( $expressions)) {
				return $string;
			}
			$value	= $this->_expLang->evaluate( $expressions[0], $context);
// 			$this->_logger->debug( 'Got value ['.print_r( $value, true).']');
			if ( is_a( $value, 'Convo\Core\Evaluation\IValueAdapter')) {
			    $this->_logger->debug( 'Got IValueAdapter value');
				return $value->get();
			}

			$this->_logger->debug( 'Got value of type ['.gettype($value).']');

			return $value;
		} catch ( \Symfony\Component\ExpressionLanguage\SyntaxError $e) {
			throw $e;
		}
	}

	public function evalString( $string, $context=[], $skipEmpty=false)
	{
		$expressions	=	$this->_extractExpressions( $string);

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
			        $string = preg_replace($pattern, strval($value), $string);
			    }

				if ( $string === '') {
				    if ( is_null( $value) || is_int( $value) || is_bool( $value) || is_float( $value)) {
				        $string =   $value;
				    }
				}
			} else {
				// not parsing, single value get
				if ( is_a( $value, 'Convo\Core\Evaluation\IValueAdapter')) {
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

		preg_match_all('/\${(.*?)}(?=[\s\w\-_\/\\;,.?!()|]|$)/', $string, $matches);

		if (isset($matches[1])) {
			foreach ($matches[1] as $match) {
				$expression = trim($match);
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
