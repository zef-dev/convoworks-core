<?php

declare(strict_types=1);

namespace Convo\Core\Workflow;


interface IFunctionScope
{
    /**
     * @return \Convo\Core\Params\IServiceParams
     */
    public function getFunctionParams();

    // public function resetParams();

    public function initParams();

    public function restoreParams($id);
}
