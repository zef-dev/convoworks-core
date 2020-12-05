<?php declare(strict_types=1);

namespace Convo\Core\Params;

abstract class AbstractServiceParams implements \Convo\Core\Params\IServiceParams
{
	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
    protected $_logger;

    /**
     * Scope to define parms namespace (scope)
     *
     * @var \Convo\Core\Params\IServiceParamsScope
     */
	protected $_scope;


    public function __construct( \Psr\Log\LoggerInterface $logger, \Convo\Core\Params\IServiceParamsScope $scope)
    {
    	$this->_logger		=	$logger;
        $this->_scope 		=   $scope;
    }

    public function setServiceParam( $name, $value)
    {
        $data			=	$this->getData();
        $data[$name]	=	$value;
        $this->_storeData( $data);
    }
    
    public function getServiceParam( $name)
    {
        $data		=	$this->getData();
        
        if ( isset( $data[$name])) {
            return $data[$name];
        }
        
        return null;
    }

    protected abstract function _storeData( $data);

    // UTIL
    public function __toString()
    {
    	return get_class( $this).'['.$this->_scope.']';
    }
}
