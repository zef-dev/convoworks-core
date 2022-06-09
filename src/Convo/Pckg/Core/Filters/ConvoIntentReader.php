<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Filters;

use Convo\Core\Preview\PreviewSpeechPart;
use Convo\Core\Workflow\IIntentAwareRequest;

class ConvoIntentReader extends PlatformIntentReader implements \Convo\Core\Intent\IIntentDriven, \Convo\Core\Preview\IUserSpeechResource
{
    /**
     * @var \Convo\Core\Factory\PackageProviderFactory
     */
    private $_packageProviderFactory;

    private $_disable;

    private $_requiredSlots =   [];

    public function __construct($config, $packageProviderFactory)
    {
        parent::__construct( $config);

        $this->_packageProviderFactory  =   $packageProviderFactory;
        $this->_disable = $config['disable'] ?? false;

        $this->_requiredSlots = $config['required_slots'] ?? [];
    }

    public function accepts(IIntentAwareRequest $request)
    {
        $disable = $this->evaluateString($this->_disable);
        if (!empty($this->_disable) && $disable) {
            $this->_logger->info('Ignoring accept in ConvoIntentReader [' . $disable . ']');
            return false;
        }

        if ($request->getIntentName() === $this->getPlatformIntentName($request->getIntentPlatformId()))
        {
            $this->_logger->info('Parent accepts request in ['.$this.']');

            if (!empty($this->_requiredSlots))
            {
                $this->_logger->debug('Required slots are present ['.print_r($this->_requiredSlots, true).']');
                $request_slots = $request->getSlotValues();
                $this->_logger->debug('Checking in real slots ['.print_r($request_slots, true).']');
                
                foreach ($this->_requiredSlots as $slot) {
                    if (!isset($request_slots[$slot]) || $request_slots[$slot] === null) {
                        $this->_logger->warning('Slot ['.$slot.'] is required but empty.');
                        return false;
                    }
                }
            }
            
            $this->_logger->info('Accepting request ['.$this.']');

            return true;
        }

        return false;
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
        return parent::__toString().'['.$this->_disable.']['.implode( ', ', $this->_requiredSlots).']';
    }
}
