<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Publish\IPlatformPublisher;

class PackageProviderFactory
{
    const SOURCE_TYPE_TEMPLATES = 'templates';
    const SOURCE_TYPE_INTENTS = 'intents';
    const SOURCE_TYPE_ENTITIES = 'entities';
    const SOURCE_TYPE_FUNCTIONS = 'functions';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_serviceDataProvider;

    /**
     * @var IPackageDescriptor[]
     */
    private $_descriptors = [];

    public function __construct($logger, $serviceDataProvider)
    {
        $this->_logger = $logger;
        $this->_serviceDataProvider = $serviceDataProvider;
    }

    public function registerPackage(IPackageDescriptor $descriptor)
    {
        $this->_descriptors[$descriptor->getNamespace()] = $descriptor;

        $this->_logger->debug('Registered package ['.$descriptor->getNamespace().']. Currently registered ['.count($this->_descriptors).'] packages.');
    }

    /**
     * @param \Convo\Core\IAdminUser $user
     * @param $serviceId
     * @return PackageProvider
     * @throws \Convo\Core\DataItemNotFoundException
     * @throws \Convo\Core\Rest\NotAuthorizedException
     */
    public function getProviderByServiceId(\Convo\Core\IAdminUser $user, $serviceId)
    {
        $service = $this->_serviceDataProvider->getServiceData($user, $serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);

        $available = [];

        $this->_logger->debug('Service has packages ['.print_r($service['packages'], true).']');

        foreach ($service['packages'] as $package)
        {
             $available[] = $this->getProviderByNamespace( $package);
        }

        return new PackageProvider($this->_logger, $available);
    }

    public function getProviderFromPackageIds($ids)
    {
        $available = [];

        foreach ($ids as $package)
        {
            $available[] = $this->getProviderByNamespace($package);
        }

        return new PackageProvider($this->_logger, $available);
    }

    /**
     * @param string $namespace
     * @throws DataItemNotFoundException
     * @return \Convo\Core\Factory\IPackageDefinition
     */
    public function getProviderByNamespace($namespace)
    {
        if ( isset( $this->_descriptors[$namespace])) {
            return $this->_descriptors[$namespace]->getPackageInstance();
        }

        throw new DataItemNotFoundException('No such package with namespace ['.$namespace.']');
    }

    public function getAvailablePackages()
    {
        $meta = [];

        foreach ($this->_descriptors as $descriptor)
        {
            $meta[] = $descriptor->getPackageMeta();
        }

        return $meta;
    }

    /**
     * @param $type
     * @return \Convo\Core\Factory\IPackageDefinition[]
     * @throws \ReflectionException
     */
    public function getSourcesFor($type)
    {
        $providers = [];

        foreach ($this->_descriptors as $descriptor)
        {
            if (in_array($type, $descriptor->getPackageMeta()['source_for'])) {
                $providers[] = $descriptor->getPackageInstance();
            }
        }

        return $providers;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
