<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters;

class PlainTextRequestFilter implements \Convo\Core\Workflow\IRequestFilter, \Psr\Log\LoggerAwareInterface
{
    /**
     * @var \Convo\Core\Workflow\IRequestFilterResult
     */
    protected $_filterResult;

    /**
     * @var \Convo\Core\ConvoServiceInstance
     */
    private $_service;

    /**
     * @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter[]
     */
    private $_filters;

    /**
     * Logger
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    private $_id;

    public function __construct($config)
    {
        $this->_logger = new \Psr\Log\NullLogger();

        $this->_filters = $config['filters'];

        $this->_id = $config['_component_id'] ?? '';
        $this->_filterResult = new \Convo\Core\Workflow\DefaultFilterResult();
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    public function accepts(\Convo\Core\Workflow\IConvoRequest $request)
    {
        if (empty($request->getText())) {
            $this->_logger->warning('Empty text request in request filter ['.$this.']');
            return false;
        }

        return true;
    }

    public function filter(\Convo\Core\Workflow\IConvoRequest $request)
    {
        /** @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter $filter */
        foreach ($this->_filters as $filter) {
            $filter->filter($request);
            $result = $filter->getFilterResult();
            
            $this->_filterResult->read($result);
        }

        return $this->_filterResult;
    }

    public function setService(\Convo\Core\ConvoServiceInstance $service)
    {
        $this->_service = $service;
    }

    public function getService()
    {
        return $this->_service;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this)."[{$this->_id}]";
    }
}