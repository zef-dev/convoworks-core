<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Psr\Http\Server\RequestHandlerInterface;

class UserPlatformConfigRestHandler implements RequestHandlerInterface
{	
	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;
	
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;
	
	/**
	 * @var \Convo\Core\IAdminUserDataProvider
	 */
	private $_adminUserDataProvider;
	
	
	public function __construct( $logger, $httpFactory, $adminUserDataProvider)
	{
		$this->_logger						= 	$logger;
		$this->_httpFactory					= 	$httpFactory;
		$this->_adminUserDataProvider	    = 	$adminUserDataProvider;
	}
	
	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);
		
		$this->_logger->debug( 'Got info ['.$info.']');
		
		$user	=	$info->getAuthUser();
		
		if ( $info->get() && $info->route( 'user-platform-config'))
		{
			return $this->_loadUserPlatformConfig( $request, $user);
		}
		
		if ( $info->put() && $info->route( 'user-platform-config'))
		{
			return $this->_updateUserPlatformConfig( $request, $user);
		}

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}
	
	private function _loadUserPlatformConfig(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user)
	{
		$config = $this->_adminUserDataProvider->getPlatformConfig($user->getId());
		
		$this->_logger->info('Getting platform config for user ['.$user->getId().']');
		
		return $this->_httpFactory->buildResponse($config);
	}
	
	private function _updateUserPlatformConfig(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user)
	{
	    $this->_adminUserDataProvider->updatePlatformConfig($user->getId(), $request->getParsedBody());
		
		$this->_logger->info('Updating platform config for user ['.$user->getId().']['.print_r($request->getParsedBody(), true).']');

		$config = $this->_adminUserDataProvider->getPlatformConfig($user->getId());
		
		return $this->_httpFactory->buildResponse( $config);
	}


	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}