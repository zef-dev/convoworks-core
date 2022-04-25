<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\IHttpFactory;
use Psr\Http\Client\ClientExceptionInterface;

class AlexaPersonProfileApi extends AlexaApi
{
	const ALEXA_PERSON_PROFILE_FULL_NAME = '/v2/persons/~current/profile/name';
	const ALEXA_PERSON_PROFILE_GIVEN_NAME = '/v2/persons/~current/profile/givenName';
	const ALEXA_PERSON_PROFILE_PHONE_NUMBER = '/v2/persons/~current/profile/mobileNumber';

	public function __construct($logger, $httpFactory)
	{
		parent::__construct($logger, $httpFactory);
	}

	public function getPersonFullName(AmazonCommandRequest $request) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_PERSON_PROFILE_FULL_NAME);
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

	public function getPersonGivenName(AmazonCommandRequest $request) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_PERSON_PROFILE_GIVEN_NAME);
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

	public function getPersonPhoneNumber(AmazonCommandRequest $request) {
        try {
            return $this->_executeAlexaApiRequest($request, IHttpFactory::METHOD_GET, self::ALEXA_PERSON_PROFILE_PHONE_NUMBER);
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
