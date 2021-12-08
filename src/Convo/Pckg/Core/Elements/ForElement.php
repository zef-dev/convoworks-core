<?php declare(strict_types=1);
namespace Convo\Pckg\Core\Elements;

class ForElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{
    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_elements = array();
    
    /** @var array */
    private $_count;
	private $_loop_until;
    private $_status_var;
    
    
    public function __construct($properties)
    {
        parent::__construct($properties);
        
        $this->_count = $properties['count'];
        $this->_loop_until = $properties['loop_until'] ?? false;
        $this->_status_var = $properties['status_var'];
        
        
        if ( isset($properties['elements'])) {
            foreach ( $properties['elements'] as $element) {
                $this->addElement( $element);
            }
        }
    }
    
    public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        $count = $this->evaluateString($this->_count);
        $status_var = $this->evaluateString($this->_status_var);
        
        $scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
        $params = $this->getService()->getComponentParams( $scope_type, $this);

        $start = 0;
        $end = intval($count);
        
        
        for ( $i = $start; $i < $end; $i++) {
            
            $params->setServiceParam($status_var, [
                'index' => $i,
                'natural' => $i + 1,
                'first' => $i === $start,
                'last' => $i === $end - 1
            ]);

			$loop_until = $this->evaluateString($this->_loop_until);
			if (is_bool($loop_until) && $loop_until) {
				$this->_logger->info('Exiting loop.');
				break;
			}
            foreach ($this->_elements as $element) {
                $element->read($request, $response);
            }
        }
    }
    
    public function addElement( \Convo\Core\Workflow\IConversationElement $element)
    {
        $this->_elements[] = $element;
        $this->addChild($element);
    }
    
    /**
     * @return \Convo\Core\Workflow\IConversationElement[]
     */
    public function getElements() {
        return $this->_elements;
    }
    
    public function evaluateString( $string, $context=[]) {
        $own_params	= $this->getService()->getAllComponentParams( $this);
        return parent::evaluateString( $string, array_merge( $own_params, $context));
    }
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.count( $this->_elements).']';
    }
}