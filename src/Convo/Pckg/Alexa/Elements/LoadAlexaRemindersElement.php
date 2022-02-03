<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Alexa\Api\AlexaRemindersApi;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use phpDocumentor\Reflection\Types\This;

class LoadAlexaRemindersElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    /**
     * @var string
     */
    private $_reminderStatus;

    /**
     * @var string
     */
    private $_statusVar;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_multipleFlow = array();

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_emptyFlow = array();

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_singleFlow = array();

    /**
     * @var AlexaRemindersApi
     */
    private $_alexaRemindersApi;

    public function __construct($properties, $alexaRemindersApi)
    {
        parent::__construct($properties);

        $this->_reminderStatus = $properties['reminder_status'] ?? 'ON';
        $this->_statusVar = $properties['reminders_status_var'];

        foreach ( $properties['multiple'] as $element) {
            $this->_multipleFlow[] = $element;
            $this->addChild($element);
        }

        foreach ( $properties['single'] as $element) {
            $this->_singleFlow[] = $element;
            $this->addChild($element);
        }

        foreach ( $properties['empty'] as $element) {
            $this->_emptyFlow[] = $element;
            $this->addChild($element);
        }

        $this->_alexaRemindersApi = $alexaRemindersApi;
    }

    /**
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     */
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $reminderStatus = $this->evaluateString($this->_reminderStatus);
        $statusVar = $this->evaluateString($this->_statusVar);

        if (is_a($request, AmazonCommandRequest::class)) {
            $this->_logger->info('Getting all reminders of [' . $request->getServiceId() . ']');
            $reminders = $this->_alexaRemindersApi->getAllReminders($request);
            $this->_logger->info('Got reminders [' . json_encode($reminders) . '] from Alexa Reminders API');

            $data = $this->_filterRemindersByStatus($reminders['alerts'], $reminderStatus);
            $this->_logger->info('Got filtered reminders [' . json_encode($data) . ']');

            $params = $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
            $params->setServiceParam($statusVar, ['reminders' => $data]);

            if (count($data) === 1) {
                $selected_flow = $this->_fallbackFlows($this->_singleFlow);
            } else if (empty($data)) {
                $selected_flow = $this->_fallbackFlows($this->_emptyFlow);
            } else {
                $selected_flow = $this->_fallbackFlows($this->_multipleFlow);
            }

            foreach ($selected_flow as $element) {
                $element->read($request, $response);
            }
        }
    }

    private function _filterRemindersByStatus($reminders, $status) {
        $result = $reminders;

        if ($status === 'ON' || $status === 'COMPLETED') {
            $this->_logger->info('Filtering reminders [' . json_encode($reminders) . ']');
            $filteredResult = array_filter($reminders, function ($reminder) use ($status) {
                return ($reminder['status'] == $status);
            });
            $result = array_values($filteredResult);
        }

        return $result;
    }

    private function _fallbackFlows($flow) {
        if ( $flow === $this->_emptyFlow && empty($flow)) {
            $this->_logger->debug('Returning multiple flow');
            return $this->_multipleFlow;
        }
        if ( $flow === $this->_singleFlow && empty($flow)) {
            $this->_logger->debug('Returning multiple flow');
            return $this->_multipleFlow;
        }
        $this->_logger->debug('Returning original flow');
        return $flow;
    }
}
