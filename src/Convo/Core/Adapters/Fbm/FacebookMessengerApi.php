<?php


namespace Convo\Core\Adapters\Fbm;


use Convo\Core\IAdminUser;
use Convo\Core\Publish\IPlatformPublisher;
use Facebook\Facebook;

class FacebookMessengerApi
{
    const FACEBOOK_MESSENGER_SEND_URL = 'https://graph.facebook.com/v7.0/me/messages';
    const FACEBOOK_MESSENGER_PROFILE_URL = 'https://graph.facebook.com/v7.0/me/messenger_profile';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var IAdminUser
     */
    private $_user;

    private $_serviceId;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    private $_serviceConfig;
    /**
     * @var Facebook
     */
    private $_fb;

    public function __construct($logger, $httpFactory, $user, $serviceId, $convoServiceDataProvider)
    {
        $this->_logger      = $logger;
        $this->_httpFactory      = $httpFactory;
        $this->_user = $user;
        $this->_serviceId = $serviceId;
        $this->_convoServiceDataProvider = $convoServiceDataProvider;
        $this->_setupFacebookGraphApi();
    }

    private function _setupFacebookGraphApi() {
        $this->_serviceConfig = $this->_convoServiceDataProvider->getServicePlatformConfig(
            $this->_user,
            $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $this->_fb = new Facebook([
            'app_id' => $this->_serviceConfig['facebook_messenger']['app_id'],
            'app_secret' => $this->_serviceConfig['facebook_messenger']['app_secret']
        ]);
    }

    public function callSendApi($senderPSID, $response) {
        $requestBody = [
            "recipient" => [
                "id" => $senderPSID
            ],
            "message" =>$response
        ];

        $uri = $this->_httpFactory->buildUri(self::FACEBOOK_MESSENGER_SEND_URL, ['access_token' => $this->_serviceConfig['facebook_messenger']['page_access_token']]);
        $request = $this->_httpFactory->buildRequest("POST", $uri, [], $requestBody);
        $client = $this->_httpFactory->getHttpClient();
        $client->sendRequest($request);
    }

    public function callMessengerProfileApi() {
        $requestBody = [
            "get_started" => [
                "payload" => "GET_STARTED"
            ],
            "greeting" => [[
                "locale" => "default",
                "text" => "Hi {{user_full_name}} and welcome to " . $this->_serviceIdToName($this->_serviceId)
            ]]
        ];

        $uri = $this->_httpFactory->buildUri(self::FACEBOOK_MESSENGER_PROFILE_URL,
            ['access_token' => $this->_serviceConfig['facebook_messenger']['page_access_token']]);
        $request = $this->_httpFactory->buildRequest("POST", $uri, [], $requestBody);
        $client = $this->_httpFactory->getHttpClient();
        $client->sendRequest($request);
    }

    public function callSubscriptionsApi($callbackUrl) {
        $appId = $this->_serviceConfig['facebook_messenger']['app_id'];

        $requestBody = [
            'object' => 'page',
            'callback_url' => $callbackUrl,
            'fields' => $this->_provideWebhookEvents(),
            'include_values' => 'true',
            'verify_token' => $this->_serviceConfig['facebook_messenger']['webhook_verify_token'],
        ];
        $this->_fb->post("/{$appId}/subscriptions", $requestBody, $this->_getAccessToken());
    }

    public function callSubscribedApps() {
        $pageId = $this->_serviceConfig['facebook_messenger']['page_id'];
        $pageAccessToken = $this->_serviceConfig['facebook_messenger']['page_access_token'];

        $requestBody = [
            'subscribed_fields' => $this->_provideWebhookEvents()
        ];
        $this->_fb->post("/{$pageId}/subscribed_apps", $requestBody, $pageAccessToken);
    }

    private function _getAccessToken() {
        $appId = $this->_serviceConfig['facebook_messenger']['app_id'];
        $appSecret = $this->_serviceConfig['facebook_messenger']['app_secret'];

        return $appId . '|' . $appSecret;
    }

    private function _serviceIdToName($serviceId)
    {
        $str = str_replace("-", " ", $serviceId);
        $str = ucwords($str);
        return $str;
    }

    private function _provideWebhookEvents() {
        $webhookEvents = $this->_serviceConfig['facebook_messenger']['webhook_events'];
        if (count($webhookEvents) > 0) {
            $this->_serviceConfig['facebook_messenger']['webhook_events'];
        } else {
            $webhookEvents = '';
        }

        return $webhookEvents;
    }
}
