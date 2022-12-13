<?php


namespace Convo\Core\Adapters\Dialogflow;


use Convo\Core\Util\IHttpFactory;
use Google\Auth\OAuth2;

class DialogflowAuthService
{
    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\IAdminUser
     */
    private $_user;

    private $_serviceAccount;

    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;

    private $_platformId;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    CONST GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';


    public function __construct($logger, $httpFactory, $user,  $serviceAccount, $adminUserDataProvider, $platformId)
    {
        $this->_logger = $logger;
        $this->_httpFactory = $httpFactory;
        $this->_user = $user;
        $this->_serviceAccount = $serviceAccount;
        $this->_adminUserDataProvider = $adminUserDataProvider;
        $this->_platformId = $platformId;
    }

    private function _createGoogleJwt($serviceAccount, $scope = 'https://www.googleapis.com/auth/dialogflow') {
        $header = json_encode([
            "alg" => "RS256",
            "typ" => "JWT"
        ]);

        $now = time();
        $expireTime = $now + 3600;

        $payload = json_encode([
            "iss" => $serviceAccount['client_email'],
            "iat" => $now,
            "exp" => $expireTime,
            "scope" => $scope,
            "aud" => "https://oauth2.googleapis.com/token"
        ]);

        // Encode Header to Base64Url String
        $base64UrlHeader = $this->_base64UrlEncode($header);

        // Encode Payload to Base64Url String
        $base64UrlPayload = $this->_base64UrlEncode($payload);

        // Create Signature Hash
        $signature = '';
        openssl_sign(
            $base64UrlHeader . "." . $base64UrlPayload,
            $signature,
            $serviceAccount['private_key'],
            OPENSSL_ALGO_SHA256
        );
        // Encode Signature to Base64Url String
        $base64UrlSignature = $this->_base64UrlEncode($signature);

        // Create JWT
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function provideDialogflowAccessToken() {
        $this->_createAccessTokenEntry();
        $token = $this->_obtainAccessToken();

        return 'Bearer ' . $token['access_token'];
    }

    private function _fetchNewAccessToken()
    {
        $tokenReq = $this->_httpFactory->buildRequest(
            IHttpFactory::METHOD_POST,
            $this->_httpFactory->buildUri(self::GOOGLE_TOKEN_URL),
            [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
            ],
            $this->_toQuery([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->_createGoogleJwt($this->_serviceAccount),
            ])
        );

        $client = $this->_httpFactory->getHttpClient([
            'timeout' => 1000
        ]);

        try {
            $res = $client->sendRequest($tokenReq);
            $response = json_decode($res->getBody()->__toString(), true);
            $this->_storeDialogflowAccessToken(array_merge($response, ['created' => time()]));
            return $response;
        } catch (\Exception $e) { //todo: temporary
            throw $e;
        }
    }

    private function _createAccessTokenEntry() {
        if (!isset($this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->_platformId][$this->_serviceAccount['project_id']])) {
            $this->_logger->info('Going to create a new token entry.');
            $this->_fetchNewAccessToken();
        }
    }

    private function _obtainAccessToken() {
        $storedAccessToken = $this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())[$this->_platformId][$this->_serviceAccount['project_id']];

        $now = time();
        $expires = $storedAccessToken['expires_in'] ?? 0;
        $created = $storedAccessToken['created'] ?? 0;

        if ($now > ($created + $expires) ) {
            $this->_logger->info('Token already expired. Going to fetch a new one.');
            $storedAccessToken = $this->_fetchNewAccessToken();
        }

        return $storedAccessToken;
    }

    private function _storeDialogflowAccessToken($data)
    {
        $userConfig = [
            $this->_platformId => [
                $this->_serviceAccount['project_id'] => [
                    'access_token' => $data['access_token'],
                    'expires_in' => $data['expires_in'],
                    'created' => $data['created']
                ]
            ]
        ];

        $this->_adminUserDataProvider->updatePlatformConfig($this->_user->getId(), $userConfig);
    }

    private function _toQuery($array, $shouldPrefixWithQuestionMark = false, $urlencode = false)
    {
        $query = '';
        $pairs = [];

        if ($shouldPrefixWithQuestionMark) {
            $query .= '?';
        }

        foreach ($array as $key => $val) {
            $val        =   strval( $val);
            $pairs[]    .=  "$key=".($urlencode ? urlencode($val) : $val);
        }

        $query .= implode('&', $pairs);

        return $query;
    }

    private function _base64UrlEncode($str) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($str));
    }
}
