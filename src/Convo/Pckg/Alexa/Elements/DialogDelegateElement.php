<?php


namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\IAlexaResponseType;

class DialogDelegateElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    private $_delegationAction;
    private $_intentToUpdate;
    private $_slotSlotValues;

	public function __construct( $properties)
	{
		parent::__construct( $properties);
        $this->getId();
        $this->_delegationAction = $properties['delegation_action'] ?? 'DELEGATE';
        $this->_intentToUpdate = $properties['intent_to_update'] ?? '';
        $this->_slotSlotValues = $properties['intent_slot_values'] ?? [];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
        /* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
        /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
        if ( is_a( $request, 'Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
            $delegationAction = $this->evaluateString($this->_delegationAction);
            $intentToUpdate = $this->evaluateString($this->_intentToUpdate);
            $intentSlotValues = $this->getService()->evaluateArgs( $this->_slotSlotValues, $this);

            $response->prepareResponse(IAlexaResponseType::DIALOG_DELEGATE_DIRECTIVE);

            $updatedIntent = [];
            if ($delegationAction === 'DELEGATE_AND_UPDATE_INCOMING_INTENT') {
                if (!empty($intentSlotValues) ) {
                    $updatedIntent = $request->getPlatformData()['request']['intent'] ?? [];
                    if (!empty($updatedIntent)) {
                        foreach ($intentSlotValues as $key => $value) {
                            $updatedIntent['slots'][$key]['value'] = $value;
                        }
                    }
                }
            } else if ($delegationAction === 'DELEGATE_AND_UPDATE_ANOTHER_INTENT') {
                if (!empty($intentSlotValues) ) {
                    $updatedIntent = [
                        'name' => $intentToUpdate
                    ];
                    foreach ($intentSlotValues as $key => $value) {
                        $updatedIntent['slots'][$key]['name'] = $key;
                        $updatedIntent['slots'][$key]['value'] = $value;
                    }
                }
            }

            $response->delegate($updatedIntent);
        }
	}

}
