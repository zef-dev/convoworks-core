<?php

namespace Convo\Core\Adapters\Alexa\Api;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\WebApiCaller;

abstract class AlexaApi
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;

	/**
	 * @var WebApiCaller
	 */
	private $_webApiCaller;

	public function __construct($logger, $webApiCaller)
	{
		$this->_logger = $logger;
		$this->_webApiCaller = $webApiCaller;
	}

	/**
	 * @param AmazonCommandRequest $request
	 * @param $method
	 * @param $alexaApiUri
	 * @param array $alexaApiQueryParams
	 * @param array $alexaApiHeaders
	 * @param null $body
	 * @return mixed
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	protected function _executeAlexaApiRequest(AmazonCommandRequest $request, $method, $alexaApiUri, $alexaApiQueryParams = [], $alexaApiHeaders = [], $body = null) {
		$requestData = $request->getPlatformData();
		$alexaBaseApiEndpoint = $requestData['context']['System']['apiEndpoint'] ?? '';
		$alexaApiAccessToken = $requestData['context']['System']['apiAccessToken'] ?? '';
		$alexaEndpointUri = $alexaBaseApiEndpoint . $alexaApiUri;

		if (empty($alexaApiHeaders)) {
			$alexaApiHeaders = ['Authorization' => 'Bearer ' . $alexaApiAccessToken];
		}

		$this->_logger->info('Going to execute request on [' . $method . ' ' . $alexaEndpointUri . ']');

		return $this->_webApiCaller->makeApiRequest($method, $alexaEndpointUri, $alexaApiQueryParams, $alexaApiHeaders, $body);
	}
}