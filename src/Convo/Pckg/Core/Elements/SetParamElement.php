<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Util\ArrayUtil;

class SetParamElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{

	private $_scopeType		=	\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION;

	private $_params		=	[];

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		if ( isset( $properties['scope_type'])) {
			$this->_scopeType	=	$properties['scope_type'];
		}

		$this->_params	=	$properties['properties'];
	}

	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$service	=	$this->getService();
		$scope_type =	$this->evaluateString($this->_scopeType);
		$params		=	$service->getServiceParams($scope_type);

		foreach ( $this->_params as $key => $val) {
			$key	=	$this->evaluateString( $key);
			$parsed =   $this->evaluateString( $val);

			if (!ArrayUtil::isComplexKey($key))
			{
				$params->setServiceParam( $key, $parsed);
			}
			else
            {
                $root = ArrayUtil::getRootOfKey($key);
                $final = ArrayUtil::setDeepObject($key, $parsed, $params->getServiceParam($root) ?? []);
                $params->setServiceParam($root, $final);
			}
		}
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.json_encode( $this->_params).']';
	}
}
