<?php


namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Util\ArrayUtil;

class DialogDelegateElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{
    private $_shouldUpdateIntent;
    private $_slotSlotValues;

	public function __construct( $properties)
	{
		parent::__construct( $properties);
        $this->getId();
        $this->_shouldUpdateIntent = $properties['should_update_intent'] ?? false;
        $this->_slotSlotValues = $properties['intent_slot_values'] ?? [];
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
        /* @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest  $request */
        /* @var \Convo\Core\Adapters\Alexa\AmazonCommandResponse  $response */
        if ( is_a( $request, 'Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
            $shouldUpdateIntent = $this->evaluateString($this->_shouldUpdateIntent);
            $intentSlotValues = $this->_evaluateArgs($this->_slotSlotValues);

            $response->prepareResponse(IAlexaResponseType::DIALOG_DELEGATE_DIRECTIVE);

            $updatedIntent = [];
            if ($shouldUpdateIntent && !empty($intentSlotValues) ) {
                $updatedIntent = $request->getPlatformData()['request']['intent'] ?? [];
                if (!empty($updatedIntent)) {
                    foreach ($intentSlotValues as $key => $value) {
                        $updatedIntent['slots'][$key]['value'] = $value;
                    }
                }
            }
            $response->delegate($updatedIntent);
        }
	}

    private function _evaluateArgs( $args)
    {
        // $this->_logger->debug( 'Got raw args ['.print_r( $args, true).']');
        $returnedArgs   =   [];
        foreach ( $args as $key => $val)
        {
            $key	=	$this->evaluateString( $key);
            $parsed =   $this->evaluateString( $val);

            if ( !ArrayUtil::isComplexKey( $key))
            {
                $returnedArgs[$key] =   $parsed;
            }
            else
            {
                $root           =   ArrayUtil::getRootOfKey( $key);
                $final          =   ArrayUtil::setDeepObject( $key, $parsed, $returnedArgs[$root] ?? []);
                $returnedArgs[$root]    =   $final;
            }
        }
        // $this->_logger->debug( 'Got evaluated args ['.print_r( $returnedArgs, true).']');
        return $returnedArgs;
    }
}
