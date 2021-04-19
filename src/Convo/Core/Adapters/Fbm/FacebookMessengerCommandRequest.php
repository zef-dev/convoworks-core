<?php


namespace Convo\Core\Adapters\Fbm;


use Convo\Core\Util\StrUtil;
use Convo\Core\Workflow\IConvoRequest;

class FacebookMessengerCommandRequest implements IConvoRequest
{
    const PLATFORM_ID	=	'facebook_messenger';
    private $_serviceId;
    private $_isLaunchRequest = true;

    private $_webhookEvent;
    private $_requestId;
    private $_sessionId;
    private $_installationId;
    private $_data;

    private $_senderId;
    private $_recipientId;

    private $_text = "";
    private $_postbackPayload = "";
    private $_attachments = [];

    private $_entry = [];

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

    public function init()
    {
        $this->_logger->debug( 'Initializing Facebook Messenger request ...');
        $this->_isLaunchRequest = isset($this->_entry["messaging"][0]["postback"]) && $this->_entry["messaging"][0]["postback"]["payload"] === 'GET_STARTED';
        $this->_webhookEvent = null;
        $this->_requestId = StrUtil::uuidV4();
        $senderId = $this->_entry["messaging"][0]["sender"]["id"];
        $recipientId = $this->_entry["messaging"][0]["recipient"]["id"];
        $this->_sessionId = $senderId;
        $this->_installationId = $senderId . "_" . $recipientId;

        $this->_senderId = $this->_entry["messaging"][0]["sender"]["id"];
        $this->_recipientId = $this->_entry["messaging"][0]["recipient"]["id"];

        if (isset($this->_entry['messaging'][0]['message'])) {
            if (isset($this->_entry['messaging'][0]['message']['text'])) {
                $this->_webhookEvent = 'text_message';
                $this->_text = $this->_entry['messaging'][0]['message']['text'];
            } else if (isset($this->_entry['messaging'][0]['message']['attachments'])) {
                $this->_webhookEvent = 'attachments_message';
                $this->_attachments = $this->_entry['messaging'][0]['message']['attachments'];
            }
        } else if (isset($this->_entry['messaging'][0]['postback'])) {
            $this->_webhookEvent = 'postback';
            $this->_postbackPayload = $this->_entry['messaging'][0]['postback']['payload'];
        } else {
            throw new \Exception( 'Webhook event not found!');
        }
    }

    public function setEntry($entry) {
        $this->_entry = $entry;
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
        return false;
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

        if (!$this->isSessionStart()) {
            if (is_numeric($this->_text)) {
                $isEmpty = false;
            }

            if (!empty($this->_postbackPayload)) {
                $isEmpty = false;
            }

            if (count($this->_attachments) > 0) {
                $isEmpty = false;
            }
        } else {
            $isEmpty = true;
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
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getDeviceId()
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

    /**
     * @inheritDoc
     */
    public function isMediaRequest()
    {
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


    public function __toString()
    {
        return get_class( $this).'['.self::PLATFORM_ID.']['.$this->_serviceId.']['.$this->_text.']'. '['.$this->_sessionId.']';
    }
}
