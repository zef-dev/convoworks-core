<?php declare(strict_types=1);

namespace Convo\Core\Adapters\ConvoChat;

class DefaultTextCommandRequest implements \Convo\Core\Workflow\IConvoRequest
{
    const PLATFORM_ID	=	'convo_chat';

	private $_serviceId;

	private $_platformId;
	private $_deviceId;
	private $_installationId;
	private $_sessionId;
	private $_requestId;

	private $_text;

	private $_isLaunch;
	private $_isEnd;

	public function __construct( $serviceId, $installationId, $sessionId, $requestId, $text, $isLaunch=false, $isEnd=false, $platformId = 'UNKNOWN')
	{
		$this->_serviceId		=	$serviceId;

		$this->_platformId		=	$platformId;
		$this->_deviceId		=	'UNKNOWN';
		$this->_installationId	=	$installationId;
		$this->_sessionId		=	$sessionId;
		$this->_requestId		=	$requestId;

		$this->_text			=	$text;
		$this->_isLaunch			=	$isLaunch;
		$this->_isEnd			=	$isEnd;
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
		return array();
	}

    public function isMediaRequest() {
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
