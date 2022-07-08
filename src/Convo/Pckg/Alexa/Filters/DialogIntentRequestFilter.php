<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Filters;

use Convo\Core\ComponentNotFoundException;
use Convo\Core\Intent\IntentModel;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class DialogIntentRequestFilter extends AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IRequestFilter, \Convo\Core\Intent\IIntentDriven, \Convo\Core\Adapters\Alexa\IAlexaDialogDriven
{
    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    private $_intent;

    private $_delegationStrategy;

    /**
	 * @var \Convo\Core\Intent\IIntentAdapter[]
	 */
    private $_adapters = [];

    private $_id;

    public function __construct($config, $packageProviderFactory)
    {
        parent::__construct( $config);

        $this->_packageProviderFactory  =   $packageProviderFactory;
        $this->_intent = $config['intent'] ?? '';
        $this->_delegationStrategy = $config['delegation_strategy'] ?? 'ALWAYS';

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

        $result    =  new \Convo\Core\Workflow\DefaultFilterResult();
        $slotValues = $request->getSlotValues();

        $this->_logger->debug( 'Matching dialog against intent ['.$request->getIntentName().']');

        if (!empty($slotValues)) {
            foreach ($slotValues as $slotName => $slotValue) {
                $result->setSlotValue($slotName, $slotValue);
            }
        } else {
            // useful for stated dialog state
            $this->_logger->info('Setting dummy slot value as no slots are yet present.');
            $result->setSlotValue(get_class($this), true);
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

        $intentModel = $this->getPlatformIntentModel('amazon');
        $entity_names = array_unique($intentModel->getEntities());
        $entityNameTypeDialog = [];
        $this->_logger->debug('Got entity names ['.json_encode($entity_names).']');

        foreach ($entity_names as $entity_name) {
            $this->_logger->debug('Got entity name ['.json_encode($entity_name).']');
            try {
                $entity        =   $service->getEntity( $entity_name);
                $entityNameTypeDialog[$entity->getName()] = $entity->getName();
            } catch ( ComponentNotFoundException $e) {
                $sys_entity    =   $provider->getEntity( $entity_name);
                $entity        =   $sys_entity->getPlatformModel( 'amazon');
                $entityNameTypeDialog[$sys_entity->getName()] = $entity->getName();
            }
        }

        $alexaPrompts = [];
        $dialogEntitiesNames = [];
        foreach ($this->_adapters as $adapter) {
            $adapterAlexaPrompts = $adapter->getAlexaPrompts();
            $alexaPrompts[key($adapterAlexaPrompts)] = array_values($adapterAlexaPrompts)[0];
            $dialogEntitiesNames[] = $adapter->getTargetSlot();
        }

        $this->_logger->debug('Got alexa prompts '.json_encode($alexaPrompts, JSON_PRETTY_PRINT));

        $userUtterances = [];
        foreach ($this->_adapters as $adapter) {
            $userUtterances[] = $adapter->getUserUtterances();
        }

        $dialogIntent['slots'] = [];
        foreach ($dialogEntitiesNames as $dialogEntitiesName) {
            $dialogIntent['slots'][] = [
                'name' => $dialogEntitiesName,
                'type' => $entityNameTypeDialog[$dialogEntitiesName],
                'confirmationRequired' => false,
                'elicitationRequired' => true,
                'prompts' => [
                    'elicitation' => 'Elicit.Slot.'.$this->_intent.'.'.$dialogEntitiesName
                ]
            ];
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

        $result = [
            'slotSamples' => $userUtterances,
            'dialogIntent' => $dialogIntent,
            'prompts' => $prompts
        ];

        $this->_logger->debug('Got result dialog intent ['.json_encode($result, JSON_PRETTY_PRINT).']');

        return $result;

        /**
        {
            "name": "TestIntent",
            "delegationStrategy": "ALWAYS",
            "confirmationRequired": false,
            "prompts": {},
            "slots": [
                {
                    "name": "TestEntity",
                    "type": "TestEntity",
                    "confirmationRequired": false,
                    "elicitationRequired": true,
                    "prompts": {
                        "elicitation": "Elicit.Slot.1495098284960.242927177617"
                    }
                }
            ]
        }
        **/
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.$this->_id.']';
    }
}
