<?php declare(strict_types=1);


namespace Convo\Core\Adapters\Google\Dialogflow;

use Convo\Core\Factory\PackageProviderFactory;
use Convo\Core\ConvoServiceInstance;
use Convo\Core\ComponentNotFoundException;

class DialogflowSlotParser
{
    
    /**
     * @var PackageProviderFactory
     */
    private $_packageProviderFactory;


    public function __construct( $logger, $packageProviderFactory)
    {
        $this->_packageProviderFactory  =   $packageProviderFactory;
        $this->_logger		            =	$logger;
    }


    /**
     * @param ConvoServiceInstance $service
     * @param string $intentName
     * @param array $data
     * @return array
     */
    public function parseSlotValues( $service, $intentName, $data)
	{
		$values	=	array();
		
		$provider = $this->_packageProviderFactory->getProviderFromPackageIds( $service->getPackageIds());
		
		$this->_logger->debug( 'Searching for intent ['.$intentName.']');
		try {
		    $intent_model = $service->getIntent( $intentName);
		} catch ( ComponentNotFoundException $e) {
		    $sys_intent = $provider->getIntent( $intentName);
		    $intent_model = $sys_intent->getPlatformModel( 'dialogflow_es');
		}
		
		if ( isset( $data['queryResult']['parameters']))
		{
			foreach ( $data['queryResult']['parameters'] as $key=>$slot)
			{
				if ( empty( $slot)) {
					continue;
				}

				$newKey = $this->_replaceWithUnderscoreKeyName($key);
				
				if ( !$this->_isSlotValid( $newKey, $slot)) {
					$this->_logger->debug( 'Found not valid slot ['.$newKey.']');
					continue;
				}
				
				$entity_type = $intent_model->getEntityTypeBySlot( $newKey);
			    
				try {
				    $entity = $service->getEntity( $entity_type);
				} catch ( ComponentNotFoundException $e) {
				    $entity = $provider->getEntity( $entity_type);
				}
				
				$value              =   $this->_useOriginalISlotValuefExists( $newKey, $slot);
				$values[$newKey]	=	$entity->parseValue( $value);

// 				if ( is_array( $slot) && isset( $slot['name'])) {
// 				    $values[$newKey]	=	$slot['name'];
// 				} else {
// 				    $values[$newKey]	=	$this->_useOriginalISlotValuefExists( $newKey, $slot);
// 				}

				$this->_logger->debug( 'Parsed slot value ['.$newKey.'] => ['.print_r($values[$newKey], true).']');
			}
		}

		return $values;
	}

	private function _useOriginalISlotValuefExists( $name, $value) {

	    if ( isset( $this->_data['queryResult']['outputContexts']) && is_array( $this->_data['queryResult']['outputContexts'])) {
	        foreach ( $this->_data['queryResult']['outputContexts'] as $context) {
	            if ( isset( $context['parameters'][$name.'.original']) && !empty( $context['parameters'][$name.'.original'])) {
	                return $context['parameters'][$name.'.original'];
	            }
	        }
	    }

	    return $value;
	}

    private function _isSlotValid($key, $slot) {
        $this->_logger->debug( 'Slot data to validate: '.$key.' ['.print_r($slot, true).']');

		if ( !isset($slot) && empty($slot)) {
			$this->_logger->debug( 'Found empty slot ['.$key.']');
			return false;
		}

		return true;
	}

    private function _replaceWithUnderscoreKeyName($key)
    {
        return str_replace( '-', '_', $key);
    }


    // UTIL
    public function __toString()
    {
        return get_class( $this).'';
    }

}
