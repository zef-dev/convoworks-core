<?php declare(strict_types=1);

namespace Convo\Core\Publish;

abstract class AbstractServicePublisher implements \Convo\Core\Publish\IPlatformPublisher
{
	/**
	 * @var \Convo\Core\IServiceDataProvider
	 */
    protected $_convoServiceDataProvider;
	
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;
	
	/**
	 * @var \Convo\Core\IAdminUser
	 */
	protected $_user;
	
	/**
	 * @var string
	 */
	protected $_serviceId;
	
	/**
	 * @var \Convo\Core\Publish\ServiceReleaseManager
	 */
	protected $_serviceReleaseManager;
	
	public function __construct( $logger, \Convo\Core\IAdminUser $user, $serviceId, $serviceDataProvider, $serviceReleaseManager)
	{
		$this->_logger						=	$logger;
		$this->_user						=	$user;
		$this->_serviceId					=	$serviceId;
		$this->_convoServiceDataProvider	= 	$serviceDataProvider;
		$this->_serviceReleaseManager       = 	$serviceReleaseManager;
	}
	
	public function getPropagateInfo() {
	    return IPlatformPublisher::DEFAULT_PROPAGATE_INFO;
	}
	
	public function enable()
	{
	    $this->_checkEnabled();
	    
	    $this->_serviceReleaseManager->initDevelopmentRelease( $this->_user, $this->_serviceId, $this->getPlatformId());
	}
	
	public function propagate()
	{
	    $this->_checkEnabled();
	}

    public function createRelease($platformId, $targetReleaseType, $targetReleaseStage, $alias, $versionId = null)
    {
        throw new \Convo\Core\Util\NotImplementedException('Not yet implemented for ['.$platformId.'] platform.');
    }

    public function createVersionTag($platformId, $versionTagId = null)
    {
        throw new \Convo\Core\Util\NotImplementedException('Not yet implemented for ['.$platformId.'] platform.');
    }

    public function importToDevelop($fromAlias, $toAlias, $versionId = null, $versionTag = null)
    {
        throw new \Convo\Core\Util\NotImplementedException('Not yet implemented.');
    }

    public function importToRelease($targetReleaseType, $targetReleaseStage, $alias, $versionId = null, $nextVersionId = null)
    {
        throw new \Convo\Core\Util\NotImplementedException('Not yet implemented.');
    }

    public function promoteToRelease($targetReleaseType, $targetReleaseStage, $alias, $versionId = null)
    {
        throw new \Convo\Core\Util\NotImplementedException('Not yet implemented.');
    }

	protected function _checkEnabled()
	{
	    $config		=	$this->_convoServiceDataProvider->getServicePlatformConfig( $this->_user, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
		
	    if ( !isset( $config[$this->getPlatformId()])) {
			throw new \Exception( 'Platform ['.$this->getPlatformId().'] is not enabled');
		}
	}
	
	protected function _recordPropagation()
	{
	    $alias = $this->_serviceReleaseManager->getDevelopmentAlias( $this->_user, $this->_serviceId, $this->getPlatformId());
	    $meta = $this->_convoServiceDataProvider->getServiceMeta( $this->_user, $this->_serviceId);
	    $meta['release_mapping'][$this->getPlatformId()][$alias]['time_propagated'] = time();
	    $meta = $this->_convoServiceDataProvider->saveServiceMeta( $this->_user, $this->_serviceId, $meta);
	}
	
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'['.$this->getPlatformId().']['.$this->_serviceId.']';
	}
}
