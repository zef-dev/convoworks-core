<?php

declare(strict_types=1);

namespace Convo\Core\Workflow;

use Convo\Core\Params\IServiceParams;
use Convo\Core\Params\SimpleParams;
use Convo\Core\Util\StrUtil;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IFunctionScope;

abstract class AbstractScopedFunction extends AbstractWorkflowContainerComponent implements IFunctionScope
{

    /**
     * @var string
     */
    private $_executionId = null;

    /**
     * @var IServiceParams[]
     */
    private $_functionParams = [];

    /**
     * @return \Convo\Core\Params\IServiceParams
     */
    public function getFunctionParams()
    {
        if (!$this->_functionParams[$this->_executionId]) {
            throw new \Convo\Core\ComponentNotFoundException('No params defined for [' . $this->_executionId . ']');
        }

        return $this->_functionParams[$this->_executionId];
    }

    public function initParams()
    {
        $old = $this->_executionId;
        $this->_executionId = StrUtil::uuidV4();
        $this->_functionParams[$this->_executionId] = new SimpleParams();
        return $old;
    }

    public function restoreParams($id)
    {
        $this->_executionId = $id;
    }

    public function evaluateString($string, $context = [])
    {
        return parent::evaluateString($string, array_merge(
            $context,
            $this->_executionId ? $this->getFunctionParams()->getData() : []
        ));
    }
}
