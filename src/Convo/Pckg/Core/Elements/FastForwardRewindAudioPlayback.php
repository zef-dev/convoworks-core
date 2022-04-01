<?php

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\Factory\InvalidComponentDataException;
use Convo\Core\Workflow\AbstractWorkflowContainerComponent;
use Convo\Core\Workflow\IConversationElement;
use Convo\Core\Workflow\IConvoAudioRequest;
use Convo\Core\Workflow\IConvoAudioResponse;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IMediaSourceContext;

class FastForwardRewindAudioPlayback extends AbstractWorkflowContainerComponent implements IConversationElement
{
    private $_contextId;

    private $_mode;

    private $_rewindFastForwardValue;

    public function __construct( $properties)
    {
        parent::__construct( $properties);
        $this->_contextId              = $properties['context_id'];
        $this->_mode                   = $properties['mode'];
        $this->_rewindFastForwardValue = $properties['rewind_fast_forward_value'];
    }

    /**
     * @param IConvoRequest $request
     * @param IConvoResponse $response
     */
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        /** @var $request IConvoAudioRequest */
        if ( !( $response instanceof IConvoAudioResponse)) {
            $this->_logger->info( 'Not an IConvoAudioResponse. Exiting ...');
            return;
        }

        /** @var $response IConvoAudioResponse */
        $context    =   $this->_getMediaSourceContext();

        try {
            $context->setPlaying();
            $response->playSong($context->current(), $this->_adjustOffset($context, $request));
        } catch (DataItemNotFoundException $e) {
            $this->_logger->notice( $e->getMessage());
        }
    }

    private function _adjustOffset(IMediaSourceContext $context, IConvoAudioRequest $request) {
        $mode = $this->evaluateString($this->_mode);
        $rewindFastForwardValue = $this->evaluateString($this->_rewindFastForwardValue);

        switch ($request->getPlatformId()) {
            case AmazonCommandRequest::PLATFORM_ID:
                $playerOffset = $request->getPlatformData()['context']['AudioPlayer']['offsetInMilliseconds'] ?? 0;
                $this->_logger->info('Got offset from ['.$request->getPlatformId().'] device audio player state ['.$playerOffset.']');
                break;
            default:
                $playerOffset = 0;
                $this->_logger->info('Could not get offset from ['.$request->getPlatformId().'] device audio player state ['.$playerOffset.']');
                break;
        }

        $this->_logger->info('Got current player offset ['.$playerOffset.']'.' of song ['.$context->current().'] in ['.$mode.'] mode.');

        $result = 0;
        switch ($mode) {
            case 'rewind':
                $result = $playerOffset - intval($rewindFastForwardValue) * 1000;
                $result = max($result, 0);
                $this->_logger->info('Rewinding current song to ['.$result.']');
                $context->setOffset($result);
                break;
            case 'fast_forward':
                $result = $playerOffset + intval($rewindFastForwardValue) * 1000;
                $this->_logger->info('Forwarding current song to ['.$result.']');
                $context->setOffset($result);
                break;
            default:
                throw new InvalidComponentDataException('Unsupported mode ['.$mode.']');
        }

        $this->_logger->info('Got current song offset ['.$context->getOffset().']');

        return $result;
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
