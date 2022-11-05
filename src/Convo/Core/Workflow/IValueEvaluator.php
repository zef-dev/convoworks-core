<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * @author Tole
 * Evaluates string and returns evaluated value.
 */
interface IValueEvaluator
{
	
	/**
	 * Parse string and evaluates all found expressions in it. Expressions are evaulated in all available scopes and levels, from service up to this component.
	 * @param string $string
	 * @param array $context
	 * @return string
	 */
	public function evaluateString( $string, $context=[]);
	
}