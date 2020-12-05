<?php

declare(strict_types=1);

namespace Convo\Core\Adapters\Alexa;

use Convo\Core\IAdminUser;
use Convo\Core\Util\IHttpFactory;

class AmazonAuthService
{
    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var string
     */
    private $_publicRestBaseUrl;

    /**
     * HTTP Factory
     *
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * Admin user data provider
     *
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    public function __construct($logger, $publicRestBaseUrl, $httpFactory, $adminUserDataProvider)
    {
        $this->_logger = $logger;
        $this->_publicRestBaseUrl = $publicRestBaseUrl;

        $this->_adminUserDataProvider = $adminUserDataProvider;

        $this->_httpFactory = $httpFactory;
    }

    /**
     * @return \Psr\Http\message\UriInterface
     */
    public function getAuthUri(IAdminUser $user)
    {
        $clientId = $this->getClientId($user);
        $redirectUri = $this->_publicRestBaseUrl.'/admin-auth/amazon';

        $uri = $this->_httpFactory->buildUri('https://www.amazon.com/ap/oa', [
            'state' => base64_encode($user->getEmail()),
            'client_id' => $clientId,
            'scope' => rawurlencode('alexa::ask:skills:readwrite alexa::ask:skills:test alexa::ask:models:readwrite alexa::ask:skills:test alexa::ask:models:read alexa::ask:skills:read alexa::ask:catalogs:readwrite'),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
        ]);

        $this->_logger->debug('Got auth uri [' . $uri->__toString() . ']');

        return $uri;
    }

    public function redeemCodeForAccessToken($user, $code)
    {
        $clientId = $this->getClientId($user);
        $clientSecret = $this->getClientSecret($user);
        $redirectUri = $this->_publicRestBaseUrl.'/admin-auth/amazon';

        $tokenReq = $this->_httpFactory->buildRequest(
            IHttpFactory::METHOD_POST,
            $this->_httpFactory->buildUri('https://api.amazon.com/auth/o2/token'),
            [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
            ],
            $this->_toQuery([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ], false, true)
        );

        $client = $this->_httpFactory->getHttpClient([
            'timeout' => 1000
        ]);

        $this->_logger->debug('Got final request ['.$tokenReq->getUri()->__toString().']['.$tokenReq->getBody()->__toString().']');

        try {
            $res = $client->sendRequest($tokenReq);

            return $res;
        } catch (\Exception $e) { //todo: temporary
            $this->_logger->error($e->getMessage());
            throw $e;
        }
    }

    public function refreshExpiredToken(IAdminUser $user)
    {
        $user_platform_config = $this->_adminUserDataProvider->getPlatformConfig($user->getId());

        $clientId = $this->getClientId($user);
        $clientSecret = $this->getClientSecret($user);
        $amazonConfig = $user_platform_config[AmazonCommandRequest::PLATFORM_ID]['client_auth'];

        $now = time();

        $expires = $amazonConfig['expires_in'];
        $created = $amazonConfig['created'] ?? 0;

        if ($now < $created + $expires) {
            $this->_logger->debug('No need to refresh token yet.');
            return;
        }

        $tokenReq = $this->_httpFactory->buildRequest(
            IHttpFactory::METHOD_POST,
            $this->_httpFactory->buildUri('https://api.amazon.com/auth/o2/token'),
            [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
            ],
            $this->_toQuery([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $amazonConfig['refresh_token']
            ], false, true)
        );

        $client = $this->_httpFactory->getHttpClient([
            'timeout' => 1000
        ]);

        $this->_logger->debug('Got final request ['.$tokenReq->getUri()->__toString().']['.$tokenReq->getBody()->__toString().']');

        try {
            $res = $client->sendRequest($tokenReq);
            $body = json_decode($res->getBody()->__toString(), true);

            $this->storeAuthCredentials(
                $user,
                array_merge($body, ['created' => time()])
            );

            return $body;
        } catch (\Exception $e) { //todo: temporary
            $this->_logger->error($e->getMessage());
            throw $e;
        }
    }

    public function storeAuthCredentials(IAdminUser $user, $credentials)
    {
        $config = [
            'amazon' => [
                'client_auth' => $credentials
            ]
        ];

        $this->_adminUserDataProvider->updatePlatformConfig($user->getId(), $config);
    }

    public function getAuthCredentials(IAdminUser $user)
    {
        $config = $this->_adminUserDataProvider->getPlatformConfig($user->getId());
        return $config['amazon']['client_auth'];
    }

    public function getClientId(IAdminUser $user)
    {
        return isset($this->_adminUserDataProvider->getPlatformConfig($user->getId())[AmazonCommandRequest::PLATFORM_ID]) ?
            $this->_adminUserDataProvider->getPlatformConfig($user->getId())[AmazonCommandRequest::PLATFORM_ID]['client_id'] : '';
    }

    public function getClientSecret(IAdminUser $user)
    {
        return isset($this->_adminUserDataProvider->getPlatformConfig($user->getId())[AmazonCommandRequest::PLATFORM_ID]) ?
            $this->_adminUserDataProvider->getPlatformConfig($user->getId())[AmazonCommandRequest::PLATFORM_ID]['client_secret'] : '';
    }

    // UTIL
    private function _toQuery($array, $shouldPrefixWithQuestionMark = false, $urlencode = false)
    {
        $query = '';
        $pairs = [];

        if ($shouldPrefixWithQuestionMark) {
            $query .= '?';
        }

        foreach ($array as $key => $val) {
            $pairs[] .= "$key=".($urlencode ? urlencode($val) : $val);
        }

        $query .= implode('&', $pairs);

        $this->_logger->debug("Final query [$query]");

        return $query;
    }

    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
