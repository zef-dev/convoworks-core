<?php declare(strict_types=1);

namespace Convo\Core\Adapters\ConvoChat;

class DefaultTextCommandRequest implements \Convo\Core\Workflow\IConvoRequest
{
    const PLATFORM_ID	=	'convo_chat';

	private $_serviceId;

	private $_platformId;
	private $_deviceId;
	private $_applicationId;
	private $_installationId;
	private $_sessionId;
	private $_requestId;

	private $_text;

	private $_isLaunch;
	private $_isEnd;

    private $_platformData;

	public function __construct( $serviceId, $installationId, $sessionId, $requestId, $text, $isLaunch=false, $isEnd=false, $platformId = 'UNKNOWN', $platformData)
	{
		$this->_serviceId		=	$serviceId;

		$this->_platformId		=	$platformId;
		$this->_deviceId		=	'UNKNOWN';
		$this->_applicationId	=	'UNKNOWN';
		$this->_installationId	=	$installationId;
		$this->_sessionId		=	$sessionId;
		$this->_requestId		=	$requestId;

		$this->_text			=	$text;
		$this->_isLaunch			=	$isLaunch;
		$this->_isEnd			=	$isEnd;
		$this->_platformData			=	$platformData;
	}

	public function getPlatformId() {
		return $this->_platformId;
	}

	public function setPlatformId( $platformId) {
		$this->_platformId	=	$platformId;
	}

	public function isLaunchRequest() {
		return $this->_isLaunch;
	}

    public function isSessionStart() {
        return $this->isLaunchRequest();
    }

	public function isSessionEndRequest() {
		return $this->_isEnd;
	}

	public function getServiceId() {
		return $this->_serviceId;
	}


	public function isEmpty() {
	    $isEmpty = empty($this->_text);

	    if (is_numeric($this->_text)) {
	        $isEmpty = false;
        }

		return $isEmpty;
	}

	public function getText() {
		return $this->_text;
	}

	public function getAccessToken()
	{
		return null;
	}

	public function getDeviceId() {
		return $this->_deviceId;
	}

	public function setDeviceId( $deviceId) {
	    $this->_deviceId = $deviceId;
	}

	public function getApplicationId()
	{
		return $this->_applicationId;
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
		return $this->_platformData;
	}

    public function isMediaRequest() {
        return false;
    }

    public function getMediaTypeRequest() {
        return '';
    }

	public function isSalesRequest() {
		return false;
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_platformId.']['.$this->_serviceId.']['.$this->_text.']['.$this->_deviceId.']'.
			'['.$this->_installationId.']['.$this->_sessionId.']['.$this->_requestId.']['.$this->_isLaunch.']['.$this->_isEnd.']';
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
        return false;
    }
}
