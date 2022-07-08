<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Filters;

use Convo\Core\Preview\PreviewSpeechPart;
use Convo\Core\Workflow\IIntentAwareRequest;
use Convo\Pckg\Core\Filters\PlatformIntentReader;

class DialogIntentSlotFilter extends PlatformIntentReader implements \Convo\Core\Preview\IUserSpeechResource, IDialogFilter
{
    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    private $_targetSlot = '';

    private $_alexaPrompts = [];

    public function __construct($config, $packageProviderFactory)
    {
        parent::__construct( $config);

        $this->_packageProviderFactory  =   $packageProviderFactory;

        $this->_targetSlot = $config['target_slot'] ?? '';
        $this->_alexaPrompts = $config['alexa_prompts'] ?? [];
    }

    public function getTargetSlot() {
        return $this->_targetSlot;
    }

    public function getAlexaPrompts() {
        $alexaPrompts = [];
        foreach ($this->_alexaPrompts as $alexaPrompt) {
            $alexaPrompts[$this->getTargetSlot()][] = $alexaPrompt->getAlexaPrompt();
        }
        return $alexaPrompts;
    }

    public function getUserUtterances() {
        $service = $this->getService();
        $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());
        $userUtterances = [];
        try {
            $intent = $this->getService()->getIntent(parent::getPlatformIntentName('dialogflow'));
        } catch (\Convo\Core\ComponentNotFoundException $e) {
            $this->_logger->debug($e->getMessage());

            try {
                $service = $this->getService();

                $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());

                $sys_intent = $provider->getIntent(parent::getPlatformIntentName('dialogflow'));
                $intent = $sys_intent->getPlatformModel('dialogflow');
            } catch (\Exception $e) {
                $this->_logger->debug($e->getMessage());
            }
        }

        foreach ($intent->getUtterances() as $utterance)
        {
            $parts = $utterance->getParts();
            $text = '';
            foreach ($parts as $part) {
                if (isset($part['type']) && isset($part['text'])) {
                    try {
                        $entity = $provider->getEntity($part['type']);
                    } catch (\Convo\Core\ComponentNotFoundException $e) {
                        $entity = $service->getEntity($part['type']);
                    }
                    $text .= ' '.'{'.$entity->getName().'}';
                } else if (!isset($part['type']) && isset($part['text'])) {
                    $text .= ' '.$part['text'];
                }
            }
            $text = trim($text);
            $this->_logger->debug('Got text for Alexa ['.$text.']');
            $this->_logger->debug('Got utterance parts '.json_encode($parts).' of utterance text ['.$utterance->getText().']');
            $userUtterances[$this->getTargetSlot()][] = $text;
        }

        return $userUtterances;
    }

    /**
     * TODO: implement this right
     * @return PreviewSpeechPart
     */
    public function getSpeech()
    {
        // convo intent, need utterances
        // platform name is irrelevant
        try {
            $intent = $this->getService()->getIntent(parent::getPlatformIntentName('dialogflow'));
        } catch (\Convo\Core\ComponentNotFoundException $e) {
            $this->_logger->debug($e->getMessage());

            try {
                $service = $this->getService();

                $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());

                $sys_intent = $provider->getIntent(parent::getPlatformIntentName('dialogflow'));
                $intent = $sys_intent->getPlatformModel('dialogflow');
            } catch (\Exception $e) {
                $this->_logger->debug($e->getMessage());

                $part = new PreviewSpeechPart($this->getId());
                $part->setIntentSource('Unknown');
                $part->addText('');

                return $part;
            }
        }

        $part = new PreviewSpeechPart($this->getId());
        $part->setIntentSource($intent->getName());

        foreach ($intent->getUtterances() as $utterance)
        {
            $part->addText($utterance->getText());
        }

        return $part;
    }

    // UTIL
    public function __toString()
    {
        return parent::__toString();
    }
}
