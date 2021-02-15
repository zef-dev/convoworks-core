<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Publish\IPlatformPublisher;

class TestServiceRestHandler implements RequestHandlerInterface
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
	 * @var \Convo\Core\Factory\PlatformRequestFactory
	 */
	private $_platformRequestFactory;

	public function __construct( $logger, $httpFactory, $serviceFactory, $serviceDataProvider, $serviceParamsFactory, $platformRequestFactory)
	{
		$this->_logger						= 	$logger;
		$this->_httpFactory					= 	$httpFactory;
		$this->_convoServiceFactory			= 	$serviceFactory;
		$this->_convoServiceDataProvider	= 	$serviceDataProvider;
		$this->_convoServiceParamsFactory	= 	$serviceParamsFactory;
		$this->_platformRequestFactory	    = 	$platformRequestFactory;
	}

	public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
	{
		$info			=	new \Convo\Core\Rest\RequestInfo( $request);
		$route 			= 	$info->route( 'service-test/{serviceId}', true);
		$service_id 	= 	$route->get( 'serviceId');
		$user			=	$info->getAuthUser();
		$json			=	$request->getParsedBody();

		$text			=	$json['text'] ?? null;
		$is_init		=	$this->_isInit($json);
		$is_end			=	$json['end'] ?? false;
		$device_id		=	$json['device_id'] ?? false;
		$platform_id	=	$json['platform_id'] ?? null;

		if ( empty( $device_id)) {
			throw new \Convo\Core\Rest\InvalidRequestException( 'Could not get device_id from request body');
		}

		$text_request   =   new \Convo\Core\Adapters\ConvoChat\DefaultTextCommandRequest( $service_id, $device_id, $device_id, $device_id, $text, $is_init, $is_end, $platform_id);
		$text_response	=	new \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse();
		$text_response->setLogger($this->_logger);

		if ( $platform_id) {
// 		    TODO: load & use service owner account
// 		    $service_meta     =   $this->_convoServiceDataProvider->getServiceMeta( $user, $service_id);
// 		    $owner            =   $service_meta['owner'];
		    $text_request     =   $this->_platformRequestFactory->toIntentRequest($text_request, $user, $service_id, $platform_id);
		}

		$service        =   $this->_convoServiceFactory->getService( $user, $service_id, IPlatformPublisher::MAPPING_TYPE_DEVELOP, $this->_convoServiceParamsFactory);
		$exception = [
		    "exceptionMessage" => null,
		    "exceptionStackTrace" => null,
        ];

        try {
            $service->run($text_request, $text_response);
        } catch (\Exception $e) {
            $exception["exceptionMessage"] = $e->getMessage();
            $stack = explode('#', $e->getTraceAsString());
            array_shift($stack);
            $exception["exceptionStackTrace"] = $stack;
            $this->_logger->error($e->getMessage());
            $this->_logger->error($e->getTraceAsString());
        }

        $request_vars = $service->getServiceParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST)->getData();
        $session_vars = $service->getServiceParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION)->getData();
        $installation_vars = $service->getServiceParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION)->getData();

		$child_params = [];

		foreach ($service->getChildren() as $child)
		{
			$child_params[] = $this->_getChildData($service, $child);
		}

		$data	=	array(
            'service_state' => $service->getServiceState(),
            'variables' => [
                'service' => [
					'request' => $request_vars,
					'session' => $session_vars,
					'installation' => $installation_vars
				],
                'component' => $child_params
            ],
            'exception' => $exception
		);

		$data		=	array_merge( $data, $text_response->getPlatformResponse());

		return $this->_httpFactory->buildResponse( $data);
	}

    private function _isInit($json) {
        $isInit = false;

        if (isset($json['lunch'])) {
            $isInit = $json['lunch'];
        } else if (isset($json['launch'])) {
            $isInit = $json['launch'];
        }

        return $isInit;
    }

	private function _getChildData($service, $child)
	{
		$data = [
			// 'id' => $child->getId(),
			'class' => (new \ReflectionClass($child))->getShortName(),
			'params' => $service->getAllComponentParams($child)
		];

		if (is_a($child, '\Convo\Core\Workflow\AbstractWorkflowContainerComponent')) {
			/** @var \Convo\Core\Workflow\AbstractWorkflowContainerComponent $child */
			foreach ($child->getChildren() as $childs_child) {
				$data['children'][] = $this->_getChildData($service, $childs_child);
			}
		}

		return $data;
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
