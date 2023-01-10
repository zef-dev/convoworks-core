<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Core\Workflow\AbstractWorkflowComponent;

class StriposFilter extends AbstractWorkflowComponent implements IPlainTextFilter
{
    /**
     * @var \Convo\Core\Workflow\DefaultFilterResult
     */
    private $_filterResult;

    private $_search;
    private $_startsWith;
    private $_slotName;
    private $_slotValue;

    public function __construct($config = [])
    {
        parent::__construct( $config);

        $this->_filterResult = new DefaultFilterResult();

        $this->_search = $config['search'];
        $this->_startsWith = $config['starts_with'] ?? null;
        $this->_slotName = $config['slot_name'];
        $this->_slotValue = $config['slot_value'] ?? null;
    }

    public function filter(\Convo\Core\Workflow\IConvoRequest $request)
    {
        $text = $request->getText();
        $search = $this->evaluateString( $this->_search);
        $starts = $this->evaluateString( $this->_startsWith);
        $value = $this->evaluateString( $this->_slotValue);

        $match = false;
        if ( $starts) {
            if ( stripos( $text, $search) === 0) {
                $this->_logger->info( 'Matched starts with ['.$search.']['.$text.']');
                $match = true;
            }
        } else if ( stripos( $text, $search) !== false) {
            $this->_logger->info( 'Matched stripos with ['.$search.']['.$text.']');
            $match = true;
        }
        
        if ( $match) {
            $value = $value ?? $search;
            $name = $this->_slotName ?? 'match';
            $this->_filterResult->setSlotValue( $name, $value);
        }
    }

    public function getFilterResult()
    {
        return $this->_filterResult;
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString()."[{$this->_search}][{$this->_startsWith}]";
    }
}