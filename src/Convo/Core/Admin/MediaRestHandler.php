<?php declare(strict_types=1);

namespace Convo\Core\Admin;

use Convo\Core\Rest\RequestInfo;
use Convo\Core\Util\SimpleFileResource;

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
    private $_mediaService;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

    /**
     * @var \Convo\Core\IAdminUser
     */
    private $_user;

    public function __construct($logger, $httpFactory, $mediaService, $serviceDataProvider)
    {
        $this->_logger = $logger;

        $this->_httpFactory = $httpFactory;
        $this->_mediaService = $mediaService;
        $this->_convoServiceDataProvider = $serviceDataProvider;
    }

    public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $info = new RequestInfo($request);

        $this->_user = $info->getAuthUser();

        if ($info->post() && $route = $info->route('media/{serviceId}')) {
            return $this->_handleMediaPathServiceIdPost($request, $route->get('serviceId'));
        }

        if ($info->get() && $route = $info->route('media/{serviceId}/{mediaItemId}/download')) {
            return $this->_handleMediaPathServiceIdPathMediaItemIdPathDownloadGet(
                $request,
                $route->get('serviceId'),
                $route->get('mediaItemId')
            );
        }

        throw new \Convo\Core\Rest\NotFoundException('Could not map info ['.$info.']');
    }

    private function _handleMediaPathServiceIdPost(\Psr\Http\Message\ServerRequestInterface $request, $serviceId)
    {
        /** @var \Psr\Http\Message\UploadedFileInterface[] $files */
        $files = $request->getUploadedFiles();

        foreach ($files as $filename => $image) {
            $this->_logger->debug("Handling file [$filename]");

            $file = new SimpleFileResource(
                $image->getClientFilename(),
                $image->getClientMediaType(),
                $image->getStream()->__toString()
            );
    
            $mediaItemId = $this->_mediaService->saveMediaItem($serviceId, $file);

            return $this->_httpFactory->buildResponse(['mediaItemId' => $mediaItemId]);
        }
    }

    private function _handleMediaPathServiceIdPathMediaItemIdPathDownloadGet(\Psr\Http\Message\ServerRequestInterface $request, $serviceId, $mediaItemId)
    {
        $meta = $this->_mediaService->getMediaInfo($serviceId, $mediaItemId);

        $image = $this->_mediaService->getMediaItem($serviceId, $mediaItemId);

        return $this->_httpFactory->buildResponse($image->getContent(), 200, [
            'Content-Type' => $meta['mime_type'],
            'Content-Length' => $meta['size']
        ]);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'[]';
    }
}