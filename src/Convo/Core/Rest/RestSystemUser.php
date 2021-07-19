<?php declare(strict_types=1);

namespace Convo\Core\Rest;

use Convo\Core\IAdminUser;

class RestSystemUser implements IAdminUser
{
	
	public function __construct()
	{
	}
	
	public function isSystem() {
		return true;
	}
	
	public function getId() {
		throw new \Exception( 'Not to be used here');
	}
	
	public function getEmail() {
		throw new \Exception( 'Not to be used here');
	}
	
	public function getName() {
		throw new \Exception( 'Not to be used here');
	}
	
	public function getUsername() {
		throw new \Exception( 'Not to be used here');
	}

	public function __toString()
	{
		return get_class( $this).'[]';
	}
}