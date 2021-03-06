<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * This component defines minimal requirement for service components which are used in the workflow. They always have parent, 
 * theay are inside block and they can evaluate string in own context.
 * @author Tole
 *
 */
interface IServiceWorkflowComponent extends IBasicServiceComponent
{
	
	
	/**
	 * Returns parent component.
	 * @return \Convo\Core\Workflow\IWorkflowContainerComponent
	 */
	public function getParent();
	
	/**
	 * Sets parent component. This enables us to move and reuse compnents in a runtime.
	 * @param \Convo\Core\Workflow\IWorkflowContainerComponent $parent
	 */
	public function setParent( \Convo\Core\Workflow\IWorkflowContainerComponent $parent);
	
	
	/**
	 * Parse string and evaluates all found expressions in it. Expressions are evaulated in all available scopes and levels, from service up to this component.
	 * @param string $string
	 * @param array $context
	 * @return string
	 */
	public function evaluateString( $string, $context=[]);
	
	
	/**
	 * Searches for scope of the block which is currently executed executed
	 * @param string $scopeType
	 * @return \Convo\Core\Params\IServiceParams
	 */
	public function getBlockParams( $scopeType);
	
	
}