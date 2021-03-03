<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IMediaSourceContext;
use Convo\Core\Workflow\IConvoAudioResponse;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\DataItemNotFoundException;

class StartAudioPlayback extends AbstractWorkflowContainerComponent implements IConversationElement
{

    
    private $_contextId;
    
    private $_playIndex;
    
    /**
     * @var string
     */
    private $_mediaInfoVar;
    
    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_failback = array();
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        $this->_contextId       =   $properties['context_id'];
        $this->_playIndex	    =	$properties['play_index'] ?? '';
        $this->_mediaInfoVar    =	$properties['media_info_var'] ?? 'media_info';
        
        foreach ( $properties['failback'] as $element) {
            $this->_failback[]        =   $element;
            $this->addChild( $element);
        }
    }

    public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        if ( !( $response instanceof IConvoAudioResponse)) {
            $this->_logger->info( 'Not an IConvoAudioResponse. Exiting ...');
            return ;
        }
        
        /** @var $response IConvoAudioResponse */
        $context    =   $this->_getMediaSourceContext();
        $params     =   $this->getService()->getComponentParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( $this->evaluateString( $this->_mediaInfoVar), $context->getMediaInfo());
        
        $index      =   $this->evaluateString( $this->_playIndex);
        
        if ( !is_numeric( $index)) {
            $this->_logger->info( 'Playing current song ...');
            $response->playSong( $context->current());
            $context->setPlaying();
            return ;
        }
        
        $index  =   intval( $index);
        $this->_logger->info( 'Playing song ['.$index.'] ...');
        
        try 
        {
            $context->seek( $index);
            $response->playSong( $context->current());
            $context->setPlaying();
        } 
        catch ( DataItemNotFoundException $e) 
        {
            $this->_logger->notice( $e->getMessage());
            
            if ( !empty( $this->_failback))
            {
                foreach ( $this->_failback as $element) {
                    $element->read( $request, $response);
                }
            }
        }
    }
    
    /**
     * @return IMediaSourceContext
     */
    private function _getMediaSourceContext()
    {
        return $this->getService()->findContext( 
            $this->evaluateString( $this->_contextId), 
            IMediaSourceContext::class);
    }
}