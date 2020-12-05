<?php declare(strict_types=1);

namespace Convo\Core\Media;

use Convo\Core\Util\IFileResource;

interface IServiceMediaManager
{
    /**
     * Saves a media file to disk
     *
     * @param string $serviceId
     * @param \Convo\Core\Util\IFileResource $file
     * @return string
     */
    public function saveMediaItem($serviceId, $file);

	/**
	 * @param $serviceId
	 * @param $mediaItemId
	 * @return IFileResource
	 */
    public function getMediaItem($serviceId, $mediaItemId);

	/**
	 * @param $serviceId
	 * @param $mediaItemId
	 * @return array
	 */
    public function getMediaInfo($serviceId, $mediaItemId);

	/**
	 * @param $serviceId
	 * @param $mediaItemId
	 * @return string
	 */
    public function getMediaUrl($serviceId, $mediaItemId);
}
