<?php declare(strict_types=1);

namespace Convo\Core\Factory;

class FunctionPackageDescriptor extends AbstractPackageDescriptor
{
    /**
     * @var callable
     */
    private $_instantiationFunction;

    
    public function __construct($packageClass, callable $instantiationFunction)
    {
        parent::__construct($packageClass);

        $this->_instantiationFunction = $instantiationFunction;
    }

    protected function _createPackageInstance()
    {
        $this->_logger->info( 'info package ['.$this->_packageClass.'] with function ...');
        
        $result = call_user_func($this->_instantiationFunction);

        if (!is_a($result, '\Convo\Core\Factory\IPackageDefinition')) {
            throw new \Exception('Expected result of callback to implement [\Convo\Core\Factory\IPackageDefinition]');
        }

        /** @var IPackageDefinition $result */
        return $result;
    }
}
