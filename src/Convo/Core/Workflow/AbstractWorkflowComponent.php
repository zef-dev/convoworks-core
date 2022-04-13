<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Basic implementation of workflow infrastructure - working with parents and evaluating strings.
 * @author Tole
 *
 */
abstract class AbstractWorkflowComponent extends AbstractBasicComponent implements \Convo\Core\Workflow\IServiceWorkflowComponent
{
	
	/**
	 * @var \Convo\Core\Workflow\IWorkflowContainerComponent
	 */
	private $_parent;

	
	public function __construct( $properties)
	{
		parent::__construct( $properties);
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IServiceWorkflowComponent::getParent()
	 */
	public function getParent() {
		if ( !$this->_parent) {
			throw new \Convo\Core\ComponentNotFoundException( 'Parent not set in ['.get_class($this).']['.$this->getId().']');
		}
		return $this->_parent;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IServiceWorkflowComponent::setParent()
	 */
	public function setParent( \Convo\Core\Workflow\IWorkflowContainerComponent $parent) {
		$this->_parent	=	$parent;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IServiceWorkflowComponent::evaluateString()
	 */
	public function evaluateString( $string, $context=[])
	{
		return $this->getParent()->evaluateString( $string, $context);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IServiceWorkflowComponent::getBlockParams()
	 */
	public function getBlockParams( $scopeType) {
		return $this->getParent()->getBlockParams( $scopeType);
	}
	
}