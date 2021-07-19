<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


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
			$this->addElement($element);
		}
	}
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$this->_logger->debug('Reading ['.count( $this->_elements).']');
		
		foreach ($this->_elements as $element) {
			/** @var $element \Convo\Core\Workflow\IConversationElement */
			$element->read( $request, $response);
		}
	}
	
	public function addElement( \Convo\Core\Workflow\IConversationElement $element)
	{
		$this->_elements[]		=	$element;
		$this->addChild( $element);
	}
	
	/**
	 * @return \Convo\Core\Workflow\IConversationElement[]
	 */
	public function getElements() {
		return $this->_elements;
	}
	
	
	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.count( $this->_elements).']';
	}
}