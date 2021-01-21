<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;


use Convo\Core\Preview\PreviewBlock;
use Convo\Core\Preview\PreviewSection;
use Convo\Core\Preview\PreviewUtterance;

class LoopBlock extends \Convo\Pckg\Core\Elements\ConversationBlock
{

    /**
     * @var \Convo\Core\Workflow\IConversationProcessor[]
     */
    private $_mainProcessors	=	array();

    /**
     * @var \Convo\Core\Workflow\IConversationElement[]
     */
    private $_done = [];

    private $_dataCollection;
    private $_item;

    private $_offset;
    private $_limit;
    private $_skipReset;


    public function __construct( $properties)
    {
        parent::__construct( $properties);

        $this->_dataCollection  =   $properties['data_collection'];
        $this->_item            =   $properties['item'];

        $this->_offset      =   $properties['offset'];
        $this->_limit       =   $properties['limit'];
        $this->_skipReset   =   $properties['skip_reset'];


        foreach ( $properties['main_processors'] as $processor) {
            /* @var $processor \Convo\Core\Workflow\IConversationProcessor */
            $this->_mainProcessors[] =   $processor;
            $this->addChild( $processor);
        }

        if ( isset( $properties['done'])) {
            foreach ( $properties['done'] as $done) {
                $this->_done[]  =   $done;
                $this->addChild( $done);
            }
        }
    }

    public function getOffset()
    {
        return intval( $this->evaluateString( $this->_offset));
    }

    public function getLimit()
    {
        return intval( $this->evaluateString( $this->_limit));
    }

    public function getItems()
    {
        $items         =   $this->evaluateString( $this->_dataCollection);
        if ( is_array( $items) && count( $items)) {
            $this->_logger->debug( 'Got items ['.$this->_dataCollection.']['.print_r( $items, true).']');
            return $items;
        }
        throw new \Exception( 'Provide non empty indexed array for ['.$this->_dataCollection.'] component parameter');
    }

    public function getPreview()
    {
        $preview = new PreviewBlock($this->getName(), $this->getComponentId());
        $preview->setLogger($this->_logger);

        $read = new PreviewSection('Read');
        foreach ($this->getElements() as $element)
        {
            /** @var \Convo\Core\Preview\IBotSpeechResource[] $read_speech */
            $read_speech = [];
            $this->_populateSpeech($read_speech, $element, '\Convo\Core\Preview\IBotSpeechResource');

            foreach ($read_speech as $part) {
                $read->addUtterance(new PreviewUtterance($part->getSpeech()->getText()));
            }
        }

        if (!empty($read_speech)) {
            $preview->addSection($read);
        }


        foreach ($this->_mainProcessors as $processor)
        {
            $processor_section = new PreviewSection('Main process - '.(new \ReflectionClass($processor))->getShortName().' ['.$processor->getId().']');

            /** @var \Convo\Core\Preview\IBotSpeechResource[] $user */
            $user = [];
            /** @var \Convo\Core\Preview\IBotSpeechResource[] $bot */
            $bot = [];
            $this->_populateSpeech($user, $processor, '\Convo\Core\Preview\IUserSpeechResource');
            $this->_populateSpeech($bot, $processor, '\Convo\Core\Preview\IBotSpeechResource');

            if (empty($user) && empty($bot)) {
                $this->_logger->debug('No user utterances or bot responses, skipping.');
                continue;
            }

            foreach ($user as $user_part)
            {
                $speech = $user_part->getSpeech();
                $utterance = new PreviewUtterance($speech->getText(), false, $speech->getIntentSource());
                $processor_section->addUtterance($utterance);
            }

            foreach ($bot as $bot_part)
            {
                $utterance = new PreviewUtterance($bot_part->getSpeech()->getText());
                $processor_section->addUtterance($utterance);
            }

            $preview->addSection($processor_section);
        }

        foreach ($this->getProcessors() as $processor)
        {
            $processor_section = new PreviewSection('Process - '.(new \ReflectionClass($processor))->getShortName().' ['.$processor->getId().']');

            /** @var \Convo\Core\Preview\IBotSpeechResource[] $user */
            $user = [];
            /** @var \Convo\Core\Preview\IBotSpeechResource[] $bot */
            $bot = [];
            $this->_populateSpeech($user, $processor, '\Convo\Core\Preview\IUserSpeechResource');
            $this->_populateSpeech($bot, $processor, '\Convo\Core\Preview\IBotSpeechResource');

            if (empty($user) && empty($bot)) {
                $this->_logger->debug('No user utterances or bot responses, skipping.');
                continue;
            }

            foreach ($user as $user_part)
            {
                $speech = $user_part->getSpeech();
                $utterance = new PreviewUtterance($speech->getText(), false, $speech->getIntentSource());
                $processor_section->addUtterance($utterance);
            }

            foreach ($bot as $bot_part)
            {
                $utterance = new PreviewUtterance($bot_part->getSpeech()->getText());
                $processor_section->addUtterance($utterance);
            }

            $preview->addSection($processor_section);
        }

        $done = new PreviewSection('Done');
        foreach ($this->_done as $element) {
            /** @var \Convo\Core\Preview\IBotSpeechResource[] $done_speech */
            $done_speech = [];
            $this->_populateSpeech($done_speech, $element, '\Convo\Core\Preview\IBotSpeechResource');

            foreach ($done_speech as $part) {
                $done->addUtterance(new PreviewUtterance($part->getSpeech()->getText()));
            }
        }

        if (!empty($done_speech)) {
            $preview->addSection($done);
        }

        $fallback = new PreviewSection('Fallback');
        foreach ($this->getFallback() as $element)
        {
            /** @var \Convo\Core\Preview\IBotSpeechResource[] $fallback_speech */
            $fallback_speech = [];
            $this->_populateSpeech($fallback_speech, $element, '\Convo\Core\Preview\IBotSpeechResource');

            foreach ($fallback_speech as $part) {
                $fallback->addUtterance(new PreviewUtterance($part->getSpeech()->getText()));
            }
        }

        if (!empty($fallback_speech)) {
            $preview->addSection($fallback);
        }



        return $preview;
    }

    public function read( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        $this->_loadItem();

        parent::read( $request, $response);
    }

    private function _loadItem()
    {
        $items         =   $this->getItems();
        $slot_name     =   $this->evaluateString( $this->_item);
        $status        =   $this->_getStatus( $items);

        $block_params  =   $this->getBlockParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION);
        $block_params->setServiceParam( $slot_name, array_merge( $status, ['value' => $items[$status['index']]]));
    }

    private function _getStatus( $items)
    {
        $items         =   $this->getItems();
        $slot_name     =   $this->evaluateString( $this->_item);
        $skip_reset    =   $this->evaluateString( $this->_skipReset);

        $block_params  =   $this->getBlockParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION);
        $req_params    =   $this->getService()->getServiceParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST);
        $returning     =   $req_params->getServiceParam( 'returning');

        $this->_logger->debug( 'Got returning ['.$returning.']');
        $this->_logger->debug( 'Got skip reset ['.$skip_reset.']');

        if ( !$returning && !$skip_reset) {
            $this->_logger->debug( 'Reset array iterration status when coming first time');
            $block_params->setServiceParam( $slot_name, $this->_getDefaultStatus( $items));
        }

        $status        =   $block_params->getServiceParam( $slot_name);
        $this->_logger->debug( 'Got loop status ['.print_r( $status, true).']');
        if ( empty( $status)) {
            $status    =   $this->_getDefaultStatus( $items);
        }

        $this->_logger->debug( 'Returning loop status ['.print_r( $status, true).']');

        return $status;
    }

    private function _getDefaultStatus( $items) {

        $start = $this->getOffset();

        $status    =   [
            'value' => null,
            'index' => $start,
            'natural' => $start + 1,
            'first' => true,
            'last' => !count( $items)
        ];
        return $status;
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IRunnableBlock::run()
     */
    public function run( \Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        $this->_loadItem();

        foreach ( $this->_mainProcessors as $processor)
        {
            if ( $this->_processProcessor( $request, $response, $processor))
            {
                $items         =   $this->getItems();
                $slot_name     =   $this->evaluateString( $this->_item);
                $status        =   $this->_getStatus( $items);
                $block_params  =   $this->getBlockParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION);

                if ( $status['last']) {
                    // last process was done
                    foreach ( $this->_done as $element) {
                        /* @var $element \Convo\Core\Workflow\IConversationElement */
                        $element->read( $request, $response);
                    }
                    return ;
                }

                // increase index

                $limit     =   $this->getLimit();
                if ( $limit) {
                    if ( $limit > count( $items)) {
                        $end    =   count( $items);
                    } else {
                        $end    =   $limit;
                    }
                } else {
                    $end    =   count( $items);
                }
                $index     =   $status['index'] + 1;
                $this->_logger->debug( 'Got limit ['.$limit.'] end ['.$end.'] index ['.$index.']');
                $status    =   array_merge( $status, [
                    'value' => null,
                    'index' => $index,
                    'natural' => $index+1,
                    'first' => false,
                    'last' => $index === ($end - 1)
                ]);

                $block_params->setServiceParam( $slot_name, $status);
                $this->read( $request, $response);
                return ;
            }
        }

        parent::run( $request, $response);
    }


    // UTIL
    public function __toString()
    {
        return parent::__toString().'[]';
    }
}
