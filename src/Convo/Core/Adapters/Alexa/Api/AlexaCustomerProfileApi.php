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
			if ($e->getCode() === 401) {
				throw new AlexaApiException('The authentication token is malformed or invalid.', $e->getCode());
			} else if ($e->getCode() === 403) {
				throw new AlexaApiException('The user has not permitted the skill to access the Full Name.', $e->getCode());
			} else if ($e->getCode() === 429) {
				throw new AlexaApiException('The skill has been throttled due to an excessive number of requests.', $e->getCode());
			} else if ($e->getCode() === 500) {
				throw new AlexaApiException('An unexpected error occurred.', $e->getCode());
			} else {
				throw new AlexaApiException($e->getMessage(), $e->getCode());
			}
		}
	}

	public function getCustomerGivenName(AmazonCommandRequest $request) {
		try {
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_CUSTOMER_PROFILE_GIVEN_NAME);
		} catch (ClientExceptionInterface $e) {
			if ($e->getCode() === 401) {
				throw new AlexaApiException('The authentication token is malformed or invalid.', $e->getCode());
			} else if ($e->getCode() === 403) {
				throw new AlexaApiException('The user has not permitted the skill to access the Given Name.', $e->getCode());
			} else if ($e->getCode() === 429) {
				throw new AlexaApiException('The skill has been throttled due to an excessive number of requests.', $e->getCode());
			} else if ($e->getCode() === 500) {
				throw new AlexaApiException('An unexpected error occurred.', $e->getCode());
			} else {
				throw new AlexaApiException($e->getMessage(), $e->getCode());
			}
		}
	}

	public function getCustomerEmailAddress(AmazonCommandRequest $request) {
		try {
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_CUSTOMER_PROFILE_EMAIL_ADDRESS);
		} catch (ClientExceptionInterface $e) {
			if ($e->getCode() === 401) {
				throw new AlexaApiException('The authentication token is malformed or invalid.', $e->getCode());
			} else if ($e->getCode() === 403) {
				throw new AlexaApiException('The user has not permitted the skill to access the Email Address.', $e->getCode());
			} else if ($e->getCode() === 429) {
				throw new AlexaApiException('The skill has been throttled due to an excessive number of requests.', $e->getCode());
			} else if ($e->getCode() === 500) {
				throw new AlexaApiException('An unexpected error occurred.', $e->getCode());
			} else {
				throw new AlexaApiException($e->getMessage(), $e->getCode());
			}
		}
	}

	public function getCustomerPhoneNumber(AmazonCommandRequest $request) {
		try {
			return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_CUSTOMER_PROFILE_PHONE_NUMBER);
		} catch (ClientExceptionInterface $e) {
			if ($e->getCode() === 401) {
				throw new AlexaApiException('The authentication token is malformed or invalid.', $e->getCode());
			} else if ($e->getCode() === 403) {
				throw new AlexaApiException('The user has not permitted the skill to access the Phone Number.', $e->getCode());
			} else if ($e->getCode() === 429) {
				throw new AlexaApiException('The skill has been throttled due to an excessive number of requests.', $e->getCode());
			} else if ($e->getCode() === 500) {
				throw new AlexaApiException('An unexpected error occurred.', $e->getCode());
			} else {
				throw new AlexaApiException($e->getMessage(), $e->getCode());
			}
		}
	}
}
