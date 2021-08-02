<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


use Convo\Core\ConvoServiceInstance;

class GoToElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{

	private $_value;
	private $_next;

	public function __construct( $config)
	{
		parent::__construct( $config);

		if ( is_string( $config)) {
			$this->_value	=	$config;
			return ;
		}

		$this->_value	=	$config['value'];
		$this->_next	=	isset( $config['next']) ? $config['next'] : false;
	}

	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$parsed 	=   $this->evaluateString( $this->_value);
		$params		=	$this->getService()->getServiceParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION);

        // SPECIAL
        if ($parsed === ConvoServiceInstance::SERVICE_STATE_NAME) {
            $parsed = $params->getServiceParam(ConvoServiceInstance::SERVICE_STATE_NAME);
        } else if ($parsed === ConvoServiceInstance::SERVICE_STATE_PREV_NAME) {
            $parsed = $params->getServiceParam(ConvoServiceInstance::SERVICE_STATE_PREV_NAME);
        }

		$next = $this->evaluateString($this->_next);

		if ($next) {
			$this->_logger->debug( 'Setting state next at ['.$parsed.'] got from ['.$this->_value.']');
			$params->setServiceParam( \Convo\Core\ConvoServiceInstance::SERVICE_STATE_NEXT_NAME, $parsed);
		} else {
			$this->_logger->debug( 'Setting state at ['.$parsed.'] got from ['.$this->_value.']');
// 			$params->setServiceParam( \Convo\Core\ConvoServiceInstance::SERVICE_STATE_NAME, $parsed);
			throw new \Convo\Core\StateChangedException( $parsed);
		}
	}

	// UTIL
	public function __toString()
	{
		return parent::__toString().'['.$this->_value.']['.$this->_next.']';
	}
}
