<?php


namespace Convo\Core\Factory;


use Convo\Core\Adapters\Alexa\AlexaSkillLanguageMapper;
use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Dialogflow\DialogflowLanguageMapper;
use Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandRequest;
use Convo\Core\ComponentNotFoundException;
use Convo\Core\Rest\OwnerNotSpecifiedException;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IntentAwareWrapperRequest;
use Convo\Core\Publish\IPlatformPublisher;

class PlatformRequestFactory implements IPlatformRequestFactory
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    protected $_convoServiceDataProvider;

    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    /**
     * @var \Convo\Core\Adapters\Alexa\AmazonPublishingService
     */
    private $_amazonPublishingService;

    /**
     * @var \Convo\Core\Adapters\Dialogflow\DialogflowApiFactory
     */
    private $_dialogflowApiFactory;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    public function __construct($logger, $convoServiceDataProvider, $amazonPublishingService, $dialogflowApiFactory, $adminUserDataProvider, $httpFactory)
    {
        $this->_logger                      = $logger;
        $this->_convoServiceDataProvider    = $convoServiceDataProvider;
        $this->_amazonPublishingService     = $amazonPublishingService;
        $this->_dialogflowApiFactory        = $dialogflowApiFactory;
        $this->_adminUserDataProvider       = $adminUserDataProvider;
        $this->_httpFactory                 = $httpFactory;
    }

    public function toIntentRequest(IConvoRequest $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
    {
        switch ($platformId) {
            case AmazonCommandRequest::PLATFORM_ID:
                $this->_logger->info("Accessing Platform Request Factory with Amazon Command Request");
                return $this->_prepareAmazonIntentRequest($request, $user, $serviceId, $platformId);
            case DialogflowCommandRequest::PLATFORM_ID;
            case 'dialogflow_es';
                $this->_logger->info("Accessing Platform Request Factory with Dialogflow Command Request");
                return $this->_prepareDialogflowIntentRequest($request, $user, $serviceId, $platformId);
            default:
                throw new ComponentNotFoundException('Platform ' . $platformId . ' not supported.');
        }
    }

    private function _prepareAmazonIntentRequest(IConvoRequest $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId) {

        $this->_logger->debug('Exec platform id ['.$platformId.']');

        if ($request->isEmpty()) {
            return new IntentAwareWrapperRequest($request, '', [], [], $platformId);
        }

        $service_meta = $this->_convoServiceDataProvider->getServiceMeta(
            $user, $serviceId
        );
        $config = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $user,
            $serviceId,
            IPlatformPublisher::MAPPING_TYPE_DEVELOP
        );

//		if (!isset($service_meta['platform_data'][$this->getPlatformId()]['skillId'])) {
//			throw new \Exception('Cannot simulate request for ['.$this->_serviceId.']. missing Amazon skill ID');
//		}

        if (!$service_meta['owner']) {
            throw new \Exception('Could not determine owner for service ['.$serviceId.']');
        }

        $owner = $this->_adminUserDataProvider->findUser($service_meta['owner']);

        $skill_id = $config[$platformId]['app_id'];
        $language = AlexaSkillLanguageMapper::getDefaultLocale($service_meta['default_language']);
        $simulation_data = $this->_amazonPublishingService->simulateRequest(
            $owner, $skill_id, $request->getText(), $language
        );

        $this->_logger->debug('Got simulation data ['.print_r($simulation_data, true).']');

        if (!isset($simulation_data['selectedIntent'])) {
            $this->_logger->warning('Filed [Selected Intent] is not present, going to set the field with name [AMAZON.FallbackIntent]');
            $simulation_data['selectedIntent'] = ['name' => 'AMAZON.FallbackIntent'];
        }

        $intent_name = $simulation_data['selectedIntent']['name'];
        $slots = [];
        $rawSlots = [];

        if (isset($simulation_data['selectedIntent']['slots'])) {
            $rawSlots = $simulation_data['selectedIntent']['slots'];
            foreach ($simulation_data['selectedIntent']['slots'] as $definition) {
                $name = $definition['name'];
                $value = null;

                if (!isset($definition['resolutions'])) {
                    $value = $definition['value'] ?? null;
                }

                $statusCode = $definition['slotValue']['resolutions']['resolutionsPerAuthority'][0]['status']['code'] ?? null;
                if ($statusCode === 'ER_SUCCESS_MATCH') {
                    $value = $definition['slotValue']['resolutions']['resolutionsPerAuthority'][0]['values'][0]['name'] ?? null;
                }

                $slots[$name] = $value;
            }
        }

        $this->_logger->info('Final matched intent data ['.$intent_name.']['.print_r($slots, true).']');

        return new IntentAwareWrapperRequest($request, $intent_name, $slots, $rawSlots, $platformId);
    }

    private function _prepareDialogflowIntentRequest(IConvoRequest $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId) {

        $this->_logger->debug('Exec platform id ['.$platformId.']');

        $service_meta = $this->_convoServiceDataProvider->getServiceMeta(
            $user, $serviceId
        );

        if (!$service_meta['owner']) {
            throw new OwnerNotSpecifiedException('Could not determine owner for service ['.$serviceId.']');
        }

        $owner = $this->_adminUserDataProvider->findUser($service_meta['owner']);

        $api = $this->_dialogflowApiFactory->getApi($owner, $serviceId, $platformId);

        $text = $request->getText();

        if (!$text) {
            return new IntentAwareWrapperRequest($request, '', [], [], $platformId);
        }

        $language = DialogflowLanguageMapper::getDefaultLocale($service_meta['default_language']);
        $result = $api->analyzeText($text, $language);

        $decodedResult = json_decode($result, true);
        // $this->_logger->debug('Got analysis result ['.print_r($decodedResult, true).']');

        $intent_name = $decodedResult['queryResult']['intent']['displayName'];
        $slots = $decodedResult['queryResult']['parameters'];

        $this->_logger->info('Got intent ['.$intent_name.']['.print_r($slots, true).']');

        return new IntentAwareWrapperRequest($request, $intent_name, $slots, $slots, $platformId);
    }
}
