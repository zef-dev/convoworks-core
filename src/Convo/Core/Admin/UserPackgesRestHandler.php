<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Psr\Http\Server\RequestHandlerInterface;

class UserPackgesRestHandler implements RequestHandlerInterface
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
	 * @var \Convo\Core\Factory\PackageProviderFactory
	 */
	private $_packageProviderFactory;

	public function __construct($logger, $httpFactory, $packageProviderFactory)
	{
		$this->_logger						= 	$logger;
		$this->_httpFactory					= 	$httpFactory;
		$this->_packageProviderFactory		= 	$packageProviderFactory;
	}

	public function handle( \Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$this->_logger->debug( 'Got info ['.$info.']');

		$user	=	$info->getAuthUser();

		if ( $info->get() && $info->route( 'user-packages'))
		{
			return $this->_performUserPackagesGet( $request, $user);
		}

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}


	private function _performUserPackagesGet( \Psr\Http\Message\RequestInterface $request, \Convo\Core\IAdminUser $user)
	{
        $available = $this->_packageProviderFactory->getAvailablePackages();

        return $this->_httpFactory->buildResponse($available);
	}


	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
