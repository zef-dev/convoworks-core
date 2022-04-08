<?php declare(strict_types=1);
namespace Convo\Pckg\Core\Elements;

class GeneratorElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement, \Iterator
{
	/**
	 * @var \Convo\Core\Workflow\IConversationElement
	 */
	private $_element;

	private $_dataCollection;
	
	private $_item;
	
	/**
	 * @var \Iterator
	 */
	private $_iterator;


	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_dataCollection = $properties['data_collection'];
		$this->_item = $properties['item'];

		if ( $properties['element']) {
		    $this->_element   =   $properties['element'];
		}
	}
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
	}
	
	// ITERATOR
    public function next()
    {
        return $this->_iterator->next();
    }

    public function valid()
    {
        return $this->_iterator->valid();
    }

    public function current()
    {
        $slot_name    =   $this->evaluateString( $this->_item);
        $item         =   $this->_iterator->current();
        
        $item   =   new GeneratorItem( $this->_element, $slot_name, [
            'value' => $item,
            'index' => $this->_iterator->key(),
            'natural' => $this->_iterator->key() + 1,
            'first' => $this->_iterator->key() === 0,
        ]);
        $item->setService( $this->getService());
        $this->addChild( $item);
        return $item;
    }

    public function rewind()
    {
        $items = $this->evaluateString( $this->_dataCollection);
        
        if ( !is_array($items) && !$items instanceof \Iterator && !$items instanceof \IteratorAggregate) {
            throw new \Exception( 'Excepted to find iterable for ['.$this->_dataCollection.'] got ['.gettype( $items).']');
        }
        
        if ( is_array( $items)) {
            $this->_iterator = new \ArrayIterator($items);
        } else if ($items instanceof \IteratorAggregate) {
            $this->_iterator = $items->getIterator();
        } else {
            $this->_iterator = $items;
        }
    }

    public function key()
    {
        return $this->_iterator->key();
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.count( $this->_elements).']';
    }

}