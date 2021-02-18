<?php declare(strict_types=1);

namespace Convo\Core\Params;

/**
 * @author tole
 * @todo no need for interface
 */
class RequestParamsScope implements IServiceParamsScope
{
	/**
	 * @var \Convo\Core\Workflow\IConvoRequest
	 */
	private $_request;

	private $_scopeType;
	private $_levelType;

	public function __construct( $request, $scopeType, $levelType) {
		$this->_request		=	$request;
		$this->_scopeType	=	$scopeType;
		$this->_levelType	=	$levelType;
	}

	/**
	 * @return \Convo\Core\Workflow\IConvoRequest
	 */
	public function getRequest() {
		return $this->_request;
	}

	public function getKey()
	{
		$key	=	$this->_sanitizeIdForDao($this->_request->getDeviceId());

		if ( $this->_scopeType === \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION) {
			$key	.=	'_';
			$key	.=	$this->_sanitizeIdForDao($this->_request->getInstallationId());
		} else if ( $this->_scopeType === \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION) {
			$key	.=	'_';
			$key	.=	$this->_sanitizeIdForDao($this->_request->getInstallationId());
			$key	.=	'_';
			$key	.=	$this->_sanitizeIdForDao($this->_request->getSessionId());
		} else if ( $this->_scopeType === \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST){
			$key	.=	'_';
			$key	.=	$this->_sanitizeIdForDao($this->_request->getRequestId());
		} else {
			throw new \Exception( 'Scope type ['.$this->_scopeType.'] not supported');
		}

		return $key;
	}

	public function getServiceId() {
		return $this->_request->getServiceId();
	}

	public function getScopeType() {
		return $this->_scopeType;
	}

	public function getLevelType() {
		return $this->_levelType;
	}


	// UTIL
    protected function _sanitizeIdForDao($id) {
        return md5( $id);
    }

	public function __toString()
	{
		return get_class( $this).'['.$this->_scopeType.']['.$this->_levelType.']['.$this->getKey().']['.$this->_request.']';
	}

}
