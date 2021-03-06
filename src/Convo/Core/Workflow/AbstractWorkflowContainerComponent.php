<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * Implements working with children as base class for concrete implementations.
 * @author Tole
 *
 */
abstract class AbstractWorkflowContainerComponent extends AbstractWorkflowComponent implements \Convo\Core\Workflow\IWorkflowContainerComponent
{
	
	/**
	 * @var \Convo\Core\Workflow\IBasicServiceComponent
	 */
	private $_children	=	array();
	
	/**
	 * Temporary as optional. Shouldd be obligate. 
	 * 
	 * @param array $properties
	 */
	public function __construct( $properties)
	{
		parent::__construct( $properties);
	}
	

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IWorkflowContainerComponent::addChild()
	 */
	public function addChild( \Convo\Core\Workflow\IBasicServiceComponent $child) {
		$this->_children[]	=	$child;
		if ( is_a( $child, '\Convo\Core\Workflow\IServiceWorkflowComponent')) {
		    /** @var \Convo\Core\Workflow\IServiceWorkflowComponent $child */
		    $child->setParent( $this);
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IWorkflowContainerComponent::getChildren()
	 */
	public function getChildren() {
		return $this->_children;
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IWorkflowContainerComponent::getAllChildren()
	 */
	public function getAllChildren()
	{
		$all = [];
        foreach ($this->getChildren() as $child) {
			$all[] = $child;
            
			if (is_a($child, '\Convo\Core\Workflow\IWorkflowContainerComponent'))
			{
                /** @var \Convo\Core\Workflow\IWorkflowContainerComponent $child */
                $all = array_merge($all, $child->getChildren());
            }
        }

        return $all;
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IWorkflowContainerComponent::findChildren()
	 */
	public function findChildren( $class) {
        $all  =   [];
        foreach ( $this->getChildren() as $child) {
            if ( is_a( $child, $class)) {
                $all[]   =   $child;
            }
            if ( is_a( $child, '\Convo\Core\Workflow\IWorkflowContainerComponent')) {
                /** @var \Convo\Core\Workflow\IWorkflowContainerComponent $child */
                $all   =   array_merge( $all, $child->findChildren( $class));
            }
        }
        return $all;
    }
    
    /**
     * @inheritDoc
     */
    public function evaluateString( $string, $context = [])
    {
        $own_params		=	$this->getService()->getAllComponentParams( $this);
        return parent::evaluateString( $string, array_merge( $own_params, $context));
    }
	
}