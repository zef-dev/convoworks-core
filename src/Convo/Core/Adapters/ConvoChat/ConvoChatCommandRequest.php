<?php declare(strict_types=1);

namespace Convo\Core\Adapters\ConvoChat;

class ConvoChatCommandRequest extends DefaultTextCommandRequest implements \Convo\Core\Workflow\ITimezoneAwareRequest
{
    const PLATFORM_ID	=	'convo_chat';

    private $_timezone;

    public function __construct( $serviceId, $installationId, $deviceId, $sessionId, $requestId, $timezone, $text, $isLaunch=false, $isEnd=false, $platformData)
	{
	    parent::__construct( $serviceId, $installationId, $sessionId, $requestId, $text, $isLaunch, $isEnd, ConvoChatCommandRequest::PLATFORM_ID, $platformData);
		$this->setDeviceId( $deviceId);
		$this->setTimeZone( $timezone);
	}
	
    public function getTimeZone()
    {
        return $this->_timezone;
    }

    public function setTimeZone( $timezone)
    {
        $this->_timezone = $timezone;
    }


}
