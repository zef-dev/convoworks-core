<?php


namespace Convo\Core\Adapters\Viber;


use Convo\Core\IAdminUser;

class ViberApi
{
    const VIBER_BASE_API_URL = 'https://chatapi.viber.com';

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

    private $_servicePlatformConfig;

    private $_headers = [];

    public function __construct($logger, $httpFactory)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;
    }

    public function setupViberApi($user, $serviceId, $servicePlatformConfig) {
        $this->_user = $user;
        $this->_serviceId = $serviceId;
        $this->_servicePlatformConfig = $servicePlatformConfig;
        $this->_logger->info("Setup Viber API: " . print_r($this->_servicePlatformConfig, true));
        if (empty($this->_servicePlatformConfig['viber']['auth_token'])) {
            throw new \Exception("Missing auth token.");
        }
        $this->_headers = [
            'X-Viber-Auth-Token' => $this->_servicePlatformConfig['viber']['auth_token']
        ];
    }

    /**
     * FOr use in publisher.
     * @param $url
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function callSetupWebhook($url) {
        $requestBody = [
            "url" => $url,
            "event_types" => $this->_servicePlatformConfig['viber']['event_types'],
            "send_name" => true,
            "send_photo" => true
        ];

        $uri = $this->_httpFactory->buildUri(self::VIBER_BASE_API_URL . '/pa/set_webhook');

        $request = $this->_httpFactory->buildRequest("POST", $uri, $this->_headers, $requestBody);
        $client = $this->_httpFactory->getHttpClient();
        $client->sendRequest($request);
        $response = $client->sendRequest($request);
        $this->_checkResponseCode($response->getBody()->getContents());
    }

    /**
     * For use in rest handler
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function callSendMessage($requestBody) {
        $uri = $this->_httpFactory->buildUri(self::VIBER_BASE_API_URL . '/pa/send_message');
        $request = $this->_httpFactory->buildRequest("POST", $uri, $this->_headers, $requestBody);
        $client = $this->_httpFactory->getHttpClient();
        $response = $client->sendRequest($request);
        $this->_checkResponseCode($response->getBody()->getContents());
    }

    private function _checkResponseCode($responseBody) {
        $response = json_decode($responseBody, true);
        if ($response['status'] != '0') {
            $errorMessage = "Viber API error with status " . $response['status'] . " and message " . $response['status_message'];
            $this->_logger->critical($errorMessage);
            throw new \Exception($errorMessage);
        }
    }
}
