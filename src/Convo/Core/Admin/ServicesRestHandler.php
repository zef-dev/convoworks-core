<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\Preview\ServicePreviewBuilder;
use Convo\Core\Rest\OwnerNotSpecifiedException;
use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Util\ArrayUtil;
use Convo\Core\Util\NotImplementedException;

class ServicesRestHandler implements RequestHandlerInterface
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
	 * @var \Convo\Core\Factory\PackageProviderFactory
	 */
	private $_packageProviderFactory;

    /**
     * @var \Convo\Core\Publish\PlatformPublisherFactory
     */
	private $_platformPublisherFactory;

    /**
     * @var \Convo\Core\IAdminUserDataProvider
     */
	private $_adminUserDataProvider;

	public function __construct(
	    $logger,
        $httpFactory,
        $serviceFactory,
		$serviceDataProvider,
		$convoServiceParamsFactory,
        $packageProviderFactory,
        $platformPublisherFactory,
        $adminUserDataProvider
    )
	{
		$this->_logger						= 	$logger;
		$this->_httpFactory					= 	$httpFactory;
		$this->_convoServiceFactory			= 	$serviceFactory;
		$this->_convoServiceDataProvider	= 	$serviceDataProvider;
		$this->_convoServiceParamsFactory	=	$convoServiceParamsFactory;
		$this->_packageProviderFactory	    = 	$packageProviderFactory;
		$this->_platformPublisherFactory    =   $platformPublisherFactory;
		$this->_adminUserDataProvider       =   $adminUserDataProvider;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info	=	new \Convo\Core\Rest\RequestInfo( $request);

		$this->_logger->debug( 'Got info ['.$info.']');

		$user	=	$info->getAuthUser();

		if ( $info->get() && $info->route( 'services'))
		{
			return $this->_performConvoGet( $request, $user);
		}

		if ( $info->post() && $info->route( 'services'))
		{
			return $this->_performConvoServiceCreatePost( $request, $user);
		}

		if ($info->post() && $info->route('services/import')) {
			return $this->_performConvoServicesPathImportPost($request, $user);
		}

		if ( $info->get() && $route = $info->route( 'services/{serviceId}'))
		{
			return $this->_performConvoPathServiceIdGet( $request, $user, $route->get( 'serviceId'));
		}

		if ( $info->put() && $route = $info->route( 'services/{serviceId}'))
		{
			return $this->_performConvoPathServiceIdPut( $request, $user, $route->get( 'serviceId'));
		}

		if ($info->delete() && $route = $info->route('services/{serviceId}'))
        {
            $local_only = filter_var($info->getParameterGet( 'local_only', false), FILTER_VALIDATE_BOOLEAN);
            return $this->_performConvoPathServiceIdDelete( $request, $user, $route->get( 'serviceId'), $local_only);
        }

		if ($info->get() && $route = $info->route('services/{serviceId}/preview'))
		{
			return $this->_performConvoPathServiceIdPathPreviewGet( $request, $user, $route->get('serviceId'));
		}

		if ($info->get() && $route = $info->route('services/{serviceId}/preview/{blockId}'))
        {
            return $this->_performServicesPathServiceIdPathPreviewPathBlockIdGet($request, $user, $route->get('serviceId'), $route->get('blockId'));
        }

		if ($info->get() && $route = $info->route('services/{serviceId}/meta'))
		{
			return $this->_performConvoPathServiceIdPathMetaGet( $request, $user, $route->get('serviceId'));
		}

		if ($info->put() && $route = $info->route('services/{serviceId}/meta'))
		{
			return $this->_performConvoPathServiceIdPathMetaPut($request, $user, $route->get('serviceId'));
		}

		throw new \Convo\Core\Rest\NotFoundException( 'Could not map ['.$info.']');
	}

	private function _performConvoGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user)
	{
		$data = $this->_convoServiceDataProvider->getAllServices( $user);

		return $this->_httpFactory->buildResponse($data);
	}

	private function _performConvoPathServiceIdGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		try {
			$this->_logger->info('Getting service ['.$serviceId.']');
			$this->_convoServiceFactory->migrateService( $user, $serviceId, $this->_convoServiceDataProvider);
			$data = $this->_convoServiceDataProvider->getServiceData( $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
		} catch (\Convo\Core\DataItemNotFoundException $e) {
			throw new \Convo\Core\Rest\NotFoundException( 'Service ['.$serviceId.'] not found', 0, $e);
		}

		return $this->_httpFactory->buildResponse( $data);
	}

	private function _performConvoServiceCreatePost(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user)
	{
		$json = $request->getParsedBody();

		$service_name = $json['service_name'];
		$default_language = $json['default_language'];
		$default_locale = $json['default_locale'];
		$supported_locales = $json['supported_locales'];
 		$is_private = $json['is_private'];
		$template_id = $json['template_id'];
        $service_admins = $json['admins'] ?? [];

		$template_namespace = explode('.', $template_id)[0];
		$provider = $this->_packageProviderFactory->getProviderByNamespace($template_namespace);

		$template = $provider->getTemplate($template_id);
        $this->_convoServiceFactory->fixComponentIds($template['service']);

		$service_id = $this->_convoServiceDataProvider->createNewService(
			$user,
			$service_name,
			$default_language,
			$default_locale,
			$supported_locales,
			$is_private,
			$service_admins,
			$template['service']
		);

		$this->_logger->info('Created new service ['.$service_name.']['.$service_id.'] from template ['.$template_id.']');

		return $this->_httpFactory->buildResponse(['service_id' => $service_id]);
	}

	private function _performConvoServicesPathImportPost(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user)
	{
		$files = $request->getUploadedFiles();
		$file = $files['service_definition'] ?? null;

		if (empty($file)) {
			throw new \Exception('No file uploaded.');
		}

		$this->_logger->info('Got file ['.$file->getClientFilename().']');
		$content = $file->getStream()->getContents();
		$service_data = json_decode($content, true);

		if (!$service_data) {
			throw new \Convo\Core\Rest\InvalidRequestException('Invalid JSON in ['.$file->getClientFilename().']');
		}

		if (json_last_error() !== 0) {
			throw new \Convo\Core\Rest\InvalidRequestException('Invalid JSON in ['.$file->getClientFilename().']['.json_last_error_msg().']');
		}

		$service_name = $service_data['name'];

		$configurations = $service_data['configurations'] ?? null;
		$release_mappings = $service_data['release_mappings'] ?? null; //todo

		unset($service_data['configurations']);
        unset($service_data['release_mappings']);

        $this->_convoServiceFactory->fixComponentIds($service_data);

		$service_id = $this->_convoServiceDataProvider->createNewService(
			$user,
			$service_name,
			'en',
			'en-US',
			['en-US'],
			false,
			[],
			$service_data
		);

		if (!empty($configurations)) {
			$this->_convoServiceDataProvider->updateServicePlatformConfig($user, $service_id, $configurations);
		}

		return $this->_httpFactory->buildResponse(['service_id' => $service_id]);
	}

	private function _performConvoPathServiceIdPut(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		$service = $request->getParsedBody();

		$this->_convoServiceFactory->fixComponentIds($service);

		$old = $this->_convoServiceDataProvider->getServiceData($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

		if (!ArrayUtil::areArraysEqual($old['intents'], $service['intents']) ||
		    !ArrayUtil::areArraysEqual($old['entities'], $service['entities']) ||
		    !isset($service['intents_time_updated']))
		{
			$this->_logger->info('Updating service time updated');
	        $service['intents_time_updated'] = time();
	    }

		$this->_logger->info('Updating service ['.$serviceId.']');
		$data = $this->_convoServiceDataProvider->saveServiceData($user, $serviceId, $service);

		// quickfix
		$meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId);
		if (!isset($meta['owner']) || $meta['owner'] === null) {
		    $this->_logger->warning( 'Owner not set in service ['.$serviceId.']. Fixing it by setting it to current user ['.$user->getEmail().']');
			$meta['owner'] = $user->getUsername();
			$this->_convoServiceDataProvider->saveServiceMeta($user, $serviceId, $meta);
		}

		return $this->_httpFactory->buildResponse($data);
	}

	private function _performConvoPathServiceIdDelete(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $localOnly)
    {
        $report = [
            'successes' => [],
			'warnings' => [],
            'errors' => []
        ];

        $meta = $this->_convoServiceDataProvider->getServiceMeta($user, $serviceId);

        if (isset($meta['owner'])) {
            $owner = $this->_adminUserDataProvider->findUser($meta['owner']);
        } else {
            $this->_logger->warning('Service ['.$serviceId.'] has no owner. Using request user.');
            $owner = $user;
        }

		$this->_logger->info('Deleting service ['.$serviceId.'] local only? ['.($localOnly ? 'true' : 'false').']');

        if ($user->getId() !== $owner->getId()) {
            $report['errors']['convoworks']['service'] = 'User "'.$user->getEmail().'" is not authorized to delete the service "'.$serviceId.'"';
            return $this->_httpFactory->buildResponse($report);
        }

        if (!$localOnly)
        {
			$this->_logger->info('Deleting from remote vendors as well.');

			$platform_config = $this->_convoServiceDataProvider->getServicePlatformConfig($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

			foreach ($platform_config as $platform => $config)
			{
				$this->_logger->info('Trying to delete service from platform ['.$platform.']');
				try {
					$publisher = $this->_platformPublisherFactory->getPublisher(
						$owner, $serviceId, $platform
					);
					$publisher->delete($report);
				} catch (NotImplementedException $e) {
					$this->_logger->info($e->getMessage());
				} catch (\Exception $e) {
					$this->_logger->error($e);
					$report['errors'][$platform]['service'] = $e->getMessage();
				}
			}
        }

        try {
            $this->_convoServiceDataProvider->deleteService($owner, $serviceId);
            $report['successes']['convoworks']['service'] = 'Successfully deleted service ['.$serviceId.']';
        } catch (\Exception $e) {
            $this->_logger->error($e);
            $report['errors']['convoworks']['service'] = $e->getMessage();
        }

        return $this->_httpFactory->buildResponse($report);
    }

	private function _performConvoPathServiceIdPathPreviewGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		$instance = $this->_convoServiceFactory->getService(
		    $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP, $this->_convoServiceParamsFactory
		);

		$this->_logger->info('Building preview for service ['.$serviceId.']');

		$previewBuilder = new ServicePreviewBuilder($serviceId);
		$previewBuilder->setLogger($this->_logger);

		foreach ($instance->getBlocks() as $block) {
			if (!$this->_isBlockApplicable($block)) {
				$this->_logger->debug('Skipping non applicable block [' . $block->getComponentId() . ']');
				continue;
			}

			$previewBuilder->addPreviewBlock($block->getPreview());
		}

		foreach ($instance->getFragments() as $fragment) {
			$previewBuilder->addPreviewBlock($fragment->getPreview(), true);
		}

		return $this->_httpFactory->buildResponse($previewBuilder->getPreview());
	}

	private function _isBlockApplicable($block)
    {
        // session start is fine
        if ($block->getComponentId() === '__sessionStart') {
            return true;
        }

        // otherwise only non system blocks
        return strpos($block->getComponentId(), '__') !== 0;
    }

	private function _performServicesPathServiceIdPathPreviewPathBlockIdGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId, $blockId)
    {
        $instance = $this->_convoServiceFactory->getService(
            $user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP, $this->_convoServiceParamsFactory
        );

        $blocks = $instance->getBlocks();
        $found = null;

        foreach ($blocks as $block)
        {
            // check blocks
            if ($block->getComponentId() === $blockId) {
                $found = $block;
                break;
            }
        }

        if (!$found) {
            // check fragments
            $fragments = $instance->getFragments();

            /** @var \Convo\Core\Workflow\IFragmentComponent $fragment */
            foreach ($fragments as $fragment)
            {
                if ($fragment->getComponentId() === $blockId) {
                    $found = $fragment;
                    break;
                }
            }
        }

        if (!$found) {
            throw new \Exception('No such block with ID ['.$blockId.'] in service ['.$serviceId.']');
        }

		$this->_logger->info('Got block ['.$blockId.']['.$serviceId.']');

        $previewBuilder = new ServicePreviewBuilder($serviceId);
        $previewBuilder->setLogger($this->_logger);

        $previewBuilder->addPreviewBlock($found->getPreview());

        return $this->_httpFactory->buildResponse($previewBuilder->getPreview());
    }

	private function _performConvoPathServiceIdPathMetaGet(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		$meta = $this->_convoServiceDataProvider->getServiceMeta(
			$user, $serviceId
		);

		$this->_logger->info('Getting metadata for service ['.$serviceId.']');

		return $this->_httpFactory->buildResponse( $meta);
	}

	private function _performConvoPathServiceIdPathMetaPut(\Psr\Http\Message\ServerRequestInterface $request, \Convo\Core\IAdminUser $user, $serviceId)
	{
		$body = $request->getParsedBody();

		if (empty($body["owner"])) {
		    throw new OwnerNotSpecifiedException("Please specify an owner for the service [" . $serviceId . "]");
        }

		$this->_logger->info('Updating service meta ['.$serviceId.']['.print_r($body, true).']');

		$existing = $this->_convoServiceDataProvider->getServiceMeta(
			$user, $serviceId
		);

		unset($body['service_id']);
		unset($body['release_mapping']);

        $this->_addOwnerToServiceAdminsInPrivateService($existing, $body);
		$meta = array_merge($existing, $body);

		$this->_logger->debug('Final data to update with ['.print_r($meta, true).']');

		$updated = $this->_convoServiceDataProvider->saveServiceMeta(
			$user, $serviceId, $meta
		);

		return $this->_httpFactory->buildResponse($updated);
	}

	private function _addOwnerToServiceAdminsInPrivateService($existingMeta, &$incomingMetaRequestBody) {
        $isPrivate = $incomingMetaRequestBody['is_private'] ?? false;

        if ($isPrivate) {
            $previousOwnerAsAdminOfPrivateService = $this->_adminUserDataProvider->findUser($existingMeta['owner']);
            $newOwnerOfPrivateService = $this->_adminUserDataProvider->findUser($incomingMetaRequestBody['owner']);

            if ($previousOwnerAsAdminOfPrivateService->getId() !== $newOwnerOfPrivateService->getId()) {
                array_push($incomingMetaRequestBody['admins'], $previousOwnerAsAdminOfPrivateService->getEmail());
                $incomingMetaRequestBody['admins'] = array_unique($incomingMetaRequestBody['admins']);
            }
        }
    }

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
