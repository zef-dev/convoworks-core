<?php declare(strict_types=1);


namespace Convo\Core\Adapters\Google\Dialogflow;

use Convo\Core\Adapters\Google\Common\ICapability;
use Convo\Core\Adapters\Google\Common\Intent\IActionsIntent;
use Convo\Core\Util\StrUtil;
use Convo\Core\Workflow\IConvoAudioRequest;
use Convo\Core\Workflow\IIntentAwareRequest;
use Convo\Core\Workflow\IMediaType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DialogflowCommandRequest implements IIntentAwareRequest, LoggerAwareInterface, IConvoAudioRequest
{
    const PLATFORM_ID   =   'dialogflow';
    const DEFAULT_CONVERSATION_TOKEN    =   '{ "state": null, "data": {} }';

    private $_data;
    private $_serviceId;
    private $_deviceId;
    private $_logger;

    private $_slots;
    private $_rawSlots;

    private $_accessToken;
    private $_text;
    private $_selectedOption;
    private $_sessionId;
    private $_requestId;
    private $_installationId;
    private $_preparedInstallationId;

    private $_isLaunchRequest = false;
    private $_isEnd     =   false;
    private $_isMediaRequest     =   false;

    private $_intentType;
    private $_intentName;

    private $_isWebBrowserSupported = false;
    private $_isDisplaySupported = false;
    private $_isAudioSupported = false;
    private $_isMediaResponseAudioSupported = false;
    private $_isAccountLinkingSupported = false;

    private $_isRePromptRequest = false;
    private $_conversationType = '';
    
    /**
     * @var DialogflowSlotParser
     */
    private $_parser;

    /**
     * @param string $serviceId
     * @param DialogflowSlotParser $parser
     * @param array $data
     */
    public function __construct( $serviceId, $parser, $data)
    {
        $this->_serviceId               =   $serviceId;
        $this->_parser                  =   $parser;
        $this->_data                    =   $data;

        $this->_deviceId    =   'UNKNOWN';
        $this->_logger		=	new NullLogger();
    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        if ( isset( $this->_data['responseId'])) {
            $this->_logger->debug( 'Parsing response ['.$this->_data['responseId'].']');
        }

        if (isset($this->_data['originalDetectIntentRequest']['source'])) {
            $this->_initWithOriginalDetectIntentRequest();
        } else if (!isset($this->_data['originalDetectIntentRequest']['source'])) {
            $this->_initWithQueryResult();
        }

        if (isset($this->_data['queryResult']['parameters']))
        {
            $this->_slots = $this->_parser->parseSlotValues( $this->_intentName, $this->_data['queryResult']['parameters']);
            $this->_rawSlots = $this->_data['queryResult']['parameters'];
        }
    }

    private function _initWithOriginalDetectIntentRequest() {
        $this->_requestId = $this->_data['responseId'] ?? '';
        $conversation = $this->_data['originalDetectIntentRequest']['payload']['conversation'] ?? [];
        $this->_intentName = $this->_data['queryResult']['intent']['displayName'];
        $this->_intentType = $this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['intent'] ?? null;

        $this->_logger->info( 'Got intent type ['.$this->_intentType.'] and name ['.$this->_intentName.']');

        if ( empty( $this->_intentType) && !empty( $this->_intentName)) {
            $this->_logger->warning( 'No intent type in request but intent is resolved. Using default ['.IActionsIntent::MAIN.']');
            $this->_intentType  =   IActionsIntent::MAIN;
        }

        if ($this->_canAccessUserStorage() && !$this->_hasUserStorage()) {
            $this->_preparedInstallationId = StrUtil::uuidV4();
            $this->_installationId = $this->_preparedInstallationId;
        } else if ($this->_canAccessUserStorage() && $this->_hasUserStorage()) {
            $this->_installationId = $this->_prepareInstallationIdFromUserStorage();
            $this->_preparedInstallationId = $this->_installationId;
        } else {
            $this->_installationId = $conversation['conversationId'];
        }

        $this->_sessionId           =   $conversation['conversationId'];
        $this->_conversationType    =   $conversation['type'];
        $this->_accessToken = $this->_data['originalDetectIntentRequest']['payload']['user']['accessToken'] ?? null;

        switch ($this->_intentType)
        {
            case IActionsIntent::MAIN:
                if ( isset( $this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'][0]['name'])
                    && $this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'][0]['name'] == 'trigger_query') {
                    $this->_text = $this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'][0]['rawText'];
                    $this->_logger->debug( 'Got launch request query ['.$this->_text.']');
                }
                break;
            // todo add more IActionsIntents over time
            case IActionsIntent::CONFIRMATION:
            case IActionsIntent::DATETIME:
            case IActionsIntent::OPTION:
                $this->_intentName = $this->_intentType;
                $this->_selectedOption = $this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'][0]['textValue'];
                $this->_logger->debug( 'Got event text ['.$this->_text.']');
                $this->_logger->debug( 'Got selected option ['.$this->_selectedOption.']');
                break;
            case IActionsIntent::TEXT:
                $this->_text = $this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'][0]['rawText'];
                break;
            case IActionsIntent::MEDIA_STATUS:
                // todo look if more more media statuses are supported
                // for now only media status finished is supported
                // more info at:
                // https://stackoverflow.com/questions/59713880/is-there-a-way-to-capture-handle-next-and-previous-using-google-actions-wi
                $this->_intentName = $this->_intentType;
                $this->_isMediaRequest = true;
                $this->_text = 'skip';
                break;
            case IActionsIntent::NO_INPUT:
                $this->_isRePromptRequest = true;
                break;
            case IActionsIntent::CANCEL:
            case IActionsIntent::ASSISTANT_CANCEL:
                $this->_intentName = $this->_intentType;
                $this->_text = 'exit';
                break;
            default:
                throw new \Exception( "Unexpected intent in [".print_r( $this->_data, true)."]");
        }
    }


    private function _initWithQueryResult() {
        $this->_requestId = $this->_data['responseId'] ?? '';
        $this->_intentName = $this->_data['queryResult']['intent']['displayName'];
        //$this->_intentType = IActionsIntent::TEXT;
        $this->_sessionId = StrUtil::uuidV4();
        $this->_accessToken = StrUtil::uuidV4();
        $this->_text = $this->_data['queryResult']['queryText'];
    }

	private function _useOriginalISlotValuefExists( $name, $value) {

	    if ( isset( $this->_data['queryResult']['outputContexts']) && is_array( $this->_data['queryResult']['outputContexts'])) {
	        foreach ( $this->_data['queryResult']['outputContexts'] as $context) {
	            if ( isset( $context['parameters'][$name.'.original']) && !empty( $context['parameters'][$name.'.original'])) {
	                return $context['parameters'][$name.'.original'];
	            }
	        }
	    }

	    return $value;
	}

    private function _isSlotValid($key, $slot) {
        $this->_logger->debug( 'Slot data to validate: '.$key.' ['.print_r($slot, true).']');

		if ( !isset($slot) && empty($slot)) {
			$this->_logger->debug( 'Found empty slot ['.$key.']');
			return false;
		}

		return true;
	}

    private function _replaceWithUnderscoreKeyName($key)
    {
        return str_replace( '-', '_', $key);
    }

    public function getSlotValues()
    {
        return $this->_slots ?? [];
    }

    public function getRawSlots()
    {
        return $this->_rawSlots ?? [];
    }

    private function _getProjectId() {
        $projectId = $this->_data['session'];
        $projectId = explode("/", $projectId);
        return $projectId[1];
    }

    /**
     * @inheritDoc
     */
    public function isLaunchRequest()
    {
        return $this->_intentType === IActionsIntent::MAIN;
    }

    public function isSessionStart() {
        return ($this->isLaunchRequest() || $this->_conversationType == 'NEW');
    }

    /**
     * @inheritDoc
     */
    public function isSessionEndRequest()
    {
        return $this->_isEnd;
    }

    /**
     * @inheritDoc
     */
    public function getServiceId()
    {
        return $this->_serviceId;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty()
    {
        $isEmpty = empty($this->_text);

        if (is_numeric($this->_text)) {
            $isEmpty = false;
        }

        if (is_numeric($this->_selectedOption)) {
            $isEmpty = false;
        }

		if (is_string($this->_selectedOption)) {
			$isEmpty = false;
		}

        return $isEmpty;
    }

    /**
     * @inheritDoc
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * @inheritDoc
     */
    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    /**
     * @inheritDoc
     */
    public function getDeviceId()
    {
        return $this->_deviceId;
    }

    public function getApplicationId()
	{
		return 'UNKNOWN';
	}

    /**
     * @inheritDoc
     */
    public function getInstallationId()
    {
        return $this->_installationId;
    }

    /**
     * @inheritDoc
     */
    public function getSessionId()
    {
        return $this->_sessionId;
    }

    /**
     * @inheritDoc
     */
    public function getRequestId()
    {
        return $this->_requestId;
    }

    /**
     * @inheritDoc
     */
    public function getPlatformData()
    {
        return $this->_data;
    }

    /**
     * @inheritDoc
     */
    public function getPlatformId()
    {
        return self::PLATFORM_ID;
    }

    public function getIntentPlatformId()
    {
        return self::PLATFORM_ID;
    }

    /**
     * @inheritDoc
     */
    public function isMediaRequest()
    {
        if (in_array($this->_intentName, $this->_getDialogflowAudioPlayerIntents())) {
            $this->_isMediaRequest = true;
        }
        return $this->_isMediaRequest;
    }

    /**
     * @inheritDoc
     */
    public function getMediaTypeRequest()
    {
        return IMediaType::MEDIA_TYPE_AUDIO_STREAM;
    }

	public function isSalesRequest() {
		return false;
	}

    private function _getDialogflowAudioPlayerIntents() {
        return [
            "MediaStatus",
            "actions.intent.MEDIA_STATUS",
            "LoopOnIntent",
            "LoopOffIntent",
            "ShuffleOnIntent",
            "ShuffleOffIntent",
            "ContinuePlayback"
        ];
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    // DIALOGFLOW

    public function getIntentName() {
        return $this->_intentName;
    }

    public function getIntentType() {
        return $this->_intentType;
    }

    public function getSelectedOption() {
        return $this->_selectedOption;
    }

    public function getIsWebBrowserSupported() {
        $this->_isWebBrowserSupported = $this->_getCapability(ICapability::WEB_BROWSER);
        return $this->_isWebBrowserSupported;
    }

    public function getIsDisplaySupported() {
        $this->_isDisplaySupported = $this->_getCapability(ICapability::SCREEN_OUTPUT);
        return $this->_isDisplaySupported;
    }

    public function getIsAudioSupported() {
        $this->_isAudioSupported = $this->_getCapability(ICapability::AUDIO_OUTPUT);
        return $this->_isAudioSupported;
    }

    public function getIsMediaResponseAudioSupported() {
        $this->_isMediaResponseAudioSupported = $this->_getCapability(ICapability::MEDIA_RESPONSE_AUDIO);
        return $this->_isMediaResponseAudioSupported;
    }

    public function getIsAccountLinkingSupported() {
        $this->_isAccountLinkingSupported = $this->_getCapability(ICapability::ACCOUNT_LINKING);
        return $this->_isAccountLinkingSupported;
    }

    private function _getCapability($capabilityName) {
        $capabilities = array_column($this->_data['originalDetectIntentRequest']['payload']['surface']['capabilities'], 'name');
        $this->_logger->debug("Capabilities detected in request ".print_r($capabilities, true));
        return in_array($capabilityName, $capabilities);
    }

    private function _hasUserStorage() {
        return isset($this->_data['originalDetectIntentRequest']['payload']['user']['userStorage']);
    }

    private function _canAccessUserStorage() {
        $canAccessUserStorage = false;
        if (isset($this->_data['originalDetectIntentRequest']['payload']['user']['userVerificationStatus'])) {
            $canAccessUserStorage = $this->_data['originalDetectIntentRequest']['payload']['user']['userVerificationStatus'] === 'VERIFIED';
        }
        return $canAccessUserStorage;
    }

    private function _prepareInstallationIdFromUserStorage() {
        $preparedInstallationId = stripslashes($this->_data['originalDetectIntentRequest']['payload']['user']['userStorage']);
        $preparedInstallationId = json_decode($preparedInstallationId, true);
        return $preparedInstallationId['data']['installationId'] ?? $this->_getProjectId();
    }

    public function getPreparedInstallationId() {
        return $this->_preparedInstallationId;
    }

    public function isRePromptRequest() {
        return $this->_isRePromptRequest;
    }

    // UTIL
    public function __toString()
    {
        return get_class( $this).'['.self::PLATFORM_ID.']['.$this->_serviceId.']['.$this->getIntentType().']['.$this->getIntentName().']['.$this->_text.']['.$this->_installationId.']'.
            '['.$this->_isLaunchRequest.']['.$this->_isEnd.']['.json_encode( $this->getSlotValues()).']['.$this->_sessionId.']'.'['.$this->getIsCrossSessionCapable().']';
    }

    /**
     * @inheritDoc
     */
    public function getIsCrossSessionCapable()
    {
        return $this->_canAccessUserStorage();
    }

    /**
     * @inheritDoc
     */
    public function isHealthCheck()
    {
        if (isset($this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'])) {
            $requestArguments = $this->_data['originalDetectIntentRequest']['payload']['inputs'][0]['arguments'];
            foreach ($requestArguments as $requestArgument) {
                if ($requestArgument['name'] === 'is_health_check') {
                    return true;
                }
            }
        }

        return false;
    }

    public function getOffset()
    {
        return 0;
    }

    public function getAudioItemToken()
    {
        return '';
    }
}
