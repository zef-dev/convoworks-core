<?php declare(strict_types=1);

namespace Convo\Core\Factory;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Tole
 * Implements reading package meta from filesystem while leaving out the package creation itself.
 */
abstract class AbstractPackageDescriptor implements IPackageDescriptor, LoggerAwareInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var string
     */
    protected $_packageClass;
    
    /**
     * @var array
     */
    private $_meta;
    
    /**
     * @var IPackageDefinition
     */
    private $_package;
    
    public function __construct($packageClass)
    {
        $this->_logger = new NullLogger();
        $this->_packageClass = $packageClass;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
    }

    function getPackageInstance() {
        if ( !isset( $this->_package)) {
            $this->_package =   $this->_createPackageInstance();
        }
        return $this->_package;
    }
    
    /**
     * Creates package instance.
     * @return IPackageDefinition
     */
    protected abstract function _createPackageInstance();

    public function getPackageMeta()
    {
        if ( !isset( $this->_meta)) {
            $this->_meta    =   $this->_getPackageMeta();
        }
        return $this->_meta;
    }

    protected function _getPackageMeta()
    {
        $reflection = new \ReflectionClass($this->_packageClass);
        $expected_filename = str_replace('.php', '.json', $reflection->getFileName());

        if (!file_exists($expected_filename)) {
            throw new \Exception('No meta file could be located, expected file ['.$expected_filename.'] to exist.');
        }

        if (($meta = file_get_contents($expected_filename)) === false) {
            throw new \Exception('Could not open ['.$expected_filename.'] for reading.');
        }

        return json_decode($meta, true);
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.$this->_packageClass.']';
    }
}
