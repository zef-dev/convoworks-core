<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Media\RadioStream;
use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Workflow\IConvoAudioRequest;
use Convo\Core\Workflow\IConvoAudioResponse;
use Convo\Core\Workflow\IConvoRadioStreamResponse;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Convo\Core\Workflow\IMediaSourceContext;
use Convo\Core\Workflow\IRequestFilter;
use Convo\Core\Workflow\IRequestFilterResult;
use Convo\Core\Workflow\IRunnableBlock;
use Convo\Core\Workflow\IConversationElement;

class RadioBlock extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IRunnableBlock
{

    const COMMAND_CONTINUE_PLAYBACK = 'continue_playback';
    const COMMAND_PLAYBACK_STARTED = 'playback_started';
    const COMMAND_PLAYBACK_NEARLY_FINISHED = 'playback_nearly_finished';
    const COMMAND_PLAYBACK_FINISHED = 'playback_finished';
    const COMMAND_PLAYBACK_STOPPED = 'playback_stopped';
    const COMMAND_PLAYBACK_FAILED = 'playback_failed';
    const COMMAND_PAUSE = 'pause';
    const COMMAND_CANCEL = 'cancel';
    const COMMAND_STOP = 'stop';
    const COMMAND_NEXT = 'next';
    const COMMAND_PREVIOUS = 'previous';
    const COMMAND_RESUME_PLAYBACK = 'resume_playback';
    const COMMAND_START_OVER = 'start_over';
    const COMMAND_REPEAT = 'repeat';
    const COMMAND_SHUFFLE_ON = 'shuffle_on';
    const COMMAND_SHUFFLE_OFF = 'shuffle_off';
    const COMMAND_LOOP_ON = 'loop_on';
    const COMMAND_LOOP_OFF = 'loop_off';

    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_fallback      =   [];

    /**
     * @var IRequestFilter
     */
    private $_filter  =   null;

    private $_blockId;

    private $_blockName;

    private $_mediaInfoVar;

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_onActionNotSupported    =   [];

    public function __construct($properties, \Convo\Core\ConvoServiceInstance $service, \Convo\Core\Factory\PackageProviderFactory $packageProviderFactory)
    {
        parent::__construct($properties);
        $this->setService($service);
        $this->_packageProviderFactory    =   $packageProviderFactory;

        $this->_blockId		    =	$properties['block_id'];
        $this->_blockName       =   $properties['name'] ?? 'Nameless block';
        $this->_mediaInfoVar	=	$properties['media_info_var'] ?? 'media_info';

        foreach ( $properties['on_action_not_supported'] as $element) {
            $this->_onActionNotSupported[]        =   $element;
            $this->addChild( $element);
        }

        if ( isset( $properties['fallback'])) {
            foreach ( $properties['fallback'] as $fallback) {
                $this->addFallback( $fallback);
            }
        }

        // intents
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

        $reader   =   new \Convo\Pckg\Core\Filters\ConvoIntentReader([
            'intent' => 'convo-core.CancelIntent',
            'values' => ["command" =>self::COMMAND_CANCEL]
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
            'values' => ["command" =>self::COMMAND_REPEAT]
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
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IRunnableBlock::getRole()
     */
    public function getRole()
    {
        return IRunnableBlock::ROLE_RADIO_STREAM;
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IRunnableBlock::getName()
     */
    public function getName()
    {
        return $this->_blockName;
    }

    /**
     * @inheritDoc
     */
    public function getComponentId()
    {
        return $this->_blockId;
    }

    public function read( IConvoRequest $request, IConvoResponse $response)
    {
    }

    public function getElements() {
        return [];
    }

    public function getProcessors() {
        return [];
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IRunnableBlock::run()
     */
    public function run( IConvoRequest $request, IConvoResponse $response)
    {
        $info_var   =   $this->evaluateString( $this->_mediaInfoVar);

        $result = new \Convo\Core\Workflow\DefaultFilterResult();

        if ( is_a( $request, '\Convo\Core\Workflow\IIntentAwareRequest')) {
            $result    =   $this->_filter->filter( $request);
        }

        $req_params =   $this->getService()->getComponentParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST, $this);
        $req_params->setServiceParam( $info_var, $request->getPlatformData());

        $this->_logger->debug( "Filter result empty [" . $result->isEmpty()  . "] and [" . print_r( $result->getData(), true) . "]");

        if ( !$result->isEmpty())
        {
            /** @var IConvoRadioStreamResponse $response */
            /** @var IConvoAudioRequest $request */
            $this->_handleResult( $result, $response, $request);
        }
        else
        {
            $this->_logger->info( 'Result is empty. Going to read failback.');
            $this->_readFallback( $request, $response);
        }
    }

    private function _handleResult( IRequestFilterResult $result, IConvoRadioStreamResponse $response, IConvoAudioRequest $request)
    {
        $command    =   $result->getSlotValue( 'command');

        $this->_logger->info( "Handling [" . $command . "]");

        switch ( $command) {
            // SESSION
            case self::COMMAND_CANCEL:
                $response->emptyResponse();
                break;
            case self::COMMAND_PAUSE:
                $response->stopRadioStream();
                break;
            case self::COMMAND_CONTINUE_PLAYBACK:
            case self::COMMAND_RESUME_PLAYBACK:
                $audioItem = $this->_getAudioItemFromToken($request);
                $this->_logger->debug('Logging audio item ['. json_encode($audioItem).']');
                $radioStream = new RadioStream(
                    $audioItem['stream_url'],
                    $audioItem['radio_station_name'],
                    $audioItem['radio_station_slogan'],
                    $audioItem['radio_station_logo_url']
                );
                $response->startRadioStream($radioStream);
                break;

            case self::COMMAND_START_OVER:
            case self::COMMAND_REPEAT:
            case self::COMMAND_NEXT:
            case self::COMMAND_PREVIOUS:
            case self::COMMAND_LOOP_ON:
            case self::COMMAND_LOOP_OFF:
            case self::COMMAND_SHUFFLE_ON:
            case self::COMMAND_SHUFFLE_OFF:
                $this->_readFallbackOr( $request, $response, $this->_onActionNotSupported);
                break;
            default:
                $this->_logger->notice( "Using default, empty response for [" . $command . "]");
                $response->emptyResponse();
                break;
        }
    }

    // PREVIEW
    public function getPreview()
    {
        $pblock = new PreviewBlock($this->getName(), $this->getComponentId());
        $pblock->setLogger($this->_logger);

        $section = new PreviewSection('Action Not Supported', $this->_logger);
        $section->collect( $this->_onActionNotSupported, '\Convo\Core\Preview\IBotSpeechResource');
        $pblock->addSection($section);

        // Fallback text
        $section = new PreviewSection('Fallback', $this->_logger);
        $section->collect($this->getFallback(), '\Convo\Core\Preview\IBotSpeechResource');
        $pblock->addSection($section);

        return $pblock;
    }


    public function addFallback(\Convo\Core\Workflow\IConversationElement $element)
    {
        $this->_fallback[] = $element;
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
        if (!empty($this->_fallback)) {
            foreach ($this->_fallback as $fallback)
            {
                /** @var \Convo\Core\Workflow\IConversationElement $fallback */
                $fallback->read( $request, $response);
            }
        }
        else {
            try {
                $default_fallback = $this->getService()->getBlockByRole(IRunnableBlock::ROLE_DEFAULT_FALLBACK);
                $default_fallback->read($request, $response);
            } catch (\Convo\Core\DataItemNotFoundException $e) {
            }
        }
    }


    /**
     * Executes read on given collection of elements or failback if collection is empty
     * @param IConvoRequest $request
     * @param IConvoAudioResponse $response
     * @param IConversationElement[] $collection
     */
    private function _readFallbackOr( $request, $response, $collection=[])
    {
        if ( empty( $request->getSessionId())) {
            $this->_logger->info( 'Sessionless request. Exiting with empty response ...');
            $response->emptyResponse();
            return;
        }

        if ( empty( $collection)) {
            $collection =   $this->_fallback;
        }

        foreach ( $collection as $element) {
            $element->read( $request, $response);
        }

        $response->setShouldEndSession( true);
    }

    private function _getAudioItemFromToken(IConvoAudioRequest $request)
    {
        $audioItemTokenData = $request->getAudioItemToken();
        if (!empty($audioItemTokenData)) {
            $this->_logger->info('Decoded audio token [' . base64_decode($audioItemTokenData) .']');
            $audioItemTokenData = unserialize(base64_decode($audioItemTokenData));
        }

        return $audioItemTokenData['radio_stream'] ?? [];
    }

    // UTIL
    public function __toString() {
        return parent::__toString().'['.$this->_blockId.']['.$this->_blockName.']['.$this->_mediaInfoVar.']';
    }
}
