<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\IHttpFactory;

class AlexaPersonProfileApi extends AlexaApi
{
	const ALEXA_PERSON_PROFILE_FULL_NAME = '/v2/accounts/~current/settings/Profile.name';
	const ALEXA_PERSON_PROFILE_GIVEN_NAME = '/v2/accounts/~current/settings/Profile.givenName';
	const ALEXA_PERSON_PROFILE_PHONE_NUMBER = '/v2/accounts/~current/settings/Profile.mobileNumber';

	public function __construct($logger, $webApiCaller)
	{
		parent::__construct($logger, $webApiCaller);
	}

	public function getPersonFullName(AmazonCommandRequest $request) {
		return $this->_executeAlexaApiRequest($request,IHttpFactory::METHOD_GET,self::ALEXA_PERSON_PROFILE_FULL_NAME);
	}

	public function getPersonGivenName(AmazonCommandRequest $request) {
		return $this->_executeAlexaApiRequest($request,IHttpFactory::METHOD_GET,self::ALEXA_PERSON_PROFILE_GIVEN_NAME);
	}

	public function getPersonPhoneNumber(AmazonCommandRequest $request) {
		return $this->_executeAlexaApiRequest($request,IHttpFactory::METHOD_GET,self::ALEXA_PERSON_PROFILE_PHONE_NUMBER);
	}
}