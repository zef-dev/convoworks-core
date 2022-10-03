<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Client\ClientExceptionInterface;

class AlexaDeviceAddressApi extends AlexaApi
{

	public function __construct($logger, $httpFactory)
	{
		parent::__construct($logger, $httpFactory);
	}

	public function getCountryAndPostalCode(AmazonCommandRequest $request) {
		try {
            $endpoint = '/v1/devices/'.$request->getDeviceId().'/settings/address/countryAndPostalCode';
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, $endpoint);
		} catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                case 403:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::profile:name:read] permission.', null, $e);
                default:
                    throw new \Exception($e->getMessage(), null, $e);
            }
		}
	}

    public function getAddress(AmazonCommandRequest $request) {
        try {
            $endpoint = '/v1/devices/'.$request->getDeviceId().'/settings/address';
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, $endpoint);
        } catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                case 403:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::profile:name:read] permission.', null, $e);
                default:
                    throw new \Exception($e->getMessage(), null, $e);
            }
        }
    }
}
