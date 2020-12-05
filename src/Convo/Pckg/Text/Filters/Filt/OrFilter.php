<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

use Convo\Core\Workflow\DefaultFilterResult;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class OrFilter implements IPlainTextFilter, LoggerAwareInterface
{
    /**
     * @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter[]
     */
    private $_filters;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Workflow\DefaultFilterResult
     */
    private $_filterResult;

    private $_collectAll;

    public function __construct($config = [])
    {
        $this->_logger = new NullLogger();

        /** @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter $filter */
        foreach ($config['filters'] as $filter) {
            $this->_filters[] = $filter;
        }

        $this->_filterResult = new DefaultFilterResult();

        $this->_collectAll = $config['collect_all'] ?? false;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function filter(\Convo\Core\Workflow\IConvoRequest $request)
    {
        $this->_logger->debug("Filtering text [{$request->getText()}]");

        foreach ($this->_filters as $filter) {
            $filter->filter($request);
            $sub_result = $filter->getFilterResult();

            if (!$sub_result->isEmpty()) {
                $this->_filterResult->read($sub_result);

                if (!$this->_collectAll) {
                    break;
                }
            }
        }
    }

    public function getFilterResult()
    {
        return $this->_filterResult;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this)."[]";
    }
}