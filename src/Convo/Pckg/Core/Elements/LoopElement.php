<?php declare(strict_types=1);
namespace Convo\Pckg\Core\Elements;

class LoopElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_elements = array();

	/** @var array */
	private $_dataCollection;
	private $_item;

	private $_offset;
	private $_limit;

	public function __construct($properties)
	{
		parent::__construct($properties);

		$this->_dataCollection = $properties['data_collection'];
		$this->_item = $properties['item'];

		$this->_offset = $properties['offset'];
		$this->_limit = $properties['limit'];

		if ( isset($properties['elements'])) {
			foreach ( $properties['elements'] as $element) {
				$this->addElement( $element);
			}
		}
	}
	
	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
        $items = $this->evaluateString($this->_dataCollection);
        
        if ( !is_array( $items)) {
            throw new \Exception( 'Excepted to find array for ['.$this->_dataCollection.'] got ['.gettype( $items).']');
        }
        
        $slot_name = $this->evaluateString($this->_item);

        $scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
		$params = $this->getService()->getComponentParams( $scope_type, $this);
		
		$start = 0;
		$end = count($items);

		if ($this->_offset !== null) {
			if ($this->_offset > $end || $this->_offset < 0) {
				$this->_logger->warning('Offset ['.$this->_offset.'] falls outside the range ['.$start.', '.$end.']. Starting from 0.');
			} else {
				$start = $this->_offset;
			}
		}

		if ($this->_limit !== null) {
			$limit = abs($this->_limit);
			$end = min(($start + $limit), count($items));
		}

		for ($i = $start; $i < $end; ++$i) {
			$val = $items[$i];

			$params->setServiceParam($slot_name, [
				'value' => $val,
				'index' => $i,
				'natural' => $i + 1,
				'first' => $i === $start,
				'last' => $i === $end
			]);

			foreach ($this->_elements as $element) {
				$element->read($request, $response);
			}
		}
	}
	
	public function addElement( \Convo\Core\Workflow\IConversationElement $element)
	{
		$this->_elements[] = $element;
		$this->addChild($element);
	}
	
	/**
	 * @return \Convo\Core\Workflow\IConversationElement[]
	 */
	public function getElements() {
		return $this->_elements;
	}

    public function evaluateString( $string, $context=[]) {
        $own_params	= $this->getService()->getAllComponentParams( $this);
        return parent::evaluateString( $string, array_merge( $own_params, $context));
    }
	
	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.count( $this->_elements).']';
	}
}