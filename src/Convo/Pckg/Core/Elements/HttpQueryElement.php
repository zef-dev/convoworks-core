<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use \Convo\Core\Util\IHttpFactory;
use Convo\Pckg\Core\InvalidJsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Convo\Core\Util\StrUtil;
use Psr\Http\Message\UriInterface;

class HttpQueryElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    private $_parameters;
    private $_scopeType		=	\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION;
	private $_name;
	private $_url;
	private $_contentType;
	private $_method;
	private $_timeout;
	private $_headers;
	private $_params;
	private $_body;
	private $_cacheTimeout;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_ok;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_nok;

	/**
	 * @var IHttpFactory
     */
	private $_httpFactory;

	/**
	 * @var CacheInterface
     */
	private $_cache;

	public function __construct( $config, IHttpFactory $httpFactory, CacheInterface $cache)
    {
        parent::__construct($config);

        if ( isset( $config['scope_type'])) {
            $this->_scopeType	=	$config['scope_type'];
        }

        $this->_parameters      =   $config['parameters'];

        $this->_httpFactory     =   $httpFactory;
        $this->_cache           =   $cache;

        $this->_name            =   $config['name'];
        $this->_url             =   $config['url'];
        $this->_method          =   $config['method'];
        $this->_contentType     =   $config['content_type'];
        $this->_timeout         =   $config['timeout'] ?? 0;
        $this->_headers         =   $config['headers'];
        $this->_params          =   $config['params'];
        $this->_body            =   $config['body'];
        $this->_cacheTimeout    =   $config['cache_timeout'] ?? 0;

        $this->_ok = $config['ok'] ?? [];
        foreach ( $this->_ok as $okElement) {
            $this->addChild( $okElement);
        }

        $this->_nok = $config['nok'] ?? [];
        foreach ( $this->_nok as $nokElement) {
            $this->addChild( $nokElement);
        }
	}

	public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$name		    =   $this->evaluateString( $this->_name);
		$url	 	    =   $this->evaluateString( $this->_url);
		$method		    =   $this->evaluateString( $this->_method);
		$scope_type     =   $this->evaluateString( $this->_scopeType);
		$parameters     =   $this->evaluateString( $this->_parameters);

        $query_params = [];

        if (!is_array($this->_params) && is_string($this->_params) && StrUtil::startsWith($this->_params, '${'))
        {
            $query_params = $this->evaluateString($this->_params);
        }
        else if (is_array($this->_params))
        {
            foreach ($this->_params as $key => $val) {
                $query_params[$this->evaluateString($key)] = $this->evaluateString($val);
            }
        }

		$uri = $this->_httpFactory->buildUri($url, $query_params);
		$this->_logger->info('Current uri ['.$uri.']['.(print_r($query_params, true)).']');

		if( $parameters == 'block'){
		    $params 	   =	$this->getBlockParams( $scope_type);
		}
		else if( $parameters == 'service'){
		    $params        =    $this->getService()->getServiceParams( $scope_type);
		}
		else {
		    throw new \Exception("Unrecognized parameters type [$parameters]");
		}

        try {
            $content = $this->_getContent( $method, $uri);

            $this->_logger->debug('Response body ['.print_r( $content, true).']');
            $this->_logger->debug('Setting body on http ['.$name.'] namespace');

            $params->setServiceParam( $name, array( 'status' => 200, 'body' => $content));
            $elems  =   $this->_ok;
        } catch ( ClientExceptionInterface $e) {
            $this->_logger->warning( $e);
            $params->setServiceParam( $name, array('status' => $e->getCode(), 'error' => $e->getMessage()));
            $elems  =   $this->_nok;
        } catch ( InvalidJsonException $e) {
            $this->_logger->warning( $e);
            $params->setServiceParam( $name, array('status' => $e->getCode(), 'error' => $e->getMessage()));
            $elems  =   $this->_nok;
        } catch ( \Exception $e) {
            $this->_logger->warning( $e);
            $params->setServiceParam( $name, array('status' => $e->getCode(), 'error' => $e->getMessage()));
            $elems  =   $this->_nok;
        }

        foreach ( $elems as $elem) {
            $elem->read( $request, $response);
        }

	}

	/**
	 * @param string $method
	 * @param UriInterface $uri
	 * @return array
	 */
	private function _getContent( $method, UriInterface $uri)
	{
		$timeout = $this->evaluateString($this->_timeout);
		$cacheTimeout = $this->evaluateString($this->_cacheTimeout);
	    if ( $method === 'GET' && !empty($cacheTimeout) && is_numeric($cacheTimeout)) {
	        $key  =   StrUtil::slugify(get_class($this).'-'.$method.'-'.strval($uri));
	        if ( $this->_cache->has( $key)) {
	            $this->_logger->debug('Getting data from cache ['.$key.']');
	            return $this->_cache->get( $key);
	        }
	    }

	    $contentType    =   $this->evaluateString( $this->_contentType);
	    $body		    =	json_decode( $this->_body ?: '{}', true);

	    $this->_logger->debug('Decoded body ['.print_r($body, true).']');

	    if (!empty($body)) {
            foreach ($body as $key => $value) {
                $body[$key] = $this->evaluateString($value);
            }
        }

	    $parsed_headers = [];

        if (!is_array($this->_headers) && is_string($this->_headers) && StrUtil::startsWith($this->_headers, '${'))
        {
            $parsed_headers = $this->evaluateString($this->_headers);
        }
        else if (is_array($this->_headers))
        {
            foreach ($this->_headers as $name => $value) {
                $parsed_headers[$this->evaluateString($name)] = $this->evaluateString($value);
            }
        }

		$config = array();

		if (!empty($timeout) && is_numeric($timeout)) {
			$config = array( 'timeout' => $timeout);
		}

		$http = $this->_httpFactory->getHttpClient($config);

	    $this->_logger->debug('Current uri ['.$uri.']');
	    $this->_logger->debug('Configured timeout ['.$timeout.']');
	    $this->_logger->debug('Configured cache timeout ['.$cacheTimeout.']');

	    $httpRequest = $this->_httpFactory->buildRequest( $method, $uri, $parsed_headers, $body);

	    $this->_logger->debug('Performing ['.$method.'] on ['.$uri->__toString().']');

	    $apiResponse = $http->sendRequest( $httpRequest->withUri( $uri, true));
	    $this->_logger->debug('Response ['.get_class( $apiResponse).']['.$apiResponse->getStatusCode().']');

	    $content = $this->provideContent( $contentType, $apiResponse);

	    if ( $method === 'GET' && !empty($cacheTimeout) && is_numeric($cacheTimeout)) {
	        $this->_logger->debug( 'Storing data to cache ['.$key.']');
	        $this->_cache->set( $key, $content, $cacheTimeout);
	    }

	    return $content;
	}


	// UTIL
    /**
     * @param string $contentType
     * @param \Psr\Http\Message\ResponseInterface $apiResponse
     * @return mixed
     * @throws InvalidJsonException
     */
    public function provideContent(string $contentType, \Psr\Http\Message\ResponseInterface $apiResponse)
    {
        if ( $contentType === 'AUTO') {
            $headerLine = explode( ';', $apiResponse->getHeaderLine('Content-Type'));
            $headerLine = array_shift( $headerLine);
            
            if ( $headerLine === 'application/json') {
                return $this->_readJson( $apiResponse);
            }
            
            return $apiResponse->getBody()->__toString();
        } else if ( $contentType === 'TEXT')  {
            return $apiResponse->getBody()->__toString();
        } else if ( $contentType === 'JSON')  {
            return $this->_readJson( $apiResponse);
        }
        
        throw new \Exception( 'Unexpected content type parameter ['.$contentType.']'); 
    }
    
    private function _readJson( \Psr\Http\Message\ResponseInterface $apiResponse) {
        $content = json_decode( $apiResponse->getBody()->__toString(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException('The received response was not valid JSON.');
        }
        return $content;
    }

	public function __toString()
	{
		return parent::__toString().'['.$this->_name.']';
	}
}
