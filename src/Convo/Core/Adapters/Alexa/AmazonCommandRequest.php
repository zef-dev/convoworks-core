<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\Workflow\IConvoAudioRequest;

class AmazonCommandRequest implements \Convo\Core\Workflow\IIntentAwareRequest, IConvoAudioRequest
{
	const PLATFORM_ID	=	'amazon';

	private $_serviceId;

	private $_applicationId;

	private $_deviceId;
	private $_installationId = '';
	private $_sessionId = '';
	private $_requestId = '';

	private $_accessToken;

	private $_text;
	private $_offsetMilliseconds;

	private $_intentName;
	private $_intentType;

	private $_data	=	array();

	private $_slots;

	private $_isMediaRequest = false;

    private $_selectedOption;
    private $_isDisplaySupported = false;
    private $_isDisplayInterfaceEnabled = false;
    private $_locale = '';

    private $_isNewSession = true;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	public function __construct( \Psr\Log\LoggerInterface $logger, $serviceId, $requestData)
	{
		$this->_logger			=	$logger;
		$this->_serviceId		=	$serviceId;
		$this->_data			=	$requestData;
	}

	public function init()
	{
		$this->_logger->debug( 'Initializinig amazon request ...');

// 		if ( $this->_data['session']['application']['applicationId'] != $this->_config->getAppId()) {
// 			throw new \Exception( 'Not matching application ids ['.$this->_data['session']['application']['applicationId'].']['.$this->_applicationId.']');
// 		}
                if ( isset($this->_data['session']))
                {
                    $this->_isNewSession    =   $this->_data['session']['new'];
                    $this->_sessionId		=   $this->_data['session']['sessionId'];
                    $this->_applicationId	=   $this->_data['session']['application']['applicationId'];
                    $this->_installationId	=   $this->_data['session']['user']['userId'];
                }

		$this->_applicationId	=   $this->_data['context']['System']['application']['applicationId'];
		$this->_deviceId		=   isset( $this->_data['context']['System']['device']['deviceId']) ?
					$this->_data['context']['System']['device']['deviceId'] : 'UNKONWN';
		$this->_installationId	=   $this->_data['context']['System']['user']['userId'];
		$this->_requestId		=   $this->_data['request']['requestId'];
		$this->_locale		    =   $this->_data['request']['locale'];

		$this->_accessToken		=	$this->_data['context']['System']['user']['accessToken'] ?? null;

		$this->_intentType		=	$this->_data['request']['type'];
		$this->_intentName		=   isset( $this->_data['request']['intent']['name']) ? $this->_data['request']['intent']['name'] : null;
//		$this->_text			=   isset( $this->_data['request']['intent']['slots']['CommandSlot']['value']) ?
//					$this->_data['request']['intent']['slots']['CommandSlot']['value'] : null;

		$this->_offsetMilliseconds = $this->_data['request']['offsetInMilliseconds'] ?? 0;

		if (isset($this->_data['context']['Viewport'])) {
		    $this->_isDisplaySupported = true;
		}

		if (array_key_exists('Display', $this->_data['context']['System']['device']['supportedInterfaces'])) {
		    $this->_isDisplayInterfaceEnabled = true;
        }

		switch( $this->_intentType ) {
			case 'LaunchRequest':
			case 'IntentRequest':
			case 'EndRequest':
				break;
            case 'System.ExceptionEncountered':
                throw new \Exception( 'Exception type ['.$this->_data['request']['error']['type'].']\n Exception message ['.$this->_data['request']['error']['message'].']');

            case 'Display.ElementSelected':
                $this->_selectedOption = $this->_data['request']['token'] ?? null;
                $this->_intentName = $this->_intentType;
                break;
            case 'PlaybackController.NextCommandIssued':
            case 'PlaybackController.PauseCommandIssued':
            case 'PlaybackController.PlayCommandIssued':
            case 'PlaybackController.PreviousCommandIssued':
            case 'AudioPlayer.PlaybackStarted':
            case 'AudioPlayer.PlaybackFinished':
            case 'AudioPlayer.PlaybackStopped':
            case 'AudioPlayer.PlaybackNearlyFinished':
            case 'AudioPlayer.PlaybackFailed':
                $this->_isMediaRequest = true;
                $this->_intentName = $this->_intentType;
                    break;
			case 'SessionEndedRequest':
				if ( $this->_data['request']['reason'] === 'ERROR') {
					$this->_logger->debug( 'Error ['.$this->_data['request']['error']['type'].']['.$this->_data['request']['error']['message'].'] in session ');
				} else if ( $this->_data['request']['reason'] === 'USER_INITIATED') {
					$this->_logger->debug( 'User initiated end');
				} else if ( $this->_data['request']['reason'] === 'EXCEEDED_MAX_REPROMPTS') {
					$this->_logger->debug( 'Excedded max reprompts');
				} else {
					throw new \Exception( 'Not expected session end reason ['.$this->_data['request']['reason'].']');
				}
				break;

			default:
				throw new \Exception( 'Not expected request type ['.$this->_intentType.']');
		}

		if ( isset( $this->_data['request']['intent']['slots'])) {
		    $this->_logger->debug( 'Parsing slots from ['.print_r( $this->_data['request']['intent']['slots'], true).']');
			$this->_slots = $this->_parseSlotValues($this->_data['request']['intent']['slots']);
		}

		$this->_logger->debug( 'Got parsed ['.$this.']');
	}

	public function getLocale() {
	    return $this->_locale;
    }

	public function getSlotValues()
	{
		return $this->_slots ?? [];
	}

	public function getPlatformId() {
		return self::PLATFORM_ID;
	}

	public function getIntentPlatformId() {
		return self::PLATFORM_ID;
	}

	public function isLaunchRequest() {
		return $this->_intentType == 'LaunchRequest';
	}

	public function isSessionEndRequest() {
		return $this->_intentType == 'SessionEndedRequest';
	}


	public function getServiceId() {
		return $this->_serviceId;
	}


	public function isEmpty() {
	    $isEmpty = empty( $this->_text) && empty( $this->_intentName);

	    if (is_numeric($this->_selectedOption)) {
	        $isEmpty = false;
	    }

	    return $isEmpty;
	}

	public function getText() {
		return $this->_text;
	}

	public function getOffsetMilliseconds()
	{
	    return $this->_offsetMilliseconds;
	}

	public function getSelectedOption()
	{
	    return $this->_selectedOption;
	}

	public  function getIsDisplaySupported()
	{
	    return $this->_isDisplaySupported;
	}

    public  function getIsDisplayInterfaceEnabled()
    {
        return $this->_isDisplayInterfaceEnabled;
    }

	public function getAccessToken() {
		return $this->_accessToken;
	}

	public function getDeviceId() {
		return $this->_deviceId;
	}

	public function getInstallationId() {
		return $this->_installationId;
	}

	public function getSessionId() {
		return $this->_sessionId;
	}

	public function getRequestId() {
		return $this->_requestId;
	}


	public function getPlatformData() {
		return $this->_data;
	}


	// AMAZON
	public function isIntentRequest() {
		return !empty( $this->_intentName);
	}
	public function getIntentName() {
		return $this->_intentName;
	}
	public function getIntentType() {
		return $this->_intentType;
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.self::PLATFORM_ID.']['.$this->_serviceId.']['.$this->_intentType.']['.$this->_intentName.']['.$this->_text.']['.json_encode( $this->_slots).']'.
		  		'['.$this->_deviceId.']['.$this->_installationId.']['.$this->_sessionId.']['.$this->_requestId.']';
	}


	private function _parseSlotValues( $slots)
	{
		$values	=	array();

		foreach ( $slots as $key=>$slot)
		{
		    if ( empty( $slot)) {
		        continue;
		    }

		    if ( !$this->_isSlotValid( $slot)) {
		        $this->_logger->debug( 'Found not valid slot ['.$key.']');
		        continue;
		    }

		    $values[$key]	=	$this->_parseSlotValue( $slot);
		    $this->_logger->debug( 'Parsed slot value ['.$key.']['.$values[$key].'] was ['.$slot['value'].']');
		}

		return $values;
	}

	private function _parseSlotValue( $slot)
	{
		if ( !isset( $slot['resolutions'])) {
			return $slot['value'];
		}

		if ( empty( $slot['resolutions']['resolutionsPerAuthority'])) {
			throw new \Exception( 'No resolutionsPerAuthority in slot ['.$slot['name'].']');
		}

		$authority	=	$slot['resolutions']['resolutionsPerAuthority'][0];

		$this->_logger->debug( 'Got authority ['.print_r( $authority, true).']');

		if ( empty( $authority['values'])) {
			throw new \Exception( 'No values in authority in slot ['.$slot['name'].']');
		}

		$value	=	$authority['values'][0];

		$this->_logger->debug( 'Got value ['.print_r( $value, true).']');

		return $value['value']['name'];
	}

	private function _isSlotValid( $slot) {
		if ( !isset( $slot['value'])) {
			$this->_logger->debug( 'Found empty slot ['.$slot['name'].']');
			return false;
		}

		if ( isset( $slot['resolutions']['resolutionsPerAuthority'])) {
			foreach ( $slot['resolutions']['resolutionsPerAuthority'] as $resolution) {
				if ( isset( $resolution['status']['code']) && $resolution['status']['code'] == 'ER_SUCCESS_NO_MATCH') {
					return false;
				}
			}
		}

		return true;
	}

    /**
     * @inheritDoc
     */
    public function isMediaRequest()
    {
        if ($this->_isNewSession && in_array($this->_intentName, $this->_getAlexaAudioPlayerIntents())) {
            $this->_isMediaRequest = true;
        } else if (!$this->_isNewSession && in_array($this->_intentName, array_slice($this->_getAlexaAudioPlayerIntents(), 0, 2))) {
            $this->_isMediaRequest = true;
        }

        return $this->_isMediaRequest;
    }

    private function _getAlexaAudioPlayerIntents() {
        return [
//             "PlaySong",
//             "ContinuePlayback",
            "AMAZON.RepeatIntent",
            "AMAZON.CancelIntent",
            "AMAZON.NextIntent",
            "AMAZON.PreviousIntent",
            "AMAZON.StopIntent",
            "AMAZON.PauseIntent",
            "AMAZON.ResumeIntent",
            "AMAZON.StartOverIntent",
            "AMAZON.LoopOnIntent",
            "AMAZON.LoopOffIntent",
            "AMAZON.ShuffleOnIntent",
            "AMAZON.ShuffleOffIntent",
            "PlaybackController.NextCommandIssued",
            "PlaybackController.PreviousCommandIssued",
            "PlaybackController.PlayCommandIssued",
            "PlaybackController.PauseCommandIssued",
            "AudioPlayer.PlaybackStarted",
            "AudioPlayer.PlaybackNearlyFinished",
            "AudioPlayer.PlaybackFinished",
            "AudioPlayer.PlaybackFailed"
        ];
    }

    /**
     * @inheritDoc
     */
    public function getIsCrossSessionCapable()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isHealthCheck()
    {
        // todo check if the is an way to detect the health check request
        return false;
    }

    public function getOffset()
    {
        return $this->getOffsetMilliseconds();
    }
}
