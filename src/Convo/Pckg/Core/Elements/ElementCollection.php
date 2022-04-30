<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


use Convo\Core\Workflow\IOptionalElement;

class ElementCollection extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	
	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_elements		=	array();
	
	public function __construct( $properties)
	{
		parent::__construct( $properties);
		
		$elements = $properties['elements'] ?? [];

		foreach ($elements as $element) {
			$this->_elements[]		=	$element;
			$this->addChild( $element);
		}
	}
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$this->_logger->debug('Reading ['.count( $this->_elements).']');
		
		foreach ($this->getElements() as $element) {
			/** @var $element \Convo\Core\Workflow\IConversationElement */
			$element->read( $request, $response);
		}
	}
	
	/**
	 * @return \Convo\Core\Workflow\IConversationElement[]
	 */
	public function getElements() {
	    $elements   =   $this->getService()->spreadElements( $this->_elements);
	    $filtered   =   [];
	    
	    foreach ( $elements as $element) {
	        if ( $element instanceof IOptionalElement) {
	            /* @var IOptionalElement $element*/
	            if ( $element->isEnabled()) {
	                $filtered[] = $element;
	            }
	            continue;
	        }
	        $filtered[] = $element;
	    }
	    
	    return $filtered;
	}
	
	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.count( $this->_elements).']';
	}
}