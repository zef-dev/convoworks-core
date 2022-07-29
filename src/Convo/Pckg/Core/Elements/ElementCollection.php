<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IConversationElement;

class ElementCollection extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements IConversationElement
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
		$this->_logger->info('Reading ['.count( $this->_elements).']');
		
		foreach ($this->getElements() as $i=>$element) {
		    $this->_logger->info('Reading element at index ['.$i.']');
			/** @var $element \Convo\Core\Workflow\IConversationElement */
			$element->read( $request, $response);
		}
	}
	
	/**
	 * @return \Convo\Core\Workflow\IConversationElement[]
	 */
	public function getElements() {
	    return $this->getService()->spreadElements( $this->_elements);
	}
	
	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.count( $this->_elements).']';
	}
}