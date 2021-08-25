<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

class IntentAwareWrapperRequest implements IIntentAwareRequest
{
	/**
	 * @var IConvoRequest
	 */
	private $_parent;
	
	/**
	 * @var array
	 */
	private $_slots    =   [];
	
	/**
	 * @var string
	 */
	private $_intentName;

	/**
	 * @var string
	 */
	private $_platformId;
	
	public function __construct( $parent, $intentName, $slotsData, $platformId)
	{
	    $this->_parent		=	$parent;
	    $this->_intentName	=	$intentName;
		$this->_slots		=	$slotsData;
		
		$this->_platformId	=	$platformId;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IIntentAwareRequest::getSlotValues()
	 */
	public function getSlotValues()
	{
	    return $this->_slots;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Workflow\IIntentAwareRequest::getIntentName()
	 */
	public function getIntentName()
	{
	    return $this->_intentName;
	}
	
	
	public function getPlatformId()
	{
	    return $this->_parent->getPlatformId();
	}

	public function getIntentPlatformId()
	{
		return $this->_platformId;
	}
	
	public function isLaunchRequest() {
	    return $this->_parent->isLaunchRequest();
	}

    public function isSessionStart() {
        return $this->_parent->isSessionStart();
    }

	public function isSessionEndRequest() {
	    return $this->_parent->isSessionEndRequest();
	}
	
	public function getServiceId() {
	    return $this->_parent->getServiceId();
	}
	
	
	public function isEmpty() {
	    return $this->_parent->isEmpty();
	}
	
	public function getText() {
	    return $this->_parent->getText();
	}
	
	public function getAccessToken()
	{
	    return $this->_parent->getAccessToken();
	}

	public function getDeviceId() {
	    return $this->_parent->getDeviceId();
	}

	public function getApplicationId()
	{
		return $this->_parent->getApplicationId();
	}

	public function getInstallationId() {
	    return $this->_parent->getInstallationId();
	}
	
	public function getSessionId() {
	    return $this->_parent->getSessionId();
	}
	
	public function getRequestId() {
	    return $this->_parent->getRequestId();
	}
	
	public function getPlatformData() {
	    return $this->_parent->getPlatformData();
	}

    public function isMediaRequest() {
        return $this->_parent->isMediaRequest();
    }
	
	// UTIL
	public function __toString()
	{
	    return get_class( $this).'['.$this->_intentName.']['.json_encode( $this->_slots).']['.$this->_parent.']';
	}

    /**
     * @inheritDoc
     */
    public function getIsCrossSessionCapable()
    {
        return $this->_parent->getIsCrossSessionCapable();
    }

    /**
     * @inheritDoc
     */
    public function isHealthCheck()
    {
        return $this->_parent->isHealthCheck();
    }
}