<?php declare(strict_types=1);

namespace Convo\Pckg\Text\Filters\Filt;

interface IPlainTextFilter
{
    /**
     * Filters text
     * @param \Convo\Core\Workflow\IConvoRequest $request Request to parse
     */
    public function filter(\Convo\Core\Workflow\IConvoRequest $request);

    /**
     * Returns the filter result
     * @return \Convo\Core\Workflow\IRequestFilterResult 
     */
    public function getFilterResult();
}