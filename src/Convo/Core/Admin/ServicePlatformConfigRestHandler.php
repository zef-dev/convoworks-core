<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Publish\IPlatformPublisher;

class ServicePlatformConfigRestHandler implements RequestHandlerInterface
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
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	/**
	 * @var \Convo\Core\Publish\PlatformPublisherFactory
	 */
	private $_platformPublisherFactory;

	/**
	 * @var \Convo\Core\Publish\ServiceReleaseManager
	 */
	private $_serviceReleaseManager;

    /**
     * @var \Convo\Core\Admin\PropagationErrorReport
     */
    private $_propagationErrorReport;


	public function __construct( $logger, $httpFactory, $serviceDataProvider, $platformPublisherFactory, $serviceReleaseManager, $propagationErrorReport)
	{
		$this->_logger						= 	$logger;
		$this->_httpFactory					= 	$httpFactory;
		$this->_convoServiceDataProvider	= 	$serviceDataProvider;
		$this->_platformPublisherFactory	= 	$platformPublisherFactory;
		$this->_serviceReleaseManager     	= 	$serviceReleaseManager;
		$this->_propagationErrorReport     	= 	$propagationErrorReport;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$this->_logger->debug( 'Got info ['.$info.']');

		$user	=	$info->getAuthUser();

		if ( $info->get() && $route = $info->route( 'service-platform-config/{serviceId}'))
		{
			return $this->_loadServicePlatformConfig( $request, $user, $route->get( 'serviceId'));
		}


		if ( $info->get() && $route = $info->route( 'service-platform-config/{serviceId}/{platformId}'))
		{
		    return $this->_performServicePlatformPathServiceIdPathPlatformIdConfigGet(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		if ( $info->post() && $route = $info->route( 'service-platform-config/{serviceId}/{platformId}'))
		{
		    return $this->_performServicePlatformPathServiceIdPathPlatformIdConfigPost(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		if ( $info->put() && $route = $info->route( 'service-platform-config/{serviceId}/{platformId}'))
		{
		    return $this->_performServicePlatformPathServiceIdPathPlatformIdConfigPut(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		if ( $info->delete() && $route = $info->route( 'service-platform-config/{serviceId}/{platformId}'))
		{
		    return $this->_performServicePlatformPathServiceIdPathPlatformIdConfigDelete(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		if ( $info->post() && $route = $info->route( 'service-platform-propagate/{serviceId}/{platformId}'))
		{
		    return $this->_performServicePlatformPropagatePathServiceIdPathPlatformIdPost(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		if ( $info->get() && $route = $info->route( 'service-platform-propagate/{serviceId}/{platformId}'))
		{
		    return $this->_performServicePlatformPropagatePathServiceIdPathPlatformIdGet(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		if ( $info->get() && $route = $info->route( 'service-platform-status/{serviceId}/{platformId}'))
		{
		    return $this->_performServicePlatformPropagateStatusPathServiceIdPathPlatformIdGet(
		        $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}

	private function _performServicePlatformPathServiceIdPathPlatformIdConfigGet(
	    \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
	    $config		=	$this->_convoServiceDataProvider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		if ( !isset( $config[$platformId])) {
		    throw new \Convo\Core\Rest\NotFoundException( 'Service ['.$serviceId.'] config ['.$platformId.'] not found');
		}

		return $this->_httpFactory->buildResponse( $config[$platformId]);
	}


	private function _performServicePlatformPathServiceIdPathPlatformIdConfigPost(
	    \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
	    $config		=	$this->_convoServiceDataProvider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		if ( isset( $config[$platformId])) {
		    throw new \Convo\Core\Rest\InvalidRequestException( 'Service ['.$serviceId.'] config ['.$platformId.'] already exists');
		}

		$this->_logger->info('Creating configuration ['.$platformId.'] for service ['.$serviceId.']');

		$json =   $request->getParsedBody();

		if ( empty( $json)) {
		    throw new \Convo\Core\Rest\NotFoundException( 'No configuration data in payload');
		}

		$config[$platformId]  =   $json;
		$config[$platformId]['time_created'] = time();
		$config[$platformId]['time_updated'] = time();

        $publisher	=	$this->_platformPublisherFactory->getPublisher( $user, $serviceId, $platformId);
		$this->_convoServiceDataProvider->updateServicePlatformConfig( $user, $serviceId, $config);

        try {
			$this->_logger->info('Enabling platform publisher');

            $publisher->enable();
            
			return $this->_performServicePlatformPathServiceIdPathPlatformIdConfigGet( $request, $user, $serviceId, $platformId);
        } catch (\Convo\Core\Adapters\Alexa\AlexaSkillPublisherWarningsOccurredException $e) {
            $this->_logger->warning( $e);
            $config		=	$this->_convoServiceDataProvider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

            if ( !isset( $config[$platformId])) {
                throw new \Convo\Core\Rest\NotFoundException( 'Service ['.$serviceId.'] config ['.$platformId.'] not found');
            }

            $response = $config[$platformId];
            $response['warnings'] = json_decode($e->getMessage(), true);

            return $this->_httpFactory->buildResponse($response, 201);
        } catch (\Exception $e) {
            $this->_logger->critical( $e);
            
			// remove release mapping
            $meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
            unset($meta['release_mapping'][$platformId]);
            $this->_convoServiceDataProvider->saveServiceMeta( $user, $serviceId, $meta);

            // remove platform configuration
            unset($config[$platformId]);
            $this->_convoServiceDataProvider->updateServicePlatformConfig( $user, $serviceId, $config);

            // log and report the error
            $errorMessage = $this->_propagationErrorReport->craftErrorReport($e->getMessage(), $platformId);
            return $this->_httpFactory->buildResponse($errorMessage, 400);
        }
	}


	private function _performServicePlatformPathServiceIdPathPlatformIdConfigPut(
	    \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
	    $config		=	$this->_convoServiceDataProvider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		if ( !isset( $config[$platformId])) {
		    throw new \Convo\Core\Rest\NotFoundException( 'Service ['.$serviceId.'] config ['.$platformId.'] not found');
		}

		$json =   $request->getParsedBody();

		if ( empty( $json)) {
		    throw new \Convo\Core\Rest\NotFoundException( 'No configuration data in payload');
		}

		$this->_logger->info('Updating configuration ['.$platformId.'] for service ['.$serviceId.']');

		$config[$platformId]  =   array_merge( $config[$platformId], $json);
		$config[$platformId]['time_updated'] = time();

		$this->_convoServiceDataProvider->updateServicePlatformConfig( $user, $serviceId, $config);

		$config = $this->_convoServiceDataProvider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        return $this->_httpFactory->buildResponse( $config[$platformId]);
	}
	
	private function _performServicePlatformPathServiceIdPathPlatformIdConfigDelete(
	    \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
	    $config		=	$this->_convoServiceDataProvider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		if ( !isset( $config[$platformId])) {
		    throw new \Convo\Core\Rest\NotFoundException( 'Service ['.$serviceId.'] config ['.$platformId.'] not found');
		}

		// RELEASES ?
		// META ?
		
		// PUBLISHER
		$publisher	=	$this->_platformPublisherFactory->getPublisher( $user, $serviceId, $platformId);
		$report     =   [];
		$publisher->delete( $report);
		$this->_logger->info('Platform delete report ['.print_r( $report, true).']');
		
		// CONFIG
		$this->_logger->info('Deleting configuration ['.$platformId.'] for service ['.$serviceId.']');
		unset( $config[$platformId]);
		$this->_convoServiceDataProvider->updateServicePlatformConfig( $user, $serviceId, $config);

        return $this->_httpFactory->buildResponse( []);
	}

	private function _performServicePlatformPropagatePathServiceIdPathPlatformIdPost(
	    \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
        $publisher	=	$this->_platformPublisherFactory->getPublisher( $user, $serviceId, $platformId);

		$this->_logger->info('Propagating changes for service ['.$serviceId.'] to platform ['.$platformId.']');

	    try {
            $publisher->propagate();
            return $this->_performServicePlatformPropagatePathServiceIdPathPlatformIdGet( $request, $user, $serviceId, $platformId);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
	        $errorMessage = $this->_propagationErrorReport->craftErrorReport($e->getMessage(), $platformId);
	        return $this->_httpFactory->buildResponse($errorMessage, 400);
        }
	}

	private function _performServicePlatformPropagatePathServiceIdPathPlatformIdGet(
	    \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
	    $publisher  =   $this->_platformPublisherFactory->getPublisher( $user, $serviceId, $platformId);
        $data       =   $publisher->getPropagateInfo();

		$this->_logger->info('Getting propagation info for ['.$serviceId.']['.$platformId.']');

	    return $this->_httpFactory->buildResponse( $data);
	}

    private function _performServicePlatformPropagateStatusPathServiceIdPathPlatformIdGet(
        \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
    {
        $publisher = $this->_platformPublisherFactory->getPublisher( $user, $serviceId, $platformId);
        $status = $publisher->getStatus();

        return $this->_httpFactory->buildResponse( $status);
    }


	private function _loadServicePlatformConfig(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
	    $config		=	$this->_convoServiceDataProvider->getServicePlatformConfig( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
		return $this->_httpFactory->buildResponse( $config);
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
