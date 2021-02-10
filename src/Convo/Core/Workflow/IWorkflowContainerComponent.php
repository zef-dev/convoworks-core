<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Special variant of workflow components which are serving as containers for other components.
 * @author Tole
 *
 */
interface IWorkflowContainerComponent extends \Convo\Core\Workflow\IServiceWorkflowComponent
{
	
	/**
	 * Adds child component. Implementation should set parent to child if child is an IServiceWorkflowComponent
	 * @param \Convo\Core\Workflow\IBasicServiceComponent $child
	 */
	public function addChild(\Convo\Core\Workflow\IBasicServiceComponent $child);

	/**
	 * Returns direct component children.
	 * @return \Convo\Core\Workflow\IBasicServiceComponent[]
	 */
	public function getChildren();

	/**
	 * Returns ALL of this component's children, both direct descendants and its children's children.
	 * @return \Convo\Core\Workflow\IBasicServiceComponent[] 
	 */
	public function getAllChildren();

	/**
	 * Searches for a children components implementing the specific interface.
	 * @param string $class
	 * @return \Convo\Core\Workflow\IBasicServiceComponent[]
	 */
	public function findChildren( $class);
}