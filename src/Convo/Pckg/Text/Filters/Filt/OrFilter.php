<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class OrFilter extends AbstractWorkflowContainerComponent implements IPlainTextFilter
{
    /**
     * @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter[]
     */
    private $_filters;

    /**
     * @var \Convo\Core\Workflow\DefaultFilterResult
     */
    private $_filterResult;

    private $_collectAll;

    public function __construct($config = [])
    {
        parent::__construct( $config);
        
        /** @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter $filter */
        foreach ($config['filters'] as $filter) {
            $this->_filters[] = $filter;
            $this->addChild( $filter);
        }

        $this->_filterResult = new DefaultFilterResult();

        $this->_collectAll = $config['collect_all'] ?? false;
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

}