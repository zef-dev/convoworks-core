<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IMediaSourceContext;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class MediaInfoElement extends AbstractWorkflowContainerComponent implements IConversationElement
{
    /**
     * @var string
     */
    private $_contextId;
    
    /**
     * @var string
     */
    private $_mediaInfoVar;
    
    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_noResults = array();
    
    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_hasResults = array();
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_contextId		=	$properties['context_id'];
        $this->_mediaInfoVar	=	$properties['media_info_var'] ?? 'media_info';
        
        foreach ( $properties['has_results'] as $element) {
            $this->_hasResults[]        =   $element;
            $this->addChild( $element);
        }
        
        foreach ( $properties['no_results'] as $element) {
            $this->_noResults[]        =   $element;
            $this->addChild( $element);
        }
    }

    public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        $context    =   $this->_getMediaSourceContext();
        $info       =   $context->getMediaInfo();
        $info_var   =   $this->evaluateString( $this->_mediaInfoVar);
        
        $this->_logger->info( 'Injectiong media info ['.$info_var.']['.print_r( $info, true).']'); 
        
        $params     =   $this->getService()->getComponentParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        
        $params->setServiceParam( $info_var, $info);
        
        if ( $context->isEmpty())
        {
            $this->_logger->info( 'Got no results');
            foreach ( $this->_noResults as $element) {
                $element->read( $request, $response);
            }
        }
        else
        {
            $this->_logger->info( 'Got results ['.$context->getCount().']');
            foreach ( $this->_hasResults as $element) {
                $element->read( $request, $response);
            }
        }
    }
    
    /**
     * @return IMediaSourceContext
     */
    private function _getMediaSourceContext()
    {
        $contextId = $this->evaluateString( $this->_contextId);
        return $this->getService()->findContext($contextId)->getComponent();
    }
    
    /**
     * @inheritDoc
     */
    public function evaluateString($string, $context = [])
    {
        $own_params		=	$this->getService()->getAllComponentParams( $this);
        return parent::evaluateString( $string, array_merge( $own_params, $context));
    }
    
    // UTIL
    public function __toString() {
        return parent::__toString().'['.$this->_contextId.']['.$this->_mediaInfoVar.']';
    }
    
}