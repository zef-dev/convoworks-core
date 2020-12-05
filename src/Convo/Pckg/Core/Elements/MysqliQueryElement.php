<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

/**
 * Class MysqliQueryElement
 * @package Convo\Pckg\Core\Elements
 * @deprecated
 */

class MysqliQueryElement extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Workflow\IConversationElement
{

	private $_conn;
	private $_query;
	private $_name;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_conn	=	$properties['conn'];
		$this->_query	=	$properties['query'];
		$this->_name	=	$properties['name'];
	}

	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$scope_type	=	\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
		/* @var $conn \mysqli */
		$context_id	=   $this->evaluateString( $this->_conn);
		$query		=   $this->evaluateString( $this->_query);
		$name		=   $this->evaluateString( $this->_name);

		$context	=	$this->getService()->findContext( $context_id);
		$conn		=	$context->getComponent();

		if ( !is_a( $conn, 'mysqli')) {
			throw new \Exception( 'Could not locate mysql connection ['.$this->_conn.']');
		}

		$result 	= 	$conn->query( $query);

		if ( is_a( $result, 'mysqli_result'))
		{
			/* @var $result \mysqli_result */
			$row	=	$result->fetch_assoc();
			$this->_logger->debug( 'Got row ['.$name.']['.print_r( $row, true).']');
			$this->getBlockParams( $scope_type)->setServiceParam( $name, $row);
		}
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_name.']';
	}
}
