<?php declare(strict_types=1);

namespace Convo\Core\Adapters;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use Convo\Core\Factory\IPlatformProvider;
use Psr\Log\LoggerInterface;

/**
 * Helper class which purpose is to group all core convo handlers into single one, ending up with just one convo route to map in your implementation
 * @author Tole
 *
 */
class PublicRestApi implements RequestHandlerInterface
{

	/**
	 * @var LoggerInterface
	 */
	private $_logger;

	/**
	 * @var ContainerInterface
	 */
	private $_container;
	
	
	/**
	 * @var \Convo\Core\Factory\PackageProviderFactory
	 */
	private $_packageProviderFactory;

	/**
	 * @param LoggerInterface $logger
	 * @param ContainerInterface $container
	 */
	public function __construct( $logger, $container)
	{
		$this->_logger						= 	$logger;
		$this->_container					= 	$container;
		$this->_packageProviderFactory      =	$container->get( 'packageProviderFactory');
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$this->_logger->debug( 'Got info ['.$info.']');

		if ( $info->startsWith( 'service-run/external')) {
		    if ( $route = $info->route( 'service-run/external/{packageId}/{platformId}/{variant}/{serviceId}')) {
		        $package_id  =   $route->get( 'packageId');
		        $platform_id  =   $route->get( 'platformId');
		        $provider     =   $this->_packageProviderFactory->getProviderByNamespace( $package_id);
		        if ( $provider instanceof IPlatformProvider) {
		            /* @var IPlatformProvider $provider */
		            $handler      =   $provider->getPlatform( $platform_id)->getPublicRestHandler();
		            return $handler->handle( $request);
		        }
		        throw new \Convo\Core\Rest\NotFoundException( 'No appropriate platform provider found for ['.$package_id.']['.$platform_id.'] at ['.$info.']');
		    }
		    throw new \Convo\Core\Rest\NotFoundException( 'No platform route found for at ['.$info.']');
		}
		
		// AMAZON
		if ( $info->startsWith( 'service-run/alexa-skill') || $info->startsWith( 'service-run/amazon')) {
		    $class_name	=	'\Convo\Core\Adapters\Alexa\AlexaSkillRestHandler';
		} else if ( $info->startsWith( 'admin-auth/amazon')) {
		    $class_name	=	'\Convo\Core\Adapters\Alexa\AmazonAuthRestHandler';

		    // GOOGLE
		} else if ( $info->startsWith( 'service-run/google-actions')) {
		    $class_name	=	'\Convo\Core\Adapters\Google\Gactions\ActionsRestHandler';
		} else if ( $info->startsWith( 'service-run/dialogflow')) {
		    $class_name	=	'\Convo\Core\Adapters\Google\Dialogflow\DialogflowAgentRestHandler';

		    // CONVO_CHAT
		} else if ( $info->startsWith( 'service-run/convo_chat')) {
		    $class_name	=	'\Convo\Core\Adapters\ConvoChat\ConvoChatRestHandler';

		    // FACEBOOK
		} else if ( $info->startsWith( 'service-run/facebook_messenger')) {
		    $class_name	= '\Convo\Core\Adapters\Fbm\FacebookMessengerRestHandler';
		    // VIBER
		} else if ( $info->startsWith( 'service-run/viber')) {
            $class_name	= '\Convo\Core\Adapters\Viber\ViberRestHandler';
        }

		// MEDIA

		else if ( $info->startsWith( 'service-media')) {
			$class_name = '\Convo\Core\Media\MediaRestHandler';
		}

		// CATALOGS

		else if ($info->startsWith('service-catalogs')) {
			$class_name = '\Convo\Core\Adapters\Alexa\CatalogRestHandler';
		}

		else {
			throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
		}

		$this->_logger->debug( 'Searching for handler ['.$class_name.']');

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
