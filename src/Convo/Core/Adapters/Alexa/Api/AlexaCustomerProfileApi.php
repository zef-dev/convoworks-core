<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Client\ClientExceptionInterface;

class AlexaCustomerProfileApi extends AlexaApi
{
	const ALEXA_CUSTOMER_PROFILE_FULL_NAME = '/v2/accounts/~current/settings/Profile.name';
	const ALEXA_CUSTOMER_PROFILE_GIVEN_NAME = '/v2/accounts/~current/settings/Profile.givenName';
	const ALEXA_CUSTOMER_PROFILE_EMAIL_ADDRESS = '/v2/accounts/~current/settings/Profile.email';
	const ALEXA_CUSTOMER_PROFILE_PHONE_NUMBER = '/v2/accounts/~current/settings/Profile.mobileNumber';

	public function __construct($logger, $httpFactory)
	{
		parent::__construct($logger, $httpFactory);
	}

	public function getCustomerFullName(AmazonCommandRequest $request) {
		try {
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_CUSTOMER_PROFILE_FULL_NAME);
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

	public function getCustomerGivenName(AmazonCommandRequest $request) {
		try {
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_CUSTOMER_PROFILE_GIVEN_NAME);
		} catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                case 403:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::profile:given_name:read] permission.', null, $e);
                default:
                    throw new \Exception($e->getMessage(), null, $e);
            }
		}
	}

	public function getCustomerEmailAddress(AmazonCommandRequest $request) {
		try {
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_CUSTOMER_PROFILE_EMAIL_ADDRESS);
		} catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                case 403:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::profile:email:read] permission.', null, $e);
                default:
                    throw new \Exception($e->getMessage(), null, $e);
            }
		}
	}

	public function getCustomerPhoneNumber(AmazonCommandRequest $request) {
		try {
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_CUSTOMER_PROFILE_PHONE_NUMBER);
		} catch (ClientExceptionInterface $e) {
            switch ($e->getCode()) {
                case 401:
                case 403:
                    throw new InsufficientPermissionsGrantedException('Missing [alexa::profile:mobile_number:read] permission.', null, $e);
                default:
                    throw new \Exception($e->getMessage(), null, $e);
            }
		}
	}
}
