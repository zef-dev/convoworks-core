<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

use Convo\Core\Workflow\DefaultFilterResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class RegexFilter implements IPlainTextFilter, LoggerAwareInterface
{
    /**
     * @var string
     */
    private $_regex;
    private $_slotName;
    private $_slotValue;

    private $_slotNameRaw;

    /**
     * Filter result to collect matches into
     *
     * @var \Convo\Core\Workflow\DefaultFilterResult
     */
    private $_filterResult;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    public function __construct($config = [])
    {
        $this->_logger = new NullLogger();

        $this->_regex = $config['regex'];
        $this->_slotName = $config['slot_name'] ?? 'regex';
        $this->_slotValue = $config['slot_value'] ?? null;

        $this->_slotNameRaw = $config['slot_name_raw'] ?? 'raw';

        $this->_filterResult = new DefaultFilterResult();
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function filter(\Convo\Core\Workflow\IConvoRequest $request)
    {
        $text = $request->getText();

        $this->_logger->debug('Filtering text ['.$text.'], to set as ['.$this->_slotName.']['.$this->_slotValue.']');

        $matches = [];

        preg_match('/'.$this->_regex.'/', $text, $matches);

        $this->_logger->debug('Matches for regex ['.$this->_regex.']['.print_r($matches, true).']');

        $value = $this->_slotValue ?? $matches[0];

        $this->_logger->debug('Final value ['.$value.']');

        if (!empty($value)) {
            $this->_filterResult->setSlotValue($this->_slotName, $value);
            $this->_filterResult->setSlotValue($this->_slotNameRaw, $matches);
        }
    }

    public function getFilterResult()
    {
        return $this->_filterResult;
    }

    // UTIl
    public function __toString()
    {
        return get_class($this)."[{$this->_regex}]";
    }
}