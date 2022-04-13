<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

use Convo\Core\ComponentNotFoundException;

/**
 * Implements working with children as base class for concrete implementations.
 * @author Tole
 *
 */
abstract class AbstractWorkflowContainerComponent extends AbstractWorkflowComponent implements \Convo\Core\Workflow\IWorkflowContainerComponent
{
	
	/**
	 * @var \Convo\Core\Workflow\IBasicServiceComponent[]
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

			try {
                $parent = $child->getParent();
                
                if ($parent !== $this) {
                    $parent->removeChild($child);
                }
            } catch (ComponentNotFoundException $e) {
                $this->_logger->info($e->getMessage());
            } finally {
				$child->setParent( $this);
			}
		}
	}

	public function removeChild(IBasicServiceComponent $child)
	{
		$index_to_remove = -1;

		foreach ($this->_children as $index => $c) {
			if ($child->getId() === $c->getId()) {
				$index_to_remove = $index;
				break;
			}
		}

		if ($index_to_remove === -1) {
			throw new \Exception('Child element ['.$child.'] could not be found inside parent element ['.$this.']');
		}

		\array_splice($this->_children, $index_to_remove, 1);
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