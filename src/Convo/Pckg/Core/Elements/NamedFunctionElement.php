<?php

declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;

class NamedFunctionElement extends AbstractWorkflowContainerComponent implements IConversationElement
{

    private $_functionName;
    private $_functionArgs;
    private $_resultData;
    /**
     * @var IConversationElement[]
     */
    private $_ok = [];

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

        $elem_params = $service->getComponentParams(\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $arguments = $service->evaluateArgs($this->_functionArgs, $this);

        $this->_logger->debug('Got parsed function arg definition [' . print_r($arguments, true) . ']');
        // Create a closure representing your function
        $function = function (...$params) use ($arguments, $elem_params, $request, $response) {
            $this->_logger->debug('Got function args in execution [' . json_encode($params) . ']');
            $i = 0;

            // Make function arguments visible to child components
            foreach ($arguments as $name => $default) {
                $value = $params[$i] ?? $this->evaluateString($default);
                $this->_logger->debug('Preparing argument  [' . $name . '][' . $value . ']');
                $elem_params->setServiceParam($name, $value);
                $i++;
            }

            // Execute subflow
            foreach ($this->_ok as $elem) {
                $elem->read($request, $response);
            }
            $result = $this->evaluateString($this->_resultData);
            $this->_logger->debug('Returning function result [' . $result . ']');
            return $result;
        };

        $this->_logger->debug('Registering function [' . $this->_functionName . '] in global scope');

        $expressionLanguage = $service->getExpressionLanguage();
        $expressionLanguage->addFunction(
            new \Symfony\Component\ExpressionLanguage\ExpressionFunction(
                $this->_functionName,
                function () {
                    return ''; // No-op for compilation
                },
                function (...$params) use ($function) {
                    array_shift($params);
                    $this->_logger->debug('Got function args in registration [' . json_encode($params) . ']');
                    return call_user_func_array($function, $params);
                }
            )
        );

        // Optionally, expose it in service_params as well
        $service_params = $service->getServiceParams(\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST);
        $service_params->setServiceParam($this->_functionName, $function);
    }


    // UTIL
    public function __toString()
    {
        return get_class($this) . '[' . json_encode($this->_functionName) . ']';
    }
}
