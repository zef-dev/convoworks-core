<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

use Convo\Core\Workflow\DefaultFilterResult;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class AndFilter extends AbstractWorkflowContainerComponent implements IPlainTextFilter
{

    /**
     * @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter[]
     */
    private $_filters;

    private $_results;

    /**
     * @var \Convo\Core\Workflow\DefaultFilterResult
     */
    private $_filterResult;

    public function __construct($config = [])
    {
        parent::__construct( $config);

        $this->_filterResult = new DefaultFilterResult();

        /** @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter $filter */
        foreach ($config['filters'] as $filter) {
            $this->_filters[] = $filter;
            $this->addChild( $filter);
        }
    }

    public function filter(\Convo\Core\Workflow\IConvoRequest $request)
    {
        for ($i = 0; $i < count($this->_filters); ++$i) {
            /** @var \Convo\Pckg\Text\Filters\Filt\IPlainTextFilter  $filter */
            /** @var \Convo\Core\Workflow\DefaultFilterResult        $sub_result */

            $filter = $this->_filters[$i];
            $filter->filter($request);

            if (!isset($this->_results[$i])) {
                $this->_results[$i] = new DefaultFilterResult();
            }

            $sub_result = $this->_results[$i];
            $sub_result->read($filter->getFilterResult());
        }

        $all_valid = true;

        foreach ($this->_results as $filter_result) {
            if ($filter_result->isEmpty()) {
                $all_valid = false;
            }
        }

        if ($all_valid) {
            foreach ($this->_results as $filter_result) {
                $this->_filterResult->read($filter_result);
            }
        }
    }

    public function getFilterResult()
    {
        return $this->_filterResult;
    }

}