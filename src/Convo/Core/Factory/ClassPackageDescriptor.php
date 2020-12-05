<?php declare(strict_types=1);

namespace Convo\Core\Factory;

/**
 * @author Tole
 */
class ClassPackageDescriptor extends AbstractPackageDescriptor
{
    /**
     * @var \Psr\Container\ContainerInterface
     */
    private $_container;

    public function __construct($packageClass, \Psr\Container\ContainerInterface $container)
    {
        parent::__construct($packageClass);

        $this->_container = $container;
    }

    protected function _createPackageInstance()
    {
        if ( $this->_container->has( $this->_packageClass)) {
            $this->_logger->debug( 'Creating package ['.$this->_packageClass.'] from container ...');
            return $this->_container->get( $this->_packageClass);
        }
        
        $this->_logger->debug( 'Creating package ['.$this->_packageClass.'] with autowire ...');
        
        $base_package_class = new \ReflectionClass($this->_packageClass);

        $reflection_params = $base_package_class->getConstructor()->getParameters();
        $deps = [];

        foreach ($reflection_params as $param)
        {
            try {
                $this->_logger->debug('Trying to fetch parameter ['.$param->getName().']');
                $deps[] = $this->_container->get($param->getName());
                continue;
            } catch (\Psr\Container\NotFoundExceptionInterface $e) {
                $this->_logger->warning('Could not find dependency ['.$param->getName().'] by short name.');
            }

            try {
                $deps[] = $this->_container->get($param->getClass()->getName());
            } catch (\Psr\Container\NotFoundExceptionInterface $e) {
                throw new \Exception('Could not find dependency ['.$param->getName().']['.$param->getClass()->getName().'] for ['.$this->_packageClass.']');
            }
        }

        /** @var IPackageDefinition $package */
        $package = $base_package_class->newInstanceArgs($deps);
        return $package;
    }
}
