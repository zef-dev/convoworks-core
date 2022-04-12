<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Params\IServiceParamsScope;

class ElementRandomizer extends ElementCollection implements \Convo\Core\Workflow\IConversationElement
{
    const RANDOM_MODE_WILD  =   'wild';
    const RANDOM_MODE_SMART =   'smart';

    private $_mode;

	private $_loop;

	private $_isRepeat;

	private $_scopeType;
	
	public function __construct($properties)
	{
	    parent::__construct($properties);

        $this->_mode = $properties['mode'] ?? self::RANDOM_MODE_WILD;

		$this->_loop = $properties['loop'] ?? false;

		$this->_isRepeat = $properties['is_repeat'] ?? false;

		$this->_scopeType = $properties['scope_type'] ?? IServiceParamsScope::SCOPE_TYPE_INSTALLATION;
	}
	
	public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
	{
		$service      =   $this->getService();
		$scope_type   =   $this->evaluateString( $this->_scopeType);
		$mode         =   $this->evaluateString( $this->_mode);
		
		$loop         =   $this->evaluateString( $this->_loop);
		$isRepeat     =   $this->evaluateString( $this->_isRepeat);

		$params       =   $service->getComponentParams( $scope_type, $this);
		$elements     =   $this->getElements();
		
		
	    if ( $mode === self::RANDOM_MODE_SMART) 
	    {
	    	$status = $params->getServiceParam( '__status');

	    	if ( empty( $status)) {
	    	    $this->_logger->info( 'First time comming. Generating new indexes');
	    	    $status   =   $this->_getDefaultStatus( $elements);
	    	}
	    	
	    	if ( empty( $status['indexes']) && $loop) {
	    	    $this->_logger->info( 'All done and loop is on. Generating indexes again.');
	    	    $status   =   $this->_getDefaultStatus( $elements);
	    	} else if ( empty( $status['indexes'])) {
	    	    $this->_logger->info( 'All done and loop is off. Existing ...');
	    	    return ;
	    	}
	    	
	    	if ( $isRepeat) {
	    	    $current_index    =   $status['indexes'][0];
	    	} else {
	    	    $current_index    =   array_shift( $status['indexes']);
	    	}
	    	
	    	$params->setServiceParam( '__status', $status);
	    	
	    	$random_element   =   $elements[$current_index];
        } 
        else 
        {
	        $random_idx     =  rand( 0, count( $elements) - 1);
	        $this->_logger->info( 'Getting element ['.$random_idx.']');
	        $random_element =  $elements[$random_idx];
        }
        
        $random_element->read( $request, $response);
	}
	
	private function _getDefaultStatus( $elements) {
	    $status['indexes'] = array_keys( $elements);
	    
	    shuffle( $status['indexes']);
	    
	    return $status;
	}

	// UTIL
	public function __toString()
	{
		return parent::__toString().'[]';
	}
}