<?php


namespace Convo\Core\Adapters\Fbm;


class FacebookMessengerApiFactory
{
    private $_logger;
    private $_httpFactory;

    public function __construct($logger, $httpFactory)
    {
        $this->_logger      = $logger;
        $this->_httpFactory = $httpFactory;
    }

    public function getApi($user, $serviceId, $convoServiceDataProvider) {
        return new FacebookMessengerApi($this->_logger, $this->_httpFactory, $user, $serviceId, $convoServiceDataProvider);
    }
}
