<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Client\ClientExceptionInterface;

class AmazonUserApi extends AmazonApi
{
    public function __construct($logger, $httpFactory)
    {
        parent::__construct($logger, $httpFactory);
    }

    /**
     * Gets the amazon user from an Alexa device.
     * For more info, please visit {@link https://developer.amazon.com/docs/login-with-amazon/obtain-customer-profile.html#call-profile-endpoint}
     * @param AmazonCommandRequest $request
     * @return mixed
     * @throws \Exception
     */
    public function getAmazonUserFromAlexa(AmazonCommandRequest $request) {
        try {
            return $this->_executeAmazonApiRequest($request, IHttpFactory::METHOD_GET, '/user/profile');
        } catch (ClientExceptionInterface $e) {
            throw new \Exception('Something went wrong, please try later again.', null, $e);
        }
    }
}
