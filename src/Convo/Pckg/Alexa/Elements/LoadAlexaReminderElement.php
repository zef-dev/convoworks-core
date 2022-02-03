<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Alexa\Api\AlexaRemindersApi;
use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class LoadAlexaReminderElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    /**
     * @var string
     */
    private $_reminderId;

    /**
     * @var string
     */
    private $_statusVar;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_okFlow = array();

    /**
     * @var AlexaRemindersApi
     */
    private $_alexaRemindersApi;

    public function __construct($properties, $alexaRemindersApi)
    {
        parent::__construct($properties);

        $this->_reminderId = $properties['reminder_id'];
        $this->_statusVar = $properties['reminders_status_var'];

        foreach ($properties['ok'] as $element) {
            $this->_okFlow[] = $element;
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
        $reminderId = $this->evaluateString($this->_reminderId);
        $statusVar = $this->evaluateString($this->_statusVar);

        if (is_a($request, AmazonCommandRequest::class)) {
            $this->_logger->info('Getting reminder with id [' . $reminderId . ']');
            $reminder = $this->_alexaRemindersApi->getReminder($request, $reminderId);
            $this->_logger->info('Got reminder [' . json_encode($reminder) . '] from Alexa Reminders API.');

            $params = $this->getService()->getComponentParams(IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
            $params->setServiceParam($statusVar, ['reminder' => $reminder]);

            foreach ($this->_okFlow as $element) {
                $element->read($request, $response);
            }
        }
    }
}
