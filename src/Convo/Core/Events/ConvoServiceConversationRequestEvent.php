<?php

namespace Convo\Core\Events;

use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IIntentAwareRequest;
use Symfony\Contracts\EventDispatcher\Event;

class ConvoServiceConversationRequestEvent extends Event
{
    const NAME = 'convo.service.conversation.request';

    protected $_convoRequest;
    protected $_convoResponse;
    protected $_stage;
    protected $_convoServiceVariables;
    protected $_convoServiceResponseStatusCode;
    protected $_convoServiceResponseErrorStackTrace;

    public function __construct(IConvoRequest $convoRequest, IConvoResponse $convoResponse, $stage, $convoServiceVariables, $convoServiceResponseStatusCode, $convoServiceResponseErrorStackTrace)
    {
        $this->_convoRequest = $convoRequest;
        $this->_convoResponse = $convoResponse;
        $this->_stage = $stage;
        $this->_convoServiceVariables = $convoServiceVariables;
        $this->_convoServiceResponseStatusCode = $convoServiceResponseStatusCode;
        $this->_convoServiceResponseErrorStackTrace = $convoServiceResponseErrorStackTrace;
    }

    public function getConvoRequest() : IConvoRequest
    {
        return $this->_convoRequest;
    }

    public function getConvoResponse() : IConvoResponse
    {
        return $this->_convoResponse;
    }

    public function getStage() {
        return $this->_stage;
    }

    public function getPlatformId() {
        $platformId = 'UNKNOWN';

        if (!empty($this->_convoRequest->getPlatformId())) {
            $platformId = $this->_convoRequest->getPlatformId();
        }

        $sessionId = $this->_convoRequest->getSessionId();
        if (strpos($sessionId, 'admin-chat') !== false) {
            $platformId = 'Convo Admin Chat';
        }

        return $platformId;
    }

    public function getIntentName()
    {
        $intentName = '';

        if (is_a($this->_convoRequest, '\Convo\Core\Workflow\IIntentAwareRequest')) {
            $intentName = $this->_convoRequest->getIntentName();
        }

        return !empty($intentName) ? $intentName : '';
    }

    public function getSlotValues() {
        $slotValues = [];

        if (is_a($this->_convoRequest, '\Convo\Core\Workflow\IIntentAwareRequest')) {
            $slotValues = $this->_convoRequest->getSlotValues();
        }

        return !empty($slotValues) ? $slotValues : [];
    }

    public function getConvoServiceVariables() {
        return $this->_convoServiceVariables;
    }

    public function getConvoServiceResponseStatusCode() {
        return $this->_convoServiceResponseStatusCode;
    }

    public function getConvoServiceResponseErrorStackTrace() {
        return $this->_convoServiceResponseErrorStackTrace;
    }
}
