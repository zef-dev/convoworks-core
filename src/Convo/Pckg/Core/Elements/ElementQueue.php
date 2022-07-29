<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;

class ElementQueue extends AbstractElementQueue
{
    private $_scopeType;

    /**
     * @var IConversationElement[]
     */
    private $_done;

    private $_shouldReset;

    private $_wraparound;

    public function __construct($properties)
    {
        parent::__construct($properties);

        $this->_scopeType = $properties['scope_type'];

        $this->_shouldReset = $properties['should_reset'];

        $this->_wraparound = $properties['wraparound'] ?? false;

        $this->_done = $properties['done'] ?: [];

        foreach ($this->_done as $done) {
            $this->addChild($done);
            $done->setParent($this);
        }
    }
    
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        $elements       =   $this->getElements();
        $should_reset   =   $this->evaluateString( $this->_shouldReset);
        
        if ( $should_reset) {
            $this->_logger->info( 'Resetting elements queue');
            $this->_reset();
        }
        
        foreach ( $elements as $elem) {
            if ( $this->_registerElement( $elem)) {
                $elem->read( $request, $response);
                return;
            }
        }

        $wraparound = $this->evaluateString($this->_wraparound);
        if ($wraparound) {
            $this->_logger->info('All elements read with wrap-around evaluated to true. Resetting available pool of elements.');
            $this->_reset();
            $this->read($request, $response);
            return;
        }

        $this->_logger->info('Going to read Done flow');
        
        foreach ( $this->_done as $done) {
            $done->read( $request, $response);
        }
    }
    
    /**
     * @param IConversationElement $element
     * @return boolean
     */
    private function _registerElement( $element) {
        $params         =   $this->getService()->getComponentParams( $this->evaluateString( $this->_scopeType), $this);
        $used           =   $params->getServiceParam( 'used');
        if ( !$used) {
            $used   =   [];
        }
        
        if ( in_array( $element->getId(), $used)) {
            return false;
        }
        
        $used[] = $element->getId();
        $params->setServiceParam( 'used', $used);
        return true;
    }
    
    private function _reset() {
        $params         =   $this->getService()->getComponentParams( $this->evaluateString( $this->_scopeType), $this);
        $params->setServiceParam( 'used', []);
    }
}
