<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Filters;

use Convo\Core\Preview\PreviewSpeechPart;

class ConvoIntentReader extends PlatformIntentReader implements \Convo\Core\Intent\IIntentDriven, \Convo\Core\Preview\IUserSpeechResource
{
    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    private $_requiredSlots =   [];

    public function __construct($config, $packageProviderFactory)
    {
        parent::__construct( $config);

        $this->_packageProviderFactory  =   $packageProviderFactory;

        if (isset($config['required_slots']) && $config['required_slots'] !== '')
        {
            $parts = explode(',', $config['required_slots']);

            if (count($parts) === 1 && empty($parts[0]))
            {
                $this->_logger->warning('Exploded empty string and got one empty element.');
                return;
            }

            $this->_requiredSlots = array_map(
                function($slot) { return trim($slot); },
                $parts
            );
        }
    }

    public function read( \Convo\Core\Workflow\IIntentAwareRequest $request)
    {
        $result =   parent::read( $request);

        foreach ( $this->_requiredSlots as $required) {
            if ( $result->isSlotEmpty( $required)) {
                $this->_logger->warning( 'Required slot ['.$required.'] is empty for intent ['.$request->getIntentName().']. Returning empty result ...');
                return new \Convo\Core\Workflow\DefaultFilterResult();
            }
        }

        return $result;
    }

    public function getPlatformIntentName( $platformId)
    {
        $intent     =   $this->getPlatformIntentModel( $platformId);
        return $intent->getName();
    }

    /**
     * {@inheritDoc}
     * @see \Convo\Core\Intent\IIntentDriven::getPlatformIntentModel()
     */
    public function getPlatformIntentModel( $platformId)
    {
        $this->_logger->debug( 'Searching for platform ['.$platformId.'] variant of intent ['.parent::getPlatformIntentName( $platformId).']');

        $service = $this->getService();

        $provider = $this->_packageProviderFactory->getProviderFromPackageIds($service->getPackageIds());

        try {
            $intent     =  $this->getService()->getIntent( parent::getPlatformIntentName( $platformId));
        } catch ( \Convo\Core\ComponentNotFoundException $e) {
            $this->_logger->debug( $e->getMessage());
            $sys_intent =   $provider->getIntent( parent::getPlatformIntentName( $platformId));
            $intent     =   $sys_intent->getPlatformModel( $platformId);
        }

        $this->_logger->debug( 'Returning intent ['.$intent.']');

        return $intent;
    }

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
                $part->addText('', 'Unknown intent');

                return $part;
            }
        }

        $part = new PreviewSpeechPart($this->getId());

        foreach ($intent->getUtterances() as $utterance)
        {
            // $this->_logger->debug('Got utterance ['.print_r($utterance, true).']');
            $part->addText($utterance->getText(), $intent->getName());
        }

        return $part;
    }
}
