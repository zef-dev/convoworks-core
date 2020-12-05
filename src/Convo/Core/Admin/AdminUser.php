<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\IAdminUser;

class AdminUser implements IAdminUser
{
	private $_id;
	private $_username;
	private $_name;
	private $_email;
	
	public function __construct( $id, $username, $name, $email)
	{
		$this->_id			=	$id;
		$this->_username	=	$username;
		$this->_name		=	$name;
		$this->_email		=	$email;
	}
	
	public function isSystem() {
		return false;
	}
	
	public function getId() {
		return $this->_id;
	}
	
	public function getUsername() {
		return $this->_username;
	}

	public function getEmail() {
		return $this->_email;
	}
	
	public function getName() {
		return $this->_name;
	}
	
	public function __toString()
	{
		return get_class( $this).'['.$this->_id.']['.$this->_email.']['.$this->_name.']';
	}
}