<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Api;

use Convo\Core\Util\IHttpFactory;

class GoogleNlpApi implements \Convo\Pckg\Gnlp\Api\IGoogleNlpApi
{
    const URI_BASE  =   "https://language.googleapis.com/v1/";

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

	/**
	 * @var string
	 */
    private $_apiKey;

    public function __construct( $apiKey, $logger, $httpFctory)
    {
        $this->_apiKey  	=   $apiKey;
        $this->_logger 		= 	$logger;
        $this->_httpFactory	=	$httpFctory;
    }

    public function analyzeTextSyntax( $text)
    {
        $newUrl =   self::URI_BASE.'documents:analyzeSyntax?key='.$this->_apiKey;

        $this->_logger->debug( 'Going to connect to url['.$newUrl.']');

        return $this->_postData( $text, $newUrl);
    }

    public function analyzeTextSentiment( $text)
    {
        $newUrl =   self::URI_BASE.'documents:analyzeSentiment?key='.$this->_apiKey;

        $this->_logger->debug( 'Going to connect to url['.$newUrl.']');

        return $this->_postData( $text, $newUrl);
    }

    public function analyzeTextEntities( $text)
    {
        $newUrl =   self::URI_BASE.'documents:analyzeEntities?key='.$this->_apiKey;

        $this->_logger->debug( 'Going to connect to url['.$newUrl.']');

        return $this->_postData( $text, $newUrl);
    }

    private function _postData($text, $uri)
    {
        $client = $this->_httpFactory->getHttpClient(['timeout' => 20]);

        $postParams = [
            "document" => [
                "type" => "PLAIN_TEXT",
                "language" => "EN",
                "content" => $text
            ],
            "encodingType" => "UTF8"
        ];
        
        $uri = $this->_httpFactory->buildUri($uri);

        $request = $this->_httpFactory->buildRequest(IHttpFactory::METHOD_POST, $uri, [], $postParams);

        $this->_logger->debug('Posting with params ['.print_r($postParams, true).']');

        $response = $client->sendRequest($request);

        if ($response->getStatusCode() !== IHttpFactory::HTTP_STATUS_200) {
        	$responseMessage = json_decode($response->getBody()->__toString(), true);
        	
        	if (isset($responseMessage['error']['message'])) {
        		throw new \Exception('Invalid status in request ['.$response->getStatusCode().'], error ['.$responseMessage['error']['message'].']');
        	}
        
        	throw new \Exception( 'Invalid status in request ['.$response->getStatusCode().']');
        }
        	
        $this->_logger->debug('Got response ['.$response->getBody().']');

        $json = json_decode($response->getBody()->__toString(), true);
        
        return $json;
    }

}