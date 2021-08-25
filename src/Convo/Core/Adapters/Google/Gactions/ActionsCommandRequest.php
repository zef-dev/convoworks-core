<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Google\Gactions;

use Convo\Core\Adapters\Google\Common\Intent\IActionsIntent;
use Convo\Core\Workflow\IConvoRequest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ActionsCommandRequest implements IConvoRequest, LoggerAwareInterface
{
	const PLATFORM_ID   =   'google_actions';
	const DEFAULT_CONVERSATION_TOKEN    =   '{ "state": null, "data": {} }';

	private $_serviceId;
	private $_deviceId;
	private $_installationId;
	private $_sessionId;

	private $_text;

	private $_data;

	private $_accessToken;

	private $_conversationToken;

	private $_isLaunch  =   false;
	private $_isEnd     =   false;
	private $_isMediaRequest     =   false;

	private $_conversationType   =   '';

	/**
	 * Logger
	 *
	 * @var LoggerInterface
	 */
	private $_logger;

	public function __construct( $serviceId, $data)
	{
		$this->_serviceId   =   $serviceId;
		$this->_data        =   $data;

		$this->_deviceId    =   'UNKNOWN';

		$this->_logger		=	new NullLogger();
	}

	public function setLogger( LoggerInterface $logger)
	{
		$this->_logger	=	$logger;
	}

    /**
     * @throws \Exception
     */
	public function init()
	{
	    $this->_installationId      = isset($this->_data['user']['userId']) ? $this->_data['user']['userId'] : null;
		$this->_sessionId           =   $this->_data['conversation']['conversationId'];
		$this->_conversationType    =   $this->_data['conversation']['type'];

		$this->_conversationToken   =   isset( $this->_data['conversation']['conversationToken']) ?
			$this->_data['conversation']['conversationToken'] : self::DEFAULT_CONVERSATION_TOKEN;

		$this->_accessToken = $this->_data['user']['accessToken'] ?? null;

		switch ( $this->_data['inputs'][0]['intent'])
		{
			case IActionsIntent::MAIN:
				$this->_isLaunch    =   true;
				if ( isset( $this->_data['inputs'][0]['arguments'][0]['name'])
						&& $this->_data['inputs'][0]['arguments'][0]['name'] == 'trigger_query') {
							$this->_text        =   $this->_data['inputs'][0]['arguments'][0]['rawText'];
							$this->_logger->debug( 'Got launch request query ['.$this->_text.']');
						}
				break;
            case IActionsIntent::CONFIRMATION:
            case IActionsIntent::OPTION:
			case IActionsIntent::TEXT:
				$this->_text        =   $this->_data['inputs'][0]['rawInputs'][0]['query'];
				break;
            case IActionsIntent::MEDIA_STATUS:
                // todo look if more more media statuses are supported
                // for now only media status finished is supported
                // more info at:
                // https://stackoverflow.com/questions/59713880/is-there-a-way-to-capture-handle-next-and-previous-using-google-actions-wi
                $this->_isMediaRequest = true;
                $this->_text = 'skip';
                break;
            case IActionsIntent::NO_INPUT:
                // todo handle no input intent
                break;
			default:
				throw new \Exception( "Unexpected intent [{$this->_data['inputs'][0]['intent']}]");
		}
	}

    private function _canAccessUserStorage() {
        $canAccessUserStorage = false;
        if (isset($this->_data['user']['userVerificationStatus'])) {
            $canAccessUserStorage = $this->_data['user']['userVerificationStatus'] === 'VERIFIED';
        }
        return $canAccessUserStorage;
    }

	/**
	 * @return string
	 */
	public function getRequestId()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function getSessionId()
	{
		return $this->_sessionId;
	}

	/**
	 * @return bool
	 */
	public function isLaunchRequest()
	{
		return $this->_isLaunch;
	}

    public function isSessionStart() {
        return ($this->isLaunchRequest() || $this->_conversationType === 'NEW') && !$this->isMediaRequest();
    }

	/**
	 * @return bool
	 */
	public function isSessionEndRequest()
	{
		return $this->_isEnd;
	}

	/**
	 * @return string
	 */
	public function getServiceId()
	{
		return $this->_serviceId;
	}

	/**
	 * @return bool
	 */
	public function isEmpty()
	{
	    $isEmpty = empty( $this->_text);

	    if (is_numeric($this->_text)) {
	        $isEmpty = false;
        }

		return $isEmpty;
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->_text;
	}

	/**
	 * @return string|null
	 */
	public function getAccessToken()
	{
		return $this->_accessToken;
	}

	/**
	 * @return string
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
	 * @return string
	 */
	public function getInstallationId()
	{
		return $this->_installationId;
	}

	/**
	 * @return array
	 */
	public function getPlatformData()
	{
		return array();
	}

	/**
	 * @return string
	 */
	public function getPlatformId()
	{
		return self::PLATFORM_ID;
	}

    /**
     * @return bool
     */
    public function isMediaRequest()
    {
        return $this->_isMediaRequest;
    }

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.self::PLATFORM_ID.']['.$this->_serviceId.']['.$this->_text.']['.$this->_installationId.']'.
			'['.$this->_sessionId.']['.$this->_isLaunch.']['.$this->_isEnd.']';
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
        return false;
    }
}
