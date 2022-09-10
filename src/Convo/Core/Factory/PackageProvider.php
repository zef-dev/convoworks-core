<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Convo\Core\Rest\NotFoundException;
use Convo\Core\Expression\ExpressionFunctionProviderInterface;

class PackageProvider implements
    \Convo\Core\Intent\ISystemIntentRepository,
    \Convo\Core\Intent\ISystemEntityRepository,
    \Convo\Core\Factory\ITemplateSource,
    ExpressionFunctionProviderInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Core\Factory\IPackageDefinition[]
     */
    private $_packages	            =	[];

    /**
     * @var \Convo\Core\Intent\ISystemIntentRepository[]
     */
    private $_intentRepositories	=	[];

    /**
     * @var \Convo\Core\Intent\ISystemEntityRepository[]
     */
    private $_entityRepositories	=	[];

    /**
     * @var \Convo\Core\Factory\ITemplateSource[]
     */
    private $_templateSources	=	[];

    /**
     * @var ExpressionFunctionProviderInterface[]
     */
    private $_functionProviders	=	[];

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(\Psr\Log\LoggerInterface $logger, $packages)
    {
        $this->_logger = $logger;
//         $this->_packages = $packages;

        foreach ( $packages as $package)
        {
            /* @var \Convo\Core\Factory\IPackageDefinition $package */
            
            $this->_packages[$package->getNamespace()] = $package;
            
            if (is_a($package, '\Convo\Core\Intent\ISystemIntentRepository')) {
                /* @var \Convo\Core\Intent\ISystemIntentRepository $package */
                $this->_intentRepositories[$package->getNamespace()] = $package;
            }

            if (is_a($package, '\Convo\Core\Intent\ISystemEntityRepository')) {
                /* @var \Convo\Core\Intent\ISystemEntityRepository $package */
                $this->_entityRepositories[$package->getNamespace()] = $package;
            }

            if (is_a($package, '\Convo\Core\Factory\ITemplateSource')) {
                /* @var \Convo\Core\Factory\ITemplateSource $package */
                $this->_templateSources[$package->getNamespace()] = $package;
            }

            if (is_a($package, '\Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface')) {
                /* @var ExpressionFunctionProviderInterface */
                $this->_functionProviders[$package->getNamespace()] = $package;
            }
        }
    }

    /**
     * @return \Convo\Core\Factory\IPackageDefinition[]
     */
    public function getPackages()
    {
        return $this->_packages;
    }

    /**
     * @param $packageId
     * @return \Convo\Core\Factory\IPackageDefinition
     * @throws NotFoundException
     */
    public function findPackageById($packageId)
    {
        if ( isset( $this->_packages[$packageId])) {
            return $this->_packages[$packageId];
        }
        throw new NotFoundException('Package ['.$packageId.'] not found.');
    }

    // TEMPLATES

    /**
     * @param string $templateId
     * @return array
     */
    public function getTemplate( $templateId) {
        foreach ( $this->_templateSources as $package) {
            try {
                return $package->getTemplate( $templateId);
            } catch ( \Convo\Core\ComponentNotFoundException $e) {
            }
        }
        throw new \Convo\Core\ComponentNotFoundException( 'Template ['.$templateId.'] not found');
    }

    // INTENTS
    /**
     * {@inheritDoc}
     * @see \Convo\Core\Intent\ISystemIntentRepository::getIntent()
     */
    public function getIntent( $name)
    {
        $this->_logger->debug( 'Searching for system intent ['.$name.']');

        $parts = explode('.', $name);
        $prefix = $parts[0];
        $name = $parts[1];

        foreach ( $this->_intentRepositories as $repo) {
            if ($repo->accepts($prefix)) {
                return $repo->getIntent($name);
            }
        }
        throw new \Convo\Core\ComponentNotFoundException( 'System intent ['.$name.'] not found');
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Intent\ISystemIntentRepository::findPlatformIntent()
     */
    public function findPlatformIntent( $name, $platformId)
    {
        foreach ( $this->_intentRepositories as $repo) {
            try {
                return $repo->findPlatformIntent( $name, $platformId);
            } catch (\Convo\Core\ComponentNotFoundException $e) {
            }
        }
        throw new \Convo\Core\ComponentNotFoundException( 'Platform ['.$platformId.'] intent ['.$name.'] not found');
    }

    // ENTITIES
    /**
     * {@inheritDoc}
     * @see \Convo\Core\Intent\ISystemEntityRepository::findPlatformEntity()
     */
    public function findPlatformEntity( $name, $platformId)
    {
        foreach ( $this->_entityRepositories as $repo) {
            try {
                return $repo->findPlatformEntity( $name, $platformId);
            } catch (\Convo\Core\ComponentNotFoundException $e) {
            }
        }
        throw new \Convo\Core\ComponentNotFoundException( 'Platform ['.$platformId.'] entity ['.$name.'] not found');
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Intent\ISystemEntityRepository::getEntity()
     */
    public function getEntity( $name)
    {
        if ( strpos( $name, '@') === 0) {
            $name  =   substr( $name, 1);
        }
        // todo Could we combine these two ifs into an if else? Only system entities should have an @ in their name.
        // do we even need @ in system entities?
        if (strpos($name, '.') === false) {
            // not a system entity.
            throw new \Convo\Core\ComponentNotFoundException( 'Entity ['.$name.'] has no prefix -- not system');
        }

        $parts = explode('.', $name);
        $prefix = $parts[0];
        $name = $parts[1];

        $this->_logger->debug("Looking for entity [$prefix][$name]");

        foreach ( $this->_entityRepositories as $repo) {
            if ($repo->accepts($prefix)) {
                return $repo->getEntity($name);
            }
        }
        throw new \Convo\Core\ComponentNotFoundException( 'System entity ['.$name.'] not found');
    }

    // EXPRESSION FUNCTIONS

    public function getFunctions()
    {
        $functions = [];

        foreach ($this->_functionProviders as $functionProvider)
        {
            $functions = array_merge($functions, $functionProvider->getFunctions());
        }

        return $functions;
    }

    // COMPONENTS

    /**
     * @param \Convo\Core\ConvoServiceInstance $service
     * @param array $componentData
     * @throws \Convo\Core\ComponentNotFoundException
     * @throws \Convo\Core\Factory\InvalidComponentDataException
     * @return \Convo\Core\Workflow\IBasicServiceComponent
     */
    public function createComponent( \Convo\Core\ConvoServiceInstance $service, $componentData)
    {
// 		$this->_logger->debug( '-----');
// 		$this->_logger->debug( 'Creating component ['.$componentData['class'].']['.json_encode( $componentData).']');

        if ( !is_array( $componentData)) {
            throw new \Exception( 'Expected to have array here. Got ['.$componentData.']');
        }

        // TMP FIX
        if ( !isset( $componentData['namespace']) && strpos( $componentData['class'], 'Convo\\Pckg\\Core') !== false) {
            $componentData['namespace']	=	'convo-core';
            $this->_logger->warning( 'Fixed empty namespace to ['.$componentData['namespace'].']');
        }

        $this->_checkComponent( $componentData);

        foreach ($this->getPackages() as $package)
        {
            if ($package->getNamespace() === $componentData['namespace'])
            {
                if (!is_a($package, '\Convo\Core\Factory\IComponentProvider')) {
                    throw new \Exception('Package ['.$package->getNamespace().'] is not [\Convo\Core\Factory\IComponentProvider]');
                }

                /** @var \Convo\Core\Factory\IComponentProvider $package */
                $component = $package->createPackageComponent( $service, $this, $componentData);
                $this->_logger->debug( 'Created component ['.get_class( $component).']');
// 				$this->_logger->debug( '-----');

                if ( is_a( $component, '\Psr\Log\LoggerAwareInterface')) {
                    /** @var \Psr\Log\LoggerAwareInterface $component */
                    $component->setLogger( $this->_logger);
                }

                if ( is_a( $component, '\Convo\Core\Workflow\IBasicServiceComponent')) {
                    /** @var \Convo\Core\Workflow\IBasicServiceComponent $component */
                    $component->setService( $service);
                }

                return $component;
            }
        }
        throw new \Convo\Core\ComponentNotFoundException( 'Service ['.$service.'] component ['.$componentData['class'].'] not found');
    }

    /**
     * @param array $componentData
     * @throws \Convo\Core\Factory\InvalidComponentDataException
     */
    private function _checkComponent( $componentData)
    {
        if ( !isset( $componentData['namespace']) || empty( $componentData['namespace'])) {
            throw new \Convo\Core\Factory\InvalidComponentDataException( 'Missing namespace in component data');
        }
        if ( !isset( $componentData['class']) || empty( $componentData['class'])) {
            throw new \Convo\Core\Factory\InvalidComponentDataException( 'Missing class in component data');
        }
        if ( !isset( $componentData['properties'])) {
            throw new \Convo\Core\Factory\InvalidComponentDataException( 'Missing properties in component data');
        }
    }

    public function getRow()
    {
        $data = [];

        foreach ($this->getPackages() as $package)
        {
            $data[] = $package->getRow();
        }

        return $data;
    }

    // UTIL
    public function __toString()
    {
        return get_class( $this).'['.count( $this->_packages).']';
    }

    public function accepts($prefix)
    {
        return true;
    }
}
