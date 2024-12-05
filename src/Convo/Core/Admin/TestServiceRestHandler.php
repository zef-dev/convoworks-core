<?php

declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Util\StrUtil;
use Psr\Http\Server\RequestHandlerInterface;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Util\ArrayUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Convo\Core\EventDispatcher\ServiceRunRequestEvent;

class TestServiceRestHandler implements RequestHandlerInterface
{
    const DEFAULT_PLATFORM_ID = 'test-chat';

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
     * @var \Convo\Core\Factory\IPlatformRequestFactory
     */
    private $_platformRequestFactory;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    private $_eventDispatcher;

    public function __construct($logger, $httpFactory, $serviceFactory, $serviceDataProvider, $serviceParamsFactory, $platformRequestFactory, EventDispatcher $eventDispatcher)
    {
        $this->_logger                        =     $logger;
        $this->_httpFactory                    =     $httpFactory;
        $this->_convoServiceFactory            =     $serviceFactory;
        $this->_convoServiceDataProvider    =     $serviceDataProvider;
        $this->_convoServiceParamsFactory    =     $serviceParamsFactory;
        $this->_platformRequestFactory        =     $platformRequestFactory;
        $this->_eventDispatcher             =   $eventDispatcher;
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $info            =    new \Convo\Core\Rest\RequestInfo($request);
        $route             =     $info->route('service-test/{serviceId}', true);
        $service_id     =     $route->get('serviceId');
        $user            =    $info->getAuthUser();
        $json            =    $request->getParsedBody();

        $text            =    $json['text'] ?? '';
        $is_init        =    $this->_isInit($json);
        $is_end            =    $json['end'] ?? false;
        $device_id        =    $json['device_id'] ?? false;
        $session_id        =    $json['session_id'] ?? session_id();
        $platform_id    =    $json['platform_id'] ?? self::DEFAULT_PLATFORM_ID;
        $request_id     =   'admin-chat-' . StrUtil::uuidV4();

        if (empty($device_id)) {
            throw new \Convo\Core\Rest\InvalidRequestException('Could not get device_id from request body');
        }

        $this->_logger->info('Performing test request [' . $text . '][' . $device_id . '][' . $platform_id . '] init [' . ($is_init ? 'true' : 'false') . '] end [' . ($is_end ? 'true' : 'false') . ']');

        $text_request   =   new \Convo\Core\Adapters\ConvoChat\DefaultTextCommandRequest($service_id, $device_id, $session_id, $request_id, $text, $is_init, $is_end, self::DEFAULT_PLATFORM_ID, $json);
        $text_response    =    new \Convo\Core\Adapters\ConvoChat\DefaultTextCommandResponse();
        $text_response->setLogger($this->_logger);


        // Enable streaming if requested
        $isStreaming = $request->getHeaderLine('X-Client-Streaming') === 'true';
        $isStreaming = true;
        if ($isStreaming) {
            $this->_logger->debug('Starting streming response');

            $text_response->enableStreaming();

            // Set streaming-specific headers
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            ignore_user_abort(true);

            // Clear any existing buffers
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            ob_start();
        }

        $service        =   $this->_convoServiceFactory->getService($user, $service_id, IPlatformPublisher::MAPPING_TYPE_DEVELOP, $this->_convoServiceParamsFactory);

        if ($platform_id !== self::DEFAULT_PLATFORM_ID) {
            // 		    TODO: load & use service owner account
            // 		    $service_meta     =   $this->_convoServiceDataProvider->getServiceMeta( $user, $service_id);
            // 		    $owner            =   $service_meta['owner'];
            $text_request     =   $this->_platformRequestFactory->toIntentRequest($text_request, $user, $service, $platform_id);
        }


        $exception = [
            "message" => null,
            "stack_trace" => null,
        ];

        try {
            $this->_logger->info('Running service instance [' . $service->getId() . '][' . $text_request . ']');
            $service->run($text_request, $text_response);

            $this->_eventDispatcher->dispatch(
                new ServiceRunRequestEvent(true, $text_request, $text_response, $service, IPlatformPublisher::MAPPING_TYPE_DEVELOP),
                ServiceRunRequestEvent::NAME
            );
            if ($isStreaming) {
                $finalData = $this->_getDebugInfo($service, $text_request, $exception);

                echo "data: " . json_encode(['remaining_response' => $finalData]) . "\n\n";
                // Finish the streaming with [DONE]
                echo "data: [DONE]\n\n";
                ob_flush();
                flush();
                wp_die();
                // return $this->_httpFactory->buildResponse(null);
            }
        } catch (\Throwable $e) {
            $exception["message"] = $e->getMessage();
            $stack = explode('#', $e->getTraceAsString());
            array_shift($stack);
            $exception["stack_trace"] = $stack;

            $this->_eventDispatcher->dispatch(
                new ServiceRunRequestEvent(true, $text_request, $text_response, $service, IPlatformPublisher::MAPPING_TYPE_DEVELOP, $e),
                ServiceRunRequestEvent::NAME
            );
            $this->_logger->error($e);
        }

        $data = $this->_getDebugInfo($service, $text_request, $exception);
        $data = array_merge($data, $text_response->getPlatformResponse());
        //         $exceptionStackTrace = !empty($exception['stack_trace']) ? $exception['stack_trace'] : '';

        return $this->_httpFactory->buildResponse($data);
    }

    private function _getDebugInfo($service, $convoRequest, $exception = null)
    {

        $request_vars = $service->getServiceParams(\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST)->getData();
        $session_vars = $service->getServiceParams(\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_SESSION)->getData();
        $installation_vars = $service->getServiceParams(\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION)->getData();
        $user_vars = $service->getServiceParams(\Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_USER)->getData();

        $child_params = [];

        foreach ($service->getChildren() as $child) {
            try {
                $child_params[] = $this->_getChildData($service, $child);
            } catch (DataItemNotFoundException $e) {
                $this->_logger->info($e->getMessage());
            }
        }

        $data =    [
            'service_state' => $service->getServiceState(),
            'variables' => [
                'service' => [
                    'request' => $request_vars,
                    'session' => $session_vars,
                    'installation' => $installation_vars,
                    'user' => $user_vars
                ],
                'component' => $child_params
            ],
            'exception' => $exception
        ];

        if (is_a($convoRequest, '\Convo\Core\Workflow\IIntentAwareRequest')) {
            $this->_logger->info('Going to extract intent and slot data from intent aware request');

            $data['intent'] = [
                'name' => $convoRequest->getIntentName(),
                'slots' => $convoRequest->getSlotValues()
            ];
        }

        $data = ArrayUtil::arrayFilterRecursive($data, function ($value) {
            return !empty($value);
        });

        return $data;
    }

    private function _isInit($json)
    {
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
        if (!$this->_shouldRender($service, $child)) {
            throw new DataItemNotFoundException('Container component [' . $child->getId() . '] has no params or children. Skipping.');
        }

        $data = [
            'class' => (new \ReflectionClass($child))->getShortName()
        ];

        $params = $service->getAllComponentParams($child);
        if (!empty($params)) {
            $data['params'] = $params;
        }

        if (is_a($child, '\Convo\Core\Workflow\AbstractWorkflowContainerComponent')) {
            /** @var \Convo\Core\Workflow\AbstractWorkflowContainerComponent $child */
            foreach ($child->getChildren() as $childs_child) {
                try {
                    $data['children'][] = $this->_getChildData($service, $childs_child);
                } catch (DataItemNotFoundException $e) {
                    //					$this->_logger->debug( $e->getMessage());
                }
            }
        }

        return $data;
    }

    /**
     * @param \Convo\Core\ConvoServiceInstance $service
     * @param \Convo\Core\Workflow\IBasicServiceComponent $component
     * @return boolean
     */
    private function _shouldRender($service, $component)
    {
        if (!empty($service->getAllComponentParams($component))) {
            return true;
        }

        if (is_a($component, '\Convo\Core\Workflow\AbstractWorkflowContainerComponent')) {
            /** @var \Convo\Core\Workflow\AbstractWorkflowContainerComponent $component */
            $children = $component->getChildren();

            if (!empty($children)) {
                $render = false;

                foreach ($children as $child) {
                    if ($this->_shouldRender($service, $child)) {
                        $render = true;
                        // break;
                    }
                }

                return $render;
            }

            return false;
        }

        return false;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this) . '[]';
    }
}
