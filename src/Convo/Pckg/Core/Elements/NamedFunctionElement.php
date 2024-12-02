<?php

declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Params\IServiceParams;
use Convo\Core\Params\SimpleParams;
use Convo\Core\Util\StrUtil;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IFunctionScope;

class NamedFunctionElement extends AbstractWorkflowContainerComponent implements IConversationElement, IFunctionScope
{

    private $_functionName;
    private $_functionArgs;
    private $_resultData;
    /**
     * @var IConversationElement[]
     */
    private $_ok = [];

    /**
     * @deprecated
     */
    private $_functionScope = [];

    /**
     * @var string
     */
    private $_executionId;

    /**
     * @var IServiceParams
     */
    private $_currentParams;

    /**
     * @var IServiceParams[]
     */
    private $_functionParams = [];

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_functionName     =   $properties['name'];
        $this->_functionArgs     =   $properties['function_args'];
        $this->_resultData      =   $properties['result_data'];
        foreach ($properties['ok'] as $element) {
            $this->_ok[] = $element;
            $this->addChild($element);
        }
    }

    public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        $service = $this->getService();


        $arguments = $service->evaluateArgs($this->_functionArgs, $this);

        $this->_logger->debug('FNC: Got parsed function arg definition [' . print_r($arguments, true) . ']');
        // Create a closure representing your function
        $function = function (...$params) use ($arguments, $request, $response) {
            $elem_params = $this->getFunctionParams();
            $this->_logger->debug('  [' . json_encode($params) . ']');
            $i = 0;

            // Make function arguments visible to child components
            foreach ($arguments as $name => $default) {
                $value = $params[$i] ?? $this->evaluateString($default);
                $this->_logger->debug('FNC: Preparing argument  [' . $name . '][' . $value . ']');
                $elem_params->setServiceParam($name, $value);
                $i++;
            }

            // Execute subflow
            foreach ($this->_ok as $elem) {
                $elem->read($request, $response);
            }
            $result = $this->evaluateString($this->_resultData);
            $this->_logger->debug('FNC: Returning function result [' . $result . ']');
            return $result;
        };

        $this->_logger->debug('FNC: Registering function [' . $this->_functionName . '] in global scope');

        $expressionLanguage = $service->getExpressionLanguage();
        $expressionLanguage->addFunction(
            new \Symfony\Component\ExpressionLanguage\ExpressionFunction(
                $this->_functionName,
                function () {
                    return ''; // No-op for compilation
                },
                function (...$params) use ($function) {
                    array_shift($params);
                    $id = $this->initParams();
                    $this->_logger->debug('Got function args in registration [' . $id . '][' . json_encode($params) . ']');
                    $res = call_user_func_array($function, $params);
                    $this->restoreParams($id);
                    $this->_logger->debug('Resttoring id [' . $id . ']');
                    return $res;
                }
            )
        );

        // Optionally, expose it in service_params as well
        $service_params = $service->getServiceParams(\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST);
        $service_params->setServiceParam($this->_functionName, $function);
    }

    /**
     * @return \Convo\Core\Params\IServiceParams
     */
    public function getFunctionParams()
    {
        if (!$this->_functionParams[$this->_executionId]) {
            throw new \Exception('No params defined for [' . $this->_executionId . ']');
        }

        return $this->_functionParams[$this->_executionId];
    }

    public function initParams()
    {
        $this->_executionId = StrUtil::uuidV4();
        $this->_functionParams[$this->_executionId] = new SimpleParams();
        $this->_currentParams = $this->_functionParams[$this->_executionId];
        return $this->_executionId;
    }

    public function restoreParams($id)
    {
        $this->_executionId = $id;
        $this->_currentParams = $this->_functionParams[$this->_executionId];
    }

    public function evaluateString($string, $context = [])
    {
        return $this->getParent()->evaluateString($string, array_merge(
            $context,
            $this->_currentParams ? $this->_currentParams->getData() : []
        ));
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[' . json_encode($this->_functionName) . ']';
    }
}
