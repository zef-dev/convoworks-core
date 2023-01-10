<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class PlainTextRequestFilter extends AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IRequestFilter
{
    /**
     * @var \Convo\Core\Workflow\IRequestFilterResult
     */
    protected $_filterResult;

    /**
     * @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter[]
     */
    private $_filters;


    private $_id;

    public function __construct( $config)
    {
        parent::__construct( $config);
        
        $this->_filters = $config['filters'];
        foreach ( $this->_filters as $filter) {
            $this->addChild( $filter);
        }
        
        $this->_id = $config['_component_id'] ?? '';
        $this->_filterResult = new \Convo\Core\Workflow\DefaultFilterResult();
    }

    public function getId()
    {
        return $this->_id;
    }

    public function accepts(\Convo\Core\Workflow\IConvoRequest $request)
    {
        if (trim($request->getText()) === '') {
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

    // UTIL
    public function __toString()
    {
        return parent::__toString()."[{$this->_id}]";
    }
}