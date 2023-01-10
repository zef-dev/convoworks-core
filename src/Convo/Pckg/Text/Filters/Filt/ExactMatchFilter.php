<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Core\Workflow\AbstractWorkflowComponent;

class ExactMatchFilter extends AbstractWorkflowComponent implements IPlainTextFilter
{
    /**
     * @var \Convo\Core\Workflow\DefaultFilterResult
     */
    private $_filterResult;

    private $_search;
    private $_slotName;
    private $_slotValue;

    public function __construct( $config = [])
    {
        parent::__construct( $config);

        $this->_filterResult = new DefaultFilterResult();

        $this->_search = $config['search'];
        $this->_slotName = $config['slot_name'];
        $this->_slotValue = $config['slot_value'] ?? null;
    }

    public function filter( \Convo\Core\Workflow\IConvoRequest $request)
    {
        $text = trim( $request->getText());
        $text = strtolower( $text);
        $search = strtolower( trim( $this->evaluateString( $this->_search)));
        
        if ( $search === $text) 
        {
            $value = $this->_slotValue ?? $text;
            $this->_filterResult->setSlotValue( $this->_slotName, $value);
        }
    }

    public function getFilterResult()
    {
        return $this->_filterResult;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString()."[{$this->_search}]";
    }
}