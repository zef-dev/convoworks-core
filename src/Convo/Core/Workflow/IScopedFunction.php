<?php

declare(strict_types=1);

namespace Convo\Core\Workflow;

interface IScopedFunction
{
    /**
     * Initializes function params scope and returns previous execution id.
     * @return string
     */
    public function initParams();

    /**
     * Restores previous params scope
     * @param string $id
     */
    public function restoreParams($executionId);

    /**
     * Returns current function execution params scope. Throws exceptiin if not initialized.
     * @return \Convo\Core\Params\IServiceParams
     */
    public function getFunctionParams();
}
