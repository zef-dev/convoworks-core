<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;


/**
 * Helper class which purpose is to group all core convo handlers into single one, ending up with just one convo route to map in your implementation
 * @author Tole
 *
 */
class AdminRestApi implements RequestHandlerInterface
{

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	/**
	 * @var ContainerInterface
	 */
	private $_container;

	public function __construct( $logger, $container)
	{
		$this->_logger						= 	$logger;
		$this->_container					= 	$container;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$this->_logger->info( 'Got info ['.$info.']');

		if ( $info->startsWith( 'services')) {
		    $class_name	=	'\Convo\Core\Admin\ServicesRestHandler';
		} else if ( $info->startsWith( 'service-versions') || $info->startsWith( 'service-releases')) {
		    $class_name	=	'\Convo\Core\Admin\ServiceVersionsRestHandler';
		} else if ( $info->startsWith( 'user-packages')) {
		    $class_name	=	'\Convo\Core\Admin\UserPackgesRestHandler';
		} else if ( $info->startsWith( 'service-packages')) {
		    $class_name =   '\Convo\Core\Admin\ServicePackagesRestHandler';
        } else if ( $info->startsWith( 'service-test')) {
		    $class_name	=	'\Convo\Core\Admin\TestServiceRestHandler';
		} else if ( $info->startsWith( 'service-imp-exp')) {
		    $class_name	=	'\Convo\Core\Admin\ServiceImpExpRestHandler';
		} else if ( $info->startsWith( 'service-platform-config') || $info->startsWith( 'service-platform-propagate')) {
		    $class_name	=	'\Convo\Core\Admin\ServicePlatformConfigRestHandler';
		} else if ( $info->startsWith( 'media')) {
		    $class_name	=	'\Convo\Core\Admin\MediaRestHandler';
		} else if ( $info->startsWith( 'user-platform-config')) {
			$class_name	=	'\Convo\Core\Admin\UserPlatformConfigRestHandler';
		} else if ( $info->startsWith( 'package-help')) {
            $class_name	=	'\Convo\Core\Admin\ComponentHelpRestHandler';
        } else if ( $info->startsWith('templates')) {
		    $class_name =   '\Convo\Core\Admin\TemplatesRestHandler';
        } else if ($info->startsWith( 'config-options')) {
		    $class_name =   '\Convo\Core\Admin\ConfigurationRestHandler';
        } else if ($info->startsWith( 'get-existing-alexa-skill')) {
		    $class_name = '\Convo\Core\Admin\AmazonAlexaSkillInfo';
        } else if ($info->startsWith( 'supply-urls')) {
		    $class_name = '\Convo\Proto\URLSupplierRestHandler';
        } else {
		    throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
		}

		$this->_logger->info( 'Searching for handler ['.$class_name.']');

		/* @var \Psr\Http\Server\RequestHandlerInterface $handler */
		$handler	=	$this->_container->get( $class_name);
		return $handler->handle( $request);
	}


	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
