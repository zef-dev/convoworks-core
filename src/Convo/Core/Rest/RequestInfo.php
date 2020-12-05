<?php declare(strict_types=1);

namespace Convo\Core\Rest;


class RequestInfo
{	
	/**
	 * @var \Psr\Http\Message\ServerRequestInterface
	 */
	private $_request;
	
	/**
	 * @var array
	 */
	private $_path		=	array();
	
	/**
	 * @var string
	 */
	private $_method;

	private $_paramsGet		=	array();
	private $_paramsPost	=	array();
	
	
	public function __construct( \Psr\Http\Message\ServerRequestInterface $request, $basePath='v1')
	{
        $this->_request = $request;

        $path = $request->getUri()->getPath();

        $pos = strpos($path, $basePath);
        
        if ( $pos === false) {
            $this->_path   =    [];
        } else {
            $path = substr( $path, strpos( $path, $basePath) + strlen($basePath) + 1);
            if ( $path === false) {
                $this->_path   =    [];
            } else {
                $this->_path = explode('/', $path);
            }
        }

        $this->_method = strtolower($request->getMethod());
        $this->_paramsGet = $this->_request->getQueryParams();

        $contentType = $request->getHeaderLine('Content-Type');
        if (strstr($contentType, 'application/x-www-form-urlencoded') || strstr($contentType, 'multipart/form-data')) {
            $this->_paramsPost = $this->_request->getParsedBody();
        }
	}
	
	/**
	 * @return \Psr\Http\Message\ServerRequestInterface
	 */
	public function getRequest()
	{
		return $this->_request;
	}
	
	// PATH
	
	/**
	 * @param string $value
	 * @return boolean
	 */
	public function startsWith( $value)
	{
	    if ( strpos( $value, '/') !== false) {
	        $values    =   explode( '/', $value);
	    } else {
	        $values    =   [$value];
	    }
	    
	    for ( $i=0; $i<count( $values); $i++) {
	        if ( !isset( $this->_path[$i])) {
	            return false;
	        }
	        if ( $this->_path[$i] !== $values[$i]) {
	            return false;
	        }
	    }
	    
		return true;
	}
	
	/**
	 * @param string $path
	 * @param boolean $force
	 * @return NULL|\Convo\Core\Rest\PathInfo
	 */
	public function route( $path, $force=false)
	{
		$path_info	=	new PathInfo( $path);
		
		$parts		=	explode( '/', $path);
		
		if ( count( $parts) !== count( $this->_path)) {
			if ( $force) {
				throw new \Convo\Core\Rest\NotFoundException( 'Could not map path ['.$path.']');
			} else {
				return null;
			}
		}
		
		for ( $i=0; $i<count( $parts); $i++) {
			
			if ( $parts[$i] === $this->_path[$i]) {
				continue;
			}	
			
			if ( strpos( $parts[$i], '{') === 0 && strpos( $parts[$i], '}') === strlen( $parts[$i]) - 1) {
				$key		=	str_replace( '{', '', str_replace( '}', '', $parts[$i]));
				$path_info->add( $key, $this->_path[$i]);
				continue;
			}
			
			if ( $parts[$i] !== $this->_path[$i]) {
				if ( $force) {
					throw new \Convo\Core\Rest\NotFoundException( 'Could not map path ['.$path.']');
				} else {
					return null;
				}
			}	
		}
		
		return $path_info;
	}

	/**
	 * @param int $index
	 * @return string
	 */
	public function pathGet( $index)
	{
		return $this->_path[$index];
	}

	// HTTP METHOD
	/**
	 * @param string $method
	 * @return boolean
	 */
	public function method( $method)
	{
		return $this->_method === strtolower( $method);
	}
	
	/**
	 * @return boolean
	 */
	public function get()
	{
		return $this->method( 'get');
	}
	
	/**
	 * @return boolean
	 */
	public function post()
	{
		return $this->method( 'post');
	}
	
	/**
	 * @return boolean
	 */
	public function put()
	{
		return $this->method( 'put');
	}
	
	/**
	 * @return boolean
	 */
	public function delete()
	{
		return $this->method( 'delete');
	}
	
	// PARAMS
	/**
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function getParameterGet( $name, $default = null)
	{
		if ( isset( $this->_paramsGet[$name])) {
			return $this->_paramsGet[$name];
		}
		return $default;
	}
	
	/**
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function getParameterPost( $name, $default = null)
	{
		if ( isset( $this->_paramsPost[$name])) {
			return $this->_paramsPost[$name];
		}
		return $default;
	}
	
	/**
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public function getParameter( $name, $default = null)
	{
		if ( isset( $this->_paramsPost[$name])) {
			return $this->_paramsPost[$name];
		}
		if ( isset( $this->_paramsGet[$name])) {
			return $this->_paramsGet[$name];
		}
		return $default;
	}
	
	// AUTH
	/**
	 * @throws \Convo\Core\Rest\NotAuthenticatedException
	 * @return \Convo\Core\IAdminUser
	 */
	public function getAuthUser(): \Convo\Core\IAdminUser
	{
		$user	=	$this->_request->getAttribute( \Convo\Core\IAdminUser::class);
		
		if ( !is_a( $user, \Convo\Core\IAdminUser::class)) {
			throw new \Convo\Core\Rest\NotAuthenticatedException( 'Request is not authenticated');
		}
		
		return $user;
	}
	
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_method.']['.implode( '/', $this->_path).']';
	}
}