<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Fbm\FacebookMessengerCommandRequest;
use Convo\Core\Adapters\Google\Dialogflow\DialogflowCommandRequest;
use Convo\Core\Adapters\Viber\ViberCommandRequest;
use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Util\StrUtil;

class ServiceImpExpRestHandler implements RequestHandlerInterface
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
	 * @var \Convo\Core\Factory\ConvoServiceFactory
	 */
	private $_convoServiceFactory;

	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
	private $_convoServiceDataProvider;

	/**
	 * @var \Convo\Core\Params\IServiceParamsFactory
	 */
	private $_convoServiceParamsFactory;

	/**
	 * @var \Convo\Core\Publish\PlatformPublisherFactory
	 */
	private $_platformPublisherFactory;


	public function __construct(
	    \Psr\Log\LoggerInterface $logger, $httpFactory, $convoServiceFactory, $serviceDataProvider, $convoServiceParamsFactory, $platformPublisherFactory)
	{
		$this->_logger                        = 	$logger;
		$this->_httpFactory                   = 	$httpFactory;
		$this->_convoServiceDataProvider      = 	$serviceDataProvider;
		$this->_convoServiceFactory           = 	$convoServiceFactory;
		$this->_convoServiceParamsFactory     = 	$convoServiceParamsFactory;
		$this->_platformPublisherFactory      = 	$platformPublisherFactory;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$user	=	$info->getAuthUser();

		if ( $info->post() && $route = $info->route( 'service-imp-exp/import/{serviceId}'))
		{
			return $this->_performConvoProtoImportServicePost( $request, $user, $route->get( 'serviceId'));
		}

		if ( $info->get() && $route = $info->route( 'service-imp-exp/export/{serviceId}'))
		{
			return $this->_performConvoProtoExportServiceGet( $request, $user, $route->get( 'serviceId'));
		}

		if ( $info->get() && $route = $info->route( 'service-imp-exp/export/{serviceId}/{platformId}'))
		{
		    return $this->_performConvoProtoExportServicePlatformGet( $request, $user, $route->get( 'serviceId'), $route->get( 'platformId'));
		}

		if ($info->get() && $route = $info->route('service-imp-exp/export-template/{serviceId}'))
		{
			return $this->_performConvoProtoExportServiceTemplatePost($request, $user, $route->get('serviceId'));
		}

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}

	private function _performConvoProtoImportServicePost(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
	    $original_data	=	$this->_convoServiceDataProvider->getServiceData( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	    $original_meta	=	$this->_convoServiceDataProvider->getServiceMeta( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
		$files			=	$request->getUploadedFiles();

		$post_data		=	$request->getParsedBody();

		$keep_vars		=	$post_data['keep_vars'] ?? false;
		$keep_vars		=	StrUtil::parseBoolean($keep_vars);

		$keep_configs	=	$post_data['keep_configs'] ?? false;
		$keep_configs   =   StrUtil::parseBoolean($keep_configs);

		$this->_logger->info('Going to import service data with keep_vars ['.$keep_vars.'] and keep_configs ['.$keep_configs.']');

		$file			=	$files['service_definition'] ?? null;

		if ( empty( $file)) {
			throw new \Convo\Core\Rest\InvalidRequestException( 'No file to upload provided');
		}

		/* @var \Psr\Http\Message\UploadedFileInterface  $file */
		$this->_logger->info('Got file ['.$file->getClientFilename().']');
		$content		=	$file->getStream()->getContents();
		$service_data	=	json_decode( $content, true);

		if ( false === $service_data) {
			throw new \Convo\Core\Rest\InvalidRequestException('Invalid JSON in ['.$file->getClientFilename().']');
		}

		if (json_last_error() !== 0) {
			throw new \Convo\Core\Rest\InvalidRequestException('Invalid JSON in ['.$file->getClientFilename().']['.json_last_error_msg().']');
		}

		if (isset($service_data['template_id'])) {
            $service_data_from_template = $service_data['service'];
            $service_data_from_template['service_id'] = $original_meta['service_id'];
            $service_data_from_template['name'] = $original_meta['name'];
            $service_data = $service_data_from_template;
		}

		if ( $keep_vars) {
			$service_data['variables']		    =	$original_data['variables'];
            $service_data['preview_variables']  =	$original_data['preview_variables'];
		}

		$service_data['service_id'] = $serviceId;
		$service_data['name'] = $original_meta['name'];

        if ( !$keep_configs && isset($service_data['configurations'])) {
            $previous_conf = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

            try {
                $this->_convoServiceDataProvider->updateServicePlatformConfig($user, $serviceId, $service_data['configurations']);
                $original_meta['release_mapping'] = $service_data['release_mappings'];
                $this->_convoServiceDataProvider->saveServiceMeta($user, $serviceId, $original_meta);
            } catch (\Exception $e) {
                $this->_logger->error($e);
                $this->_convoServiceDataProvider->updateServicePlatformConfig($user, $serviceId, $previous_conf);
            }
        }

        unset($service_data['configurations']);
        unset($service_data['release_mappings']);

        $this->_convoServiceFactory->fixComponentIds( $service_data);
		$this->_convoServiceDataProvider->saveServiceData( $user, $serviceId, $service_data);

		return $this->_httpFactory->buildResponse( array());
	}

	private function _performConvoProtoExportServiceGet( \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		$this->_logger->info('Exporting Convoworks service model');
        $configurations = [];
        $service_data = $this->_convoServiceDataProvider->getServiceData( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

	    $include_configurations = filter_var(($request->getQueryParams()['include_configurations'] ?? false), FILTER_VALIDATE_BOOLEAN);
	    
		if ($include_configurations) {
			$this->_logger->info('Including configurations in export.');
            $configurations = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
            $service_data['configurations'] = $configurations;
        } else {
			$this->_logger->info('Not going to export configurations.');
	        unset($service_data['configurations']);
        }

        if (!empty($configurations)) {
            $service_data['release_mappings'] = [];

            foreach ($configurations as $platform => $configuration) {
				switch ($platform)
				{
					case AmazonCommandRequest::PLATFORM_ID:
						if (isset($configuration['mode']) && $configuration['mode'] === 'auto' && isset($configuration['app_id']) && !empty($configuration['app_id'])) {
							$service_data['release_mappings'][$platform]['a'] = [
								"type" => "develop",
								"time_updated" => $configuration['time_created'] ?? time(),
								"time_propagated" => 0
							];
						}
						break;
					case DialogflowCommandRequest::PLATFORM_ID:
						if (isset($configuration['mode']) && $configuration['mode'] === 'auto' && isset($configuration['serviceAccount']) && !empty($configuration['serviceAccount'])) {
							$service_data['release_mappings'][$platform]['a'] = [
								"type" => "develop",
								"time_updated" => $configuration['time_created'] ?? time(),
								"time_propagated" => 0
							];
						}
						break;
					case FacebookMessengerCommandRequest::PLATFORM_ID:
						if (isset($configuration['page_access_token']) && !empty($configuration['page_access_token'])) {
							$service_data['release_mappings'][$platform]['a'] = [
								"type" => "develop",
								"time_updated" => $configuration['time_created'] ?? time(),
								"time_propagated" => 0
							];
						}
						break;
					case ViberCommandRequest::PLATFORM_ID:
						if (isset($configuration['auth_token']) && !empty($configuration['auth_token'])) {
							$service_data['release_mappings'][$platform]['a'] = [
								"type" => "develop",
								"time_updated" => $configuration['time_created'] ?? time(),
								"time_propagated" => 0
							];
						}
						break;
					case 'convo_chat':
						if (isset($configuration['time_created']) && !empty($configuration['time_created'])) {
							$service_data['release_mappings'][$platform]['a'] = [
								"type" => "develop",
								"time_updated" => $configuration['time_created'] ?? time(),
								"time_propagated" => 0
							];
						}
						break;
					default: 
						throw new \Exception('Unexpected configuration platform ID ['.$platform.']');
				}
            }
        }

		return $this->_httpFactory->buildResponse(json_encode($service_data, JSON_PRETTY_PRINT), 200, [
				'Content-Disposition' => 'attachment; filename="'.$serviceId.'.json"',
				'Content-Type' => 'application/json'
		]);
	}

	private function _performConvoProtoExportServicePlatformGet( \Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $platformId)
	{
	    $serviceMeta   =   $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

	    if (!isset($serviceMeta['release_mapping'][$platformId])) {
	        throw new \Convo\Core\Rest\NotFoundException('Platform ' . $platformId . " has no platform configuration created.");
        }

	    $publisher     =   $this->_platformPublisherFactory->getPublisher( $user, $serviceId, $platformId);
	    $export        =   $publisher->export();

		$this->_logger->info('Exporting ['.$serviceId.'] for ['.$platformId.']');

	    return $this->_httpFactory->buildResponse($export->getContent(), 200, [
	        'Content-Disposition' => 'attachment; filename="'.$export->getFilename().'"',
	        'Content-Type' => $export->getContentType()
		]);
	}

	private function _performConvoProtoExportServiceTemplatePost(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		$service_data = $this->_convoServiceDataProvider->getServiceData($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		unset($service_data['service_id']);
		unset($service_data['time_updated']);
		unset($service_data['intents_time_updated']);
		unset($service_data['name']);

		$queryParams = $request->getQueryParams();

		$name = urldecode($queryParams['name']);
		$template_id = StrUtil::slugify($name);
		$description = urldecode($queryParams['description']);

		$data = [
			'template_id' => $template_id,
			'name' => $name,
			'description' => $description,
			'service' => $service_data
		];

		return $this->_httpFactory->buildResponse(json_encode($data, JSON_PRETTY_PRINT), 200, [
	        'Content-Disposition' => 'attachment; filename="'.$template_id.'.template.json"',
	        'Content-Type' => 'application/json'
		]);
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
