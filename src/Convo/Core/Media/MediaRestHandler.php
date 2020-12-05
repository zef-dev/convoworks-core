<?php declare(strict_types=1);

namespace Convo\Core\Media;

use Convo\Core\Rest\RequestInfo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MediaRestHandler implements \Psr\Http\Server\RequestHandlerInterface
{
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;

	/**
	 * @var \Convo\Core\Media\IServiceMediaManager
	 */
	private $_serviceMediaManager;

	public function __construct($logger, $httpFactory, $serviceMediaManager)
	{
		$this->_logger = $logger;
		$this->_httpFactory = $httpFactory;
		$this->_serviceMediaManager = $serviceMediaManager;
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$info = new RequestInfo($request);

		if ($info->get() && $route = $info->route('service-media/{serviceId}/{mediaItemId}'))
		{
			return $this->_handleServiceMediaPathServiceIdPathMediaItemIdGet(
				$request, $route->get('serviceId'), $route->get('mediaItemId')
			);
		}

		throw new \Convo\Core\Rest\NotFoundException('Could not map info ['.$info.']');
	}

	private function _handleServiceMediaPathServiceIdPathMediaItemIdGet(\Psr\Http\Message\ServerRequestInterface $request, $serviceId, $mediaItemId)
	{
		$image = $this->_serviceMediaManager->getMediaItem($serviceId, $mediaItemId);

		$this->_logger->debug('Got media item ['.$image.']');

		return $this->_httpFactory->buildResponse($image->getContent(), 200, [
			'Content-Type' => $image->getContentType(),
			'Content-Length' => $image->getSize()
		]);
	}

	public function __toString()
	{
		return get_class($this).'[]';
	}
}
