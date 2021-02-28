<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\AbstractWorkflowComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IMediaSourceContext;
use Convo\Core\Workflow\IConvoAudioResponse;

class StartAudioPlayback extends AbstractWorkflowComponent implements IConversationElement
{

    
    private $_contextId;
    private $_rewind;
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        $this->_contextId   =   $properties['context_id'];
        $this->_rewind      =   $properties['rewind'] ?? null;
    }

    public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        if ( !( $response instanceof IConvoAudioResponse)) {
            $this->_logger->info( 'Not an IConvoAudioResponse. Exiting ...');
            return ;
        }
        
        /** @var $response IConvoAudioResponse */
        $context    =   $this->_getMediaSourceContext();
        
        if ( $this->_rewind && $this->evaluateString( $this->_rewind)) {
            $this->_logger->info( 'Rewinding media source ...');
            $context->rewind();
        }
        
        $response->playSong( $context->current());
    }
    
    /**
     * @return IMediaSourceContext
     */
    private function _getMediaSourceContext()
    {
        $contextId = $this->evaluateString( $this->_contextId);
        return $this->getService()->findContext($contextId)->getComponent();
    }
}