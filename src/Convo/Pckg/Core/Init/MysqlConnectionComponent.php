<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Init;

use Convo\Core\Workflow\AbstractBasicComponent;

/**
 * Class MysqlConnectionComponent
 * @package Convo\Pckg\Core\Init
 * @deprecated
 */

class MysqlConnectionComponent extends AbstractBasicComponent implements \Convo\Core\Workflow\IServiceContext, \Convo\Core\Workflow\IIdentifiableComponent
{

	private $_id;

	private $_host;
	private $_port;
	private $_user;
	private $_pass;
	private $_dbName;

	/**
	 * @var \mysqli
	 */
	private $_conn;

	public function __construct( $properties)
	{
		parent::__construct( $properties);

		$this->_id			=	$properties['id'];

		$this->_host		=	$properties['host'];
		$this->_port		=	$properties['port'];
		$this->_user		=	$properties['user'];
		$this->_pass		=	$properties['pass'];
		$this->_dbName		=	$properties['dbName'];
	}

	public function getComponentId() {
		return $this->_id;
	}

	public function init()
	{
	}

	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @return \mysqli
	 */
	public function _getConnection()
	{
		$port			=	$this->getService()->evaluateString( $this->_port);
		$host			=	$this->getService()->evaluateString( $this->_host);
		$host			=	$port ? $host.':'.$port : $host;
		$user			=	$this->getService()->evaluateString( $this->_user);
		$pass			=	$this->getService()->evaluateString( $this->_pass);
		$name			=	$this->getService()->evaluateString( $this->_dbName);

		$conn			=	new \mysqli( $host, $user, $pass, $name);

		$this->_logger->debug( 'Got mysqli connection');

		if ( $conn->connect_errno) {
			throw new \Exception('Connect Error ['.$conn->connect_errno.']['.$conn->connect_error.']');
		}

		return $conn;
	}


	/**
	 * @return \mysqli
	 */
	public function getComponent() {
		if ( !$this->_conn) {
			$this->_conn	=	$this->_getConnection();
		}
		return $this->_conn;
	}


	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->_id.']';
	}
}
