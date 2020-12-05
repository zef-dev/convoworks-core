<?php declare(strict_types=1);

namespace Convo\Core;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Rest\RestSystemUser;

/**
 * @author Tole
 * Base class you can use when implementing own service data layer.
 * It has few utility methods you might want to use.
 */
abstract class AbstractServiceDataProvider implements IServiceDataProvider
{
	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;

	public function __construct( \Psr\Log\LoggerInterface $logger)
	{
		$this->_logger		=	$logger;
	}


	/**
	 * Returns true if the user has access to the service.
	 * @param $user IAdminUser
	 * @param $serviceMeta array
	 * @return boolean
	 */
	protected function _checkServiceOwner( IAdminUser $user, $serviceMeta) {
	    $checkedOwner = false;
	    if (!$user->isSystem()) {
	        if ($user->getEmail() === $serviceMeta["owner"] || $user->getUsername() === $serviceMeta['owner'] || empty($serviceMeta["owner"])) {
	            $checkedOwner = true;
	        }

	        if (in_array($user->getEmail(), $serviceMeta["admins"])) {
	            $checkedOwner = true;
	        }

	        if (!$serviceMeta["is_private"] && !empty($user->getId()) ) {
                $checkedOwner = true;
            }
	    } else if ($user->isSystem()) {
	        $checkedOwner = true;
	    }

	    return $checkedOwner;
	}

	/**
	 * Generates unique id for passed service name
	 * @param string $serviceName
	 * @throws \Exception
	 * @return string
	 */
	protected function _generateIdFromName( $serviceName)
	{
	    $service_id   =   \Convo\Core\Util\StrUtil::slugify( $serviceName);

	    try {
	        $this->getServiceData( new RestSystemUser(), $service_id, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	    } catch ( \Convo\Core\DataItemNotFoundException $e) {
	        return $service_id;
	    }

	    $service_id   =   $service_id.'-'.sprintf( '%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

	    try {
	        $this->getServiceData( new RestSystemUser(), $service_id, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
	    } catch ( \Convo\Core\DataItemNotFoundException $e) {
	        return $service_id;
	    }

	    throw new \Exception( 'Failed to create unique service id for ['.$serviceName.']');
	}

	/**
	 * Retunrs default metadata for service
	 * @param IAdminUser $user
	 * @param string $serviceId
	 * @param string $serviceName
	 * @return array
	 */
	protected function _getDefaultMeta( IAdminUser $user, $serviceId, $serviceName)
	{
	    return array_merge( IServiceDataProvider::DEFAULT_META,
	        [ 'owner' => $user->getEmail(), 'service_id' => $serviceId, 'name' => $serviceName,
	            'time_updated' => time(), 'time_created' => time()]);
	}

	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}
}
