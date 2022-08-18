<?php
namespace Convo\Core\Adapters\Viber;

class ViberCommandRequest implements \Convo\Core\Workflow\IConvoRequest
{
    const PLATFORM_ID	=	'viber';
    private $_serviceId = "";
    private $_sessionId = "";
    private $_requestId = "";
    private $_text = "";
    private $_data = [];
    private $_isLaunchRequest = false;
    private $_isWebhookRequest = false;
    private $_isMessageRequest = false;
    private $_isEmpty = true;
    private $_isSessionEndRequest = false;
    private $_hasFailed = false;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    public function __construct(\Psr\Log\LoggerInterface $logger, $serviceId, $requestData)
    {
        $this->_logger			= $logger;
        $this->_serviceId		= $serviceId;
        $this->_data            = $requestData;
    }

    public function init() {
        $this->_logger->debug( 'Initializing Viber request ...');
        $event = $this->_data['event'] ?? null;
        $this->_requestId = $this->_data['message_token'];

        switch ($event) {
            case IViberWebhookEventType::WEBHOOK_EVENT:
                $this->_isWebhookRequest = true;
                $this->_logger->info('Webhook verified.');
                break;
            case IViberWebhookEventType::CONVERSATION_STARTED:
                $this->_logger->info("Started conversation by user [" . print_r($this->_data['user'], true) . "]");
                $this->_isLaunchRequest = true;
                $this->_sessionId = $this->_data['user']['id'];
                break;
            case IViberWebhookEventType::DELIVERED:
                $this->_logger->info("DELIVERED");
                $this->_sessionId = $this->_data['user_id'];
                break;
            case IViberWebhookEventType::SEEN:
                $this->_logger->info("SEEN");
                $this->_sessionId = $this->_data['user_id'];
                break;
            case IViberWebhookEventType::FAILED:
                $this->_hasFailed = true;
                $this->_logger->info("FAILED");
                $this->_logger->error($this->_data['desc']);
                $this->_sessionId = $this->_data['user_id'];
                break;
            case IViberWebhookEventType::SUBSCRIBED:
                $this->_logger->info("New user subscription by [" . print_r($this->_data['user'], true) . "]");
                $this->_sessionId = $this->_data['user']['id'];
                break;
            case IViberWebhookEventType::UNSUBSCRIBED:
                $this->_sessionId = $this->_data['user_id'];
                $this->_isSessionEndRequest = true;
                $this->_logger->info("UNSUBSCRIBED by user id [$this->_sessionId]");
                break;
            case IViberWebhookEventType::MESSAGE:
                $this->_isMessageRequest = true;
                $this->_text = $this->_data['message']['text'] ?? '';
                $this->_sessionId = $this->_data['sender']['id'];
                break;
            default;
                throw new \Exception("Unsupported event [" . $event . "]");
        }
    }

    /**
     * @inheritDoc
     */
    public function isLaunchRequest()
    {
        return $this->_isLaunchRequest;
    }

    public function isSessionStart() {
        return $this->isLaunchRequest();
    }

    /**
     * @inheritDoc
     */
    public function isSessionEndRequest()
    {
        return $this->_isSessionEndRequest;
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
        $this->_isEmpty = empty($this->_text);

        if (is_numeric($this->_text)) {
            $this->_isEmpty = false;
        }

        return $this->_isEmpty;
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
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getDeviceId()
    {
        return "UNKNOWN";
    }

    public function getApplicationId()
	{
		return "UNKNOWN";
	}

    /**
     * @inheritDoc
     */
    public function getInstallationId()
    {
        return $this->getServiceId() . "_" . $this->getSessionId();
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

    /**
     * @inheritDoc
     */
    public function isMediaRequest()
    {
        return false;
    }

    public function getMediaTypeRequest() {
        return '';
    }

	public function isSalesRequest() {
		return false;
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

    public function isWebhookRequest() {
        return $this->_isWebhookRequest;
    }

    public function isMessageRequest() {
        return $this->_isMessageRequest;
    }

    public function hasFailed() {
        return $this->_hasFailed;
    }

    public function __toString()
    {
        return get_class( $this).'['.self::PLATFORM_ID.']['.$this->_serviceId.']['.$this->_text.']'. '['.$this->_sessionId.']';
    }
}
