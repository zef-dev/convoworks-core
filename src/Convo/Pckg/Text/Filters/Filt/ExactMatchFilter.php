<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

use Convo\Core\Workflow\DefaultFilterResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class ExactMatchFilter implements IPlainTextFilter, LoggerAwareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Workflow\DefaultFilterResult
     */
    private $_filterResult;

    private $_search;
    private $_slotName;
    private $_slotValue;

    public function __construct($config = [])
    {
        $this->_logger = new NullLogger();

        $this->_filterResult = new DefaultFilterResult();

        $this->_search = $config['search'];
        $this->_slotName = $config['slot_name'];
        $this->_slotValue = $config['slot_value'] ?? null;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function filter( \Convo\Core\Workflow\IConvoRequest $request)
    {
        $text = trim( $request->getText());
        $text = strtolower( $text);
        $search = strtolower( trim( $this->_search));
        
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
        return get_class($this)."[{$this->_search}]";
    }
}