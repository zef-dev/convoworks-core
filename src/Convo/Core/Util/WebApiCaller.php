<?php

namespace Convo\Core\Util;

class WebApiCaller
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;

	public function __construct( \Psr\Log\LoggerInterface $logger, IHttpFactory $httpFactory)
	{
		$this->_logger	=	$logger;
		$this->_httpFactory = $httpFactory;
	}

	/**
	 * @param $method
	 * @param $uri
	 * @param array $queryParams
	 * @param array $headers
	 * @param null $body
	 * @param string $version
	 * @return mixed
	 * @throws \Psr\Http\Client\ClientExceptionInterface
	 */
	public function makeApiRequest($method, $uri, $queryParams = [], $headers = [], $body = null, $version = '1.1') {
		$client = $this->_httpFactory->getHttpClient();
		$requestUriString = $this->_httpFactory->buildUri($uri, $queryParams)->__toString();
		$this->_logger->info('Request URI [' . $requestUriString . ']');
		$apiRequest = $this->_httpFactory->buildRequest($method, $requestUriString, $headers, $body, $version);
		$response = $client->sendRequest($apiRequest)->getBody()->__toString();
		$this->_logger->info('Response [' . $response . ']');
		return json_decode($response, true);
	}
}