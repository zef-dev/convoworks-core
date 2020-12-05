<?php


namespace Convo\Core\Adapters\Dialogflow;


use Google\Auth\OAuth2;

class DialogflowAuthService
{
    /**
     * @var OAuth2 $_oAuth2
     */
    private $_oAuth2;

    /**
     * @var \Convo\Core\IAdminUser
     */
    private $_user;

    private $_serviceAccount;

    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
    private $_adminUserDataProvider;


    public function __construct($oAuth2, $user,  $serviceAccount, $adminUserDataProvider)
    {
        $this->_oAuth2 = $oAuth2;
        $this->_user = $user;
        $this->_serviceAccount = $serviceAccount;
        $this->_adminUserDataProvider = $adminUserDataProvider;

        $this->_initGoogleOAuth2($serviceAccount);
    }

    private function _initGoogleOAuth2($serviceAccount) {
        $this->_oAuth2->setIssuer($serviceAccount['client_email']);
        $this->_oAuth2->setSub($serviceAccount['client_email']);
        $this->_oAuth2->setSigningAlgorithm('RS256');
        $this->_oAuth2->setSigningKey($serviceAccount['private_key']);
        $this->_oAuth2->setTokenCredentialUri($serviceAccount['token_uri']);
        $this->_oAuth2->setAudience($serviceAccount['token_uri']);
        $this->_oAuth2->setScope(['https://www.googleapis.com/auth/dialogflow']);
    }

    public function provideDialogflowAccessToken() {
        $this->_createAccessTokenEntry();
        $this->_obtainAccessToken();

        return $this->_oAuth2->getAccessToken();
    }

    private function _fetchAccessToken()
    {
        $this->_oAuth2->fetchAuthToken();
        $this->_storeDialogflowAccessToken();
    }

    private function _createAccessTokenEntry() {
        if (!isset($this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())['dialogflow'][$this->_serviceAccount['project_id']])) {
            $this->_fetchAccessToken();
        }
    }

    private function _obtainAccessToken() {
        $storedAccessToken = $this->_adminUserDataProvider->getPlatformConfig($this->_user->getId())['dialogflow'][$this->_serviceAccount['project_id']];

        $this->_oAuth2->setAccessToken($storedAccessToken['access_token']);
        $this->_oAuth2->setExpiresAt($storedAccessToken['expires_at']);

        if ($this->_oAuth2->isExpired()) {
            $this->_fetchAccessToken();
        }
    }

    private function _storeDialogflowAccessToken()
    {
        $userConfig = [
            'dialogflow' => [
                $this->_serviceAccount['project_id'] => [
                    'access_token' => $this->_oAuth2->getAccessToken(),
                    'expires_at' => $this->_oAuth2->getExpiresAt()
                ]
            ]
        ];

        $this->_adminUserDataProvider->updatePlatformConfig($this->_user->getId(), $userConfig);
    }
}