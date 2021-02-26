<?php

namespace Convo\Pckg\Core\Elements;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Preview\PreviewUtterance;
use Convo\Core\Workflow\IConvoAudioRequest;
use Convo\Core\Workflow\IConvoAudioResponse;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IMediaSourceContext;
use Convo\Core\Workflow\IRequestFilter;
use Convo\Core\Workflow\IRequestFilterResult;
use Convo\Core\Workflow\IRunnableBlock;

class MediaBlock extends \Convo\Pckg\Core\Elements\ElementCollection implements \Convo\Core\Workflow\IRunnableBlock
{

    const COMMAND_CONTINUE_PLAYBACK = 'continue_playback';
    const COMMAND_START_PLAYBACK = 'start_playback';
    const COMMAND_PLAYBACK_STARTED = 'playback_started';
    const COMMAND_PLAYBACK_NEARLY_FINISHED = 'playback_nearly_finished';
    const COMMAND_PLAYBACK_FINISHED = 'playback_finished';
    const COMMAND_PLAYBACK_STOPPED = 'playback_stopped';
    const COMMAND_PLAYBACK_FAILED = 'playback_failed';
    const COMMAND_PAUSE = 'pause';
    const COMMAND_STOP = 'stop';
    const COMMAND_NEXT = 'next';
    const COMMAND_PREVIOUS = 'previous';
    const COMMAND_RESUME_PLAYBACK = 'resume_playback';
    const COMMAND_START_OVER = 'start_over';
    const COMMAND_SHUFFLE_ON = 'shuffle_on';
    const COMMAND_SHUFFLE_OFF = 'shuffle_off';
    const COMMAND_LOOP_ON = 'loop_on';
    const COMMAND_LOOP_OFF = 'loop_off';

    private $_blockId;

    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    /**
     * @var \Convo\Core\Workflow\IConversationProcessor[]
     */
    private $_processors	=	[];

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_fallback = [];

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_notFound = [];

    /**
     * @var IRequestFilter
     */
    private $_filter  =   null;

    private $_contextId;

    private $_blockName;

    public function __construct($properties, \Convo\Core\ConvoServiceInstance $service, \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory)
    {
        parent::__construct($properties);
        $this->setService($service);
        $this->_packageProviderFactory    =   $packageProviderFactory;

        $this->_blockId		    =	$properties['block_id'];
        $this->_blockName       =   $properties['name'] ?? 'Nameless block';
        $this->_contextId		=	$properties['context_id'];

        if ( isset( $properties['fallback'])) {
            foreach ( $properties['fallback'] as $fallback) {
                $this->addFallback( $fallback);
            }
        }

        if ( isset( $properties['not_found'])) {
            foreach ( $properties['not_found'] as $notFound) {
                $this->addNotFound( $notFound);
            }
        }

        foreach ( $properties['additional_processors'] as $processor) {
            /* @var $processor \Convo\Core\Workflow\IConversationProcessor */
            $this->_logger->info("Adding cpu ");
            $this->addProcessor( $processor);
        }

        // intents
        // play song intent
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.PlaySong',
            'values' => ["command" =>self::COMMAND_START_PLAYBACK]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // next intent
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.NextIntent',
            'values' => ["command" =>self::COMMAND_NEXT]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'actions.intent.MEDIA_STATUS',
            'values' => ["command" =>self::COMMAND_NEXT]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // previous intent
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.PreviousIntent',
            'values' => ["command" =>self::COMMAND_PREVIOUS]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // pause song intent
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.StopIntent',
            'values' => ["command" =>self::COMMAND_PAUSE]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.CancelIntent',
            'values' => ["command" =>self::COMMAND_PAUSE]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.PauseIntent',
            'values' => ["command" =>self::COMMAND_PAUSE]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // resume song intent
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.ResumeIntent',
            'values' => ["command" =>self::COMMAND_RESUME_PLAYBACK]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.ContinuePlayback',
            'values' => ["command" =>self::COMMAND_CONTINUE_PLAYBACK]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // repeat intent
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.RepeatIntent',
            'values' => ["command" =>self::COMMAND_START_OVER]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.StartOverIntent',
            'values' => ["command" =>self::COMMAND_START_OVER]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // shuffle intent's
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.ShuffleOffIntent',
            'values' => ["command" =>self::COMMAND_SHUFFLE_OFF]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.ShuffleOnIntent',
            'values' => ["command" =>self::COMMAND_SHUFFLE_ON]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // loop intent's
        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.LoopOffIntent',
            'values' => ["command" =>self::COMMAND_LOOP_OFF]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.LoopOnIntent',
            'values' => ["command" =>self::COMMAND_LOOP_ON]
        ], $this->_packageProviderFactory);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // amazon alexa audio controls
        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'PlaybackController.NextCommandIssued',
            'values' => ["command" =>self::COMMAND_NEXT]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'PlaybackController.PreviousCommandIssued',
            'values' => ["command" =>self::COMMAND_PREVIOUS]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'PlaybackController.PlayCommandIssued',
            'values' => ["command" =>self::COMMAND_RESUME_PLAYBACK]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'PlaybackController.PauseCommandIssued',
            'values' => ["command" =>self::COMMAND_STOP]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'PlaybackController.PauseCommandIssued',
            'values' => ["command" =>self::COMMAND_STOP]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // amazon alexa audio events
        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'AudioPlayer.PlaybackStarted',
            'values' => ["command" =>self::COMMAND_PLAYBACK_STARTED]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'AudioPlayer.PlaybackNearlyFinished',
            'values' => ["command" =>self::COMMAND_PLAYBACK_NEARLY_FINISHED]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'AudioPlayer.PlaybackFinished',
            'values' => ["command" =>self::COMMAND_PLAYBACK_FINISHED]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'AudioPlayer.PlaybackStopped',
            'values' => ["command" =>self::COMMAND_PLAYBACK_STOPPED]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        $reader   =   new \Convo\Pckg\Core\Filters\PlatformIntentReader([
            'intent' => 'AudioPlayer.PlaybackFailed',
            'values' => ["command" =>self::COMMAND_PLAYBACK_FAILED]
        ]);
        $reader->setLogger( $this->_logger);
        $reader->setService( $this->getService());
        $readers[]    =   $reader;

        // filters to be added
        $filter =   new \Convo\Pckg\Core\Filters\IntentRequestFilter( [
            'readers' => $readers
        ]);
        $filter->setLogger( $this->_logger);
        $filter->setService( $this->getService());
        $this->addChild( $filter);
        $this->_filter = $filter;
    }

    /**
     * @inheritDoc
     */
    public function read(IConvoRequest $request, IConvoResponse $response)
    {
        parent::read( $request, $response);
    }

    public function addProcessor(\Convo\Core\Workflow\IConversationProcessor $processor)
    {
        $this->_processors[] = $processor;
        $this->addChild($processor);
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IRunnableBlock::getRole()
     */
    public function getRole()
    {
        return IRunnableBlock::ROLE_MEDIA_PLAYER;
    }

    public function getName()
    {
        return $this->_blockName;
    }

    /**
     * @inheritDoc
     */
    public function run(IConvoRequest $request, IConvoResponse $response)
    {
        try {
            $context = $this->_getMediaSourceContext();
        } catch (\Exception $e) {
            $response->addText($e->getMessage());
            return;
        }
        $result = new \Convo\Core\Workflow\DefaultFilterResult();

        if (is_a($request, '\Convo\Core\Workflow\IIntentAwareRequest')) {
            $result    =   $this->_filter->filter( $request);
        }

        $this->_logger->debug("Filter result empty [" . $result->isEmpty()  . "] and [" . print_r($result->getData(), true) . "]");

        $processors	= $this->_collectAllAccountableProcessors();
        if ( empty( $processors)) {
            $this->_logger->debug( 'No service processors defined in ['.$this.']');
        }

        if (!$result->isEmpty()) {
            if ( $request->isMediaRequest())
            {
                /** @var IConvoAudioResponse $response */
                /** @var IConvoAudioRequest $request */
                $this->_handleResult($result, $response, $request, $context);
            } else {
                $this->_readFallback($request, $response);
            }
        } else {
            if (!empty($processors)) {
                foreach ( $processors as $processor)
                {
                    if ( $this->_processAccountableProcessor( $request, $response, $processor)) {
                        return;
                    }
                }
            }
            $this->_readFallback($request, $response);
        }
    }

    // PREVIEW
    public function getPreview()
    {
        $pblock = new PreviewBlock($this->getName(), $this->getComponentId());
        $pblock->setLogger($this->_logger);

        // What the bot says first
        $section = new PreviewSection('Read', $this->_logger);
        $section->collect($this->getElements(), '\Convo\Core\Preview\IBotSpeechResource');
        $pblock->addSection($section);

        // Fallback text
        $section = new PreviewSection('Fallback', $this->_logger);
        $section->collect($this->getFallback(), '\Convo\Core\Preview\IBotSpeechResource');
        $pblock->addSection($section);

        $section = new PreviewSection('On Not Found', $this->_logger);
        $section->collect($this->_notFound, '\Convo\Core\Preview\IBotSpeechResource');
        $pblock->addSection($section);

        // User <-> Bot back and forth
        foreach ($this->getProcessors() as $processor)
        {
			/** @var \Convo\Pckg\Core\Processors\AbstractServiceProcessor $processor */
			$name = $processor->getName() !== '' ? $processor->getName() : 'Process - '.(new \ReflectionClass($processor))->getShortName().' ['.($processor->getId()).']';
			$section = new PreviewSection($name, $this->_logger);

			$section->collectOne($processor, '\Convo\Core\Preview\IUserSpeechResource');
			$section->collectOne($processor, '\Convo\Core\Preview\IBotSpeechResource');
			$pblock->addSection($section);
        }

        return $pblock;
    }

    /**
     * @inheritDoc
     */
    public function getComponentId()
    {
        return $this->_blockId;
    }

    /**
     * @inheritDoc
     */
    public function getProcessors()
    {
        return $this->_processors;
    }

    /**
     * @inheritDoc
     */
    public function evaluateString($string, $context = [])
    {
        $own_params		=	$this->getService()->getAllComponentParams( $this);
        return parent::evaluateString( $string, array_merge( $own_params, $context));
    }

    /**
     * @inheritDoc
     */
    public function getBlockParams($scopeType)
    {
        // Is it top level block?
        if ( $this->getParent() === $this->getService()) {
            return $this->getService()->getComponentParams( $scopeType, $this);
        }

        return parent::getBlockParams( $scopeType);
    }

    public function addFallback(\Convo\Core\Workflow\IConversationElement $element)
    {
        $this->_fallback[] = $element;
        $this->addChild($element);
    }

    public function addNotFound(\Convo\Core\Workflow\IConversationElement $element)
    {
        $this->_notFound[] = $element;
        $this->addChild($element);
    }

    /**
     * @return \Convo\Core\Workflow\IConversationElement[]
     */
    public function getFallback(): array
    {
        return $this->_fallback;
    }

    private function _readFallback( $request, $response)
    {
        foreach ($this->_fallback as $fallback)
        {
            /** @var \Convo\Core\Workflow\IConversationElement $fallback */
            $fallback->read( $request, $response);
        }
    }

    protected function _collectAllAccountableProcessors()
    {
        $processors = $this->_processors;
        try {
            $block	=	$this->getService()->getBlockByRole( IRunnableBlock::ROLE_SERVICE_PROCESSORS);
            $processors = [];
            $processors	=	array_merge($block->getProcessors(), $this->_processors);
        } catch ( \Convo\Core\ComponentNotFoundException $e) {
            $this->_logger->warning($e->getMessage());
        }

        return $processors;
    }

    protected function _processAccountableProcessor(
        \Convo\Core\Workflow\IConvoRequest $request,
        \Convo\Core\Workflow\IConvoResponse $response,
        \Convo\Core\Workflow\IConversationProcessor $processor)
    {
        $processor->setParent( $this);

        $result	=	$processor->filter( $request);


        $this->_logger->debug( 'Result data ['.print_r($result->getData(), true).'] in MediaBlock. Skipping ...');

        if ( $result->isEmpty()) {
            $this->_logger->debug( 'Processor ['.$processor.'] not appliable for ['.$request.'] in MediaBlock. Skipping ...');
            return false;
        }

        $params				=	$this->getBlockParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $params->setServiceParam( 'result', $result->getData());

        $this->_logger->debug('Processing with ['.$processor.'] in MediaBlock');

        $processor->process( $request, $response, $result);
        return true;
    }

    private function _readNotFound( $request, $response)
    {
        foreach ($this->_notFound as $notFound)
        {
            /** @var \Convo\Core\Workflow\IConversationElement $fallback */
            $notFound->read( $request, $response);
        }
    }

    private function _getMediaSourceContext()
    {
        $contextId = $this->evaluateString($this->_contextId);
        return $this->getService()->findContext($contextId)->getComponent();
    }

    private function _handleResult(IRequestFilterResult $result, IConvoAudioResponse $response, IConvoAudioRequest $request, IMediaSourceContext $context) {
        $command    =   $result->getSlotValue( 'command');

        switch ( $command) {
            case self::COMMAND_START_PLAYBACK:
                $searchQuery = $request->getSlotValues();
                $context->setShouldMovePointer(true);
                try {
                    $context->find();
                    $song = $context->current();
                    $this->_logger->debug("Handling [" . self::COMMAND_START_PLAYBACK . "]");
                    if ($song->isEmpty()) {
                        $this->_readNotFound($request, $response);
                    } else {
                        $response->playSong( $song);
                    }
                } catch (DataItemNotFoundException $e) {
                    $response->addText($e->getMessage());
                }
                break;
            case self::COMMAND_PLAYBACK_STARTED:
                $this->_logger->debug("Handling [" . self::COMMAND_PLAYBACK_STARTED . "]");
                $response->emptyResponse();
                break;
            case self::COMMAND_PLAYBACK_NEARLY_FINISHED:
                $this->_logger->debug("Handling [" . self::COMMAND_PLAYBACK_NEARLY_FINISHED. "]");
                $context->setShouldMovePointer(false);
                $playingSong = $context->current();
                $nextSong = $context->next();
                if ($nextSong->isEmpty()) {
                    if ($context->getLoopStatus() === true) {
                        $nextSong = $context->first();
                        $response->enqueueSong($playingSong, $nextSong);
                    } else {
                        $response->clearQueue();
                    }
                } else {
                    $response->enqueueSong($playingSong, $nextSong);
                }
                break;
            case self::COMMAND_PLAYBACK_FINISHED:
                $this->_logger->debug("Handling [" . self::COMMAND_PLAYBACK_FINISHED . "]");
                $context->setShouldMovePointer(false);
                if ($context->next()->isEmpty()) {
                    $context->movePointerTo(0);
                } else {
                    $currentSongIndex = $context->getPointerPosition() + 1;
                    $context->movePointerTo($currentSongIndex);
                }
                $context->setOffset(0);
                $response->emptyResponse();
                break;
            case self::COMMAND_PLAYBACK_STOPPED:
                $this->_logger->debug("Handling [" . self::COMMAND_PLAYBACK_STOPPED . "]");
                $context->setOffset($request->getOffset());
                $response->emptyResponse();
                break;
            case self::COMMAND_PLAYBACK_FAILED:
                $this->_logger->warning("Handling [" . self::COMMAND_PLAYBACK_FAILED . "]");
                $response->emptyResponse();
                break;
            case self::COMMAND_PAUSE:
                $this->_logger->debug("Handling [" . self::COMMAND_PAUSE . "]");
                $response->stopSong();
                break;
            case self::COMMAND_CONTINUE_PLAYBACK:
            case self::COMMAND_RESUME_PLAYBACK:
                $this->_logger->debug("Handling [" . self::COMMAND_RESUME_PLAYBACK . "]");
                $song = $context->current();
                $response->playSong($song, $context->getOffset());
                break;
            case self::COMMAND_NEXT:
                $this->_logger->debug("Handling [" . self::COMMAND_NEXT . "]");
                $context->setShouldMovePointer(true);
                $song = $context->next();
                if ($context->getLoopStatus() === true) {
                    if ($song->isEmpty()) {
                        $song = $context->first();
                        $response->playSong($song);
                    } else {
                        $response->playSong($song);
                    }
                } else {
                    if ($song->isEmpty()) {
                        $response->emptyResponse();
                    } else {
                        $response->playSong($song);
                    }
                }
                break;
            case self::COMMAND_PREVIOUS:
                $this->_logger->debug("Handling [" . self::COMMAND_PREVIOUS . "]");
                $context->setShouldMovePointer(true);
                $song = $context->previous();
                if ($song->isEmpty()) {
                    $response->emptyResponse();
                } else {
                    $response->playSong($song);
                }
                break;
            case self::COMMAND_START_OVER:
                $this->_logger->debug("Handling [" . self::COMMAND_START_OVER . "]");
                $song = $context->current();
                $response->playSong($song, 0);
                break;
            case self::COMMAND_SHUFFLE_ON:
                $this->_logger->debug("Handling [" . self::COMMAND_SHUFFLE_ON . "]");
                $response->emptyResponse();
                break;
            case self::COMMAND_SHUFFLE_OFF:
                $this->_logger->debug("Handling [" . self::COMMAND_SHUFFLE_OFF . "]");
                $response->emptyResponse();
                break;
            case self::COMMAND_LOOP_ON:
                $this->_logger->debug("Handling [" . self::COMMAND_LOOP_ON . "]");
                $context->setLoopStatus(true);
                if ($context->next()->isEmpty()) {
                    $context->setShouldMovePointer(true);
                    $playingSong = $context->current();
                    $nextSong = $context->first();
                    $response->enqueueSong($playingSong, $nextSong);
                } else {
                    $response->emptyResponse();
                }
                break;
            case self::COMMAND_LOOP_OFF:
                $this->_logger->debug("Handling [" . self::COMMAND_LOOP_OFF . "]");
                $context->setLoopStatus(false);
                if ($context->next()->isEmpty()) {
                    $response->clearQueue();
                } else {
                    $response->emptyResponse();
                }
                break;
            default:
                $this->_logger->debug("Handling default with [" . $command . "]");
                $response->emptyResponse();
                break;
        }
    }
}
