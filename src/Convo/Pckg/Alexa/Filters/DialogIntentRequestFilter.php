<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Filters;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\Intent\IntentModel;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RestSystemUser;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use phpDocumentor\Reflection\Types\This;

class DialogIntentRequestFilter extends AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IRequestFilter, \Convo\Core\Intent\IIntentDriven, \Convo\Core\Adapters\Alexa\IAlexaDialogDriven
{
    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    private $_intent;

    private $_delegationStrategy;

    private $_alexaPrompts = [];

    /**
	 * @var \Convo\Core\Intent\IIntentAdapter[]
	 */
    private $_adapters = [];

    private $_id;

    public function __construct($config, $packageProviderFactory, $convoServiceDataProvider)
    {
        parent::__construct( $config);

        $this->_packageProviderFactory  =   $packageProviderFactory;
        $this->_convoServiceDataProvider  =   $convoServiceDataProvider;
        $this->_intent = $config['intent'] ?? '';
        $this->_delegationStrategy = $config['delegation_strategy'] ?? 'ALWAYS';
        $this->_alexaPrompts = $config['alexa_prompts'] ?? [];

        foreach ( $config['intent_slot_dialog_filters'] as $intentSlotDialogFilter) {
            $this->addAdapter( $intentSlotDialogFilter);
        }

        $this->_id = $config['_component_id'] ?? ''; // todo generate default id
    }

    public function getId()
    {
        return $this->_id;
    }

    public function addAdapter( \Convo\Core\Intent\IIntentAdapter $adapter)
    {
        $this->_adapters[]  =   $adapter;
        $this->addChild( $adapter);
    }

    public function accepts( \Convo\Core\Workflow\IConvoRequest $request)
    {
        if ( !is_a( $request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
            $this->_logger->notice('Request is not Alexa Request. Exiting.');
            return false;
        }

        /** @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */

        $intent = $this->getService()->evaluateString($this->_intent);
        if ($request->getIntentName() !== $intent) {
            $this->_logger->notice('Target intent ['.$intent.'] does not match with incoming intent ['.$request->getIntentName().']');
            return false;
        }

        $dialogState = $request->getPlatformData()['request']['dialogState'] ?? '';
        if (empty($dialogState)) {
            $this->_logger->notice("Dialog state can't be empty.");
            return false;
        }

        $this->_logger->info( 'Request is Alexa request ['.$request.']');
        return true;
    }

    public function filter( \Convo\Core\Workflow\IConvoRequest $request)
    {
        /** @var \Convo\Core\Adapters\Alexa\AmazonCommandRequest $request */

        $platformData = $request->getPlatformData();
        $result    =  new \Convo\Core\Workflow\DefaultFilterResult();
        $slotValues = $request->getSlotValues();

        $intentConfirmationStatus = $platformData['request']['intent']['confirmationStatus'] ?? 'NONE';
        $this->_logger->debug( 'Matching dialog against intent ['.$request->getIntentName().'] with slots ['.json_encode($slotValues).']');
        $result->setSlotValue('dialogState', $request->getPlatformData()['request']['dialogState']);
        $result->setSlotValue('intentName', $request->getIntentName());
        $result->setSlotValue('intentConfirmationStatus', $intentConfirmationStatus);

        if (!empty($slotValues)) {
            foreach ($slotValues as $slotName => $slotValue) {
                $slotConfirmationStatus = $platformData['request']['intent']['slots'][$slotName]['confirmationStatus'] ?? 'NONE';
                $result->setSlotValue($slotName, ['value' => $slotValue, 'confirmationStatus' => $slotConfirmationStatus]);
            }
        }

        return $result;
    }

    public function getPlatformIntentModel($platformId)
    {
        $this->_logger->debug( 'Searching for platform ['.$platformId.'] variant of intent ['.$this->_intent.']');

        $service = $this->getService();

        $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());

        try {
            $intent     =  $this->getService()->getIntent( $this->_intent);
        } catch ( \Convo\Core\ComponentNotFoundException $e) {
            $this->_logger->debug( $e->getMessage());
            $sys_intent =   $provider->getIntent( $this->_intent);
            $intent     =   $sys_intent->getPlatformModel( $platformId);
        }

        $this->_logger->debug( 'Returning intent ['.$intent.']');

        return $intent;
    }

    public function getDialogDefinition()
    {
        $service = $this->getService();
        $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());

        $dialogIntent = [
            'name' => $this->_intent,
            'delegationStrategy' => $service->evaluateString($this->_delegationStrategy),
            'confirmationRequired' => false
        ];

        $intentConfirmationAlexaPrompts = [];
        foreach ($this->_alexaPrompts as $alexaPrompt) {
            $intentConfirmationAlexaPrompts[] = $alexaPrompt->getAlexaPrompt();
        }

        $this->_logger->debug('Got intent confirmation prompts for intent ['.$this->_intent.'] '.json_encode($intentConfirmationAlexaPrompts, JSON_PRETTY_PRINT));

        if (!empty($intentConfirmationAlexaPrompts)) {
            $dialogIntent['confirmationRequired'] = true;
            $dialogIntent['prompts']['confirmation'] = 'Confirm.Intent.'.$this->_intent;
        }

        $targetWorkflowEntitiesOfIntent = $this->_getServiceWorkflowEntitiesOfIntent($service);
        $entityNameTypeDialog = [];
        foreach ($targetWorkflowEntitiesOfIntent as $workflowEntityOfIntent) {
            $this->_logger->debug('Got entity of intent ['.json_encode($workflowEntityOfIntent).']');
            try {
                $entity = $service->getEntity( $workflowEntityOfIntent['slot_value']);
                $entityNameTypeDialog[$entity->getName()] = $entity->getName();
            } catch ( ComponentNotFoundException $e) {
                $sys_entity = $provider->getEntity( $workflowEntityOfIntent['type']);
                $entity = $sys_entity->getPlatformModel( 'amazon');
                $entityNameTypeDialog[$workflowEntityOfIntent['slot_value']] = $entity->getName();
            }
        }
        $this->_logger->debug('Got entity name type dialog ['.json_encode($entityNameTypeDialog).']');

        $alexaPrompts = [];
        $dialogEntitiesNames = [];
        $dialogValidators = [];
        $intentSlotConfirmations = [];
        foreach ($this->_adapters as $adapter) {
            $adapterAlexaPrompts = $adapter->getAlexaPrompts();
            $alexaPrompts[key($adapterAlexaPrompts)] = array_values($adapterAlexaPrompts)[0];
            $dialogEntitiesNames[] = $adapter->getTargetSlot();
            $dialogValidators[] = $adapter->getDialogValidators();
            $intentSlotConfirmations[] = $adapter->getIntentSlotConfirmationAlexaPrompts();
        }

        $this->_logger->debug('Got alexa prompts '.json_encode($alexaPrompts, JSON_PRETTY_PRINT));
        $this->_logger->debug('Got dialog validators '.json_encode($dialogValidators, JSON_PRETTY_PRINT));
        $this->_logger->debug('Got slot confirmations '.json_encode($intentSlotConfirmations, JSON_PRETTY_PRINT));

        $userUtterances = [];
        foreach ($this->_adapters as $adapter) {
            $userUtterances[] = $adapter->getUserUtterances();
        }

        $intentSlotConfirmationPrompts = [];
        foreach ($intentSlotConfirmations as $value) {
            if (isset($value[key($value)])) {
                $intentSlotConfirmationPrompts[key($value)] = $value[key($value)];
            }
        }

        $this->_logger->debug('Checking intentSlotConfirmationPrompts ' . json_encode($intentSlotConfirmationPrompts, JSON_PRETTY_PRINT));

        $slotDialogValidators = [];
        foreach ($dialogValidators as $value) {
            if (isset($value[key($value)])) {
                $slotDialogValidators[key($value)] = $value[key($value)];
            }
        }

        $this->_logger->debug('Checking slotDialogValidators' . json_encode($slotDialogValidators, JSON_PRETTY_PRINT));

        $dialogIntent['slots'] = [];
        $validationPrompts = [];
        $confirmationPrompts = [];
        $this->_logger->debug('Going to prepare dialog slots.');

        foreach ($dialogEntitiesNames as $dialogEntitiesName) {
            $this->_logger->debug('Got dialog slot ['.json_encode($dialogEntitiesName).'] with ['.json_encode($entityNameTypeDialog).']');
            $dialogIntentDefinition = [
                'name' => $dialogEntitiesName,
                'type' => $entityNameTypeDialog[$dialogEntitiesName],
                'confirmationRequired' => false,
                'elicitationRequired' => true,
                'prompts' => [
                    'elicitation' => 'Elicit.Slot.'.$this->_intent.'.'.$dialogEntitiesName
                ]
            ];

            if (isset($intentSlotConfirmationPrompts[$dialogEntitiesName])) {
                $dialogIntentDefinition['confirmationRequired'] = true;
                $dialogIntentDefinition['prompts']['confirmation'] = 'Confirm.Slot.'.$this->_intent.'.'.$dialogEntitiesName;
            }

            if (isset($slotDialogValidators[$dialogEntitiesName])) {
                $dialogIntentDefinition['validations'] = array_map(function ($item) use (&$validationPrompts) {
                    $itemValidationProperties = !empty($item['validation']['properties']) ? $item['validation']['properties'] : [];

                    $validationItem = [
                        'type' => $item['validation']['name'],
                        'prompt' => 'Slot.Validation.'.$item['validation']['name'].'.'.$this->_intent.'.'.$item['slotToValidate'],
                    ];

                    foreach ($itemValidationProperties as $key => $value) {
                        $validationItem[$key] = $value;
                    }

                    $validationPrompts[] = [
                        'id' => 'Slot.Validation.'.$item['validation']['name'].'.'.$this->_intent.'.'.$item['slotToValidate'],
                        'variations' => array_map(function ($variation) {
                                return [
                                    'type' => 'PlainText',
                                    'value' => $variation
                                ];
                            }, $item['prompts'])
                    ];

                    return $validationItem;
                }, $slotDialogValidators[$dialogEntitiesName]);
            }

            $dialogIntent['slots'][] = $dialogIntentDefinition;
        }

        $prompts = [];

        foreach ($dialogIntent['slots'] as $dialogIntentSlot) {
            $promptDefinition = [
                'id' => $dialogIntentSlot['prompts']['elicitation'],
                'variations' => []
            ];

            if (isset($alexaPrompts[$dialogIntentSlot['name']])) {
                $promptDefinition['variations'] = array_map(function ($item) {
                    return ['type' => 'PlainText', 'value' => $item ];
                }, $alexaPrompts[$dialogIntentSlot['name']]);
            }
            $prompts[] = $promptDefinition;
        }

        foreach ($dialogIntent['slots'] as $dialogIntentSlot) {
            if (isset($dialogIntentSlot['prompts']['confirmation'])) {
                $confirmationPromptDefinition = [
                    'id' => $dialogIntentSlot['prompts']['confirmation'],
                    'variations' => []
                ];

                if (isset($intentSlotConfirmationPrompts[$dialogIntentSlot['name']])) {
                    $confirmationPromptDefinition['variations'] = array_map(function ($item) {
                        return ['type' => 'PlainText', 'value' => $item ];
                    }, $intentSlotConfirmationPrompts[$dialogIntentSlot['name']]);
                }
                $prompts[] = $confirmationPromptDefinition;
            }
        }

        $prompts = array_merge($prompts, $validationPrompts);
        if (!empty($intentConfirmationAlexaPrompts)) {
            $prompts[] = [
                'id' => 'Confirm.Intent.'.$this->_intent,
                'variations' => array_map(function($item) {
                    return [
                        'type' => 'PlainText',
                        'value' => $item
                    ];
                }, $intentConfirmationAlexaPrompts)
            ];
        }

        $result = [
            'slotSamples' => $userUtterances,
            'dialogIntent' => $dialogIntent,
            'prompts' => $prompts
        ];

        $this->_logger->debug('Got validation prompts ['.json_encode($validationPrompts, JSON_PRETTY_PRINT).']');
        $this->_logger->debug('Got result dialog intent ['.json_encode($result, JSON_PRETTY_PRINT).']');

        return $result;
    }

    private function _getServiceWorkflowEntitiesOfIntent($service) {
        $user = new RestSystemUser();
        $workflowIntents = $this->_convoServiceDataProvider->getServiceData(
            $user,
            $service->getId(),
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        )['intents'] ?? [];
        $intentName = $service->evaluateString($this->_intent);

        $targetWorkflowIntent =  array_values(array_filter( $workflowIntents, function ( $intent) use ( $intentName) {
            return $intent['name'] === $intentName;
        }))[0];

        $serviceWorkflowEntitiesOfIntent = [];
        $utterances = $targetWorkflowIntent['utterances'];

        foreach ($utterances as $utterance) {
            $model = $utterance['model'] ?? [];
            foreach ($model as $modelItem) {
                if (isset($modelItem['type']) && isset($modelItem['slot_value'])) {
                    unset($modelItem['text']);
                    $serviceWorkflowEntitiesOfIntent[] = $modelItem;
                }
            }
        }

        return $serviceWorkflowEntitiesOfIntent;
    }

    private function _getServiceWorkflowEntities($service) {
        $user = new RestSystemUser();
        return $this->_convoServiceDataProvider->getServiceData(
            $user,
            $service->getId(),
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        )['entities'] ?? [];
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.$this->_id.']';
    }
}
