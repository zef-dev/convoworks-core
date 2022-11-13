<?php declare(strict_types=1);

namespace Convo\Core\Adapters\Google\Dialogflow;

use Convo\Core\Intent\IIntentAndEntityLocator;

class DialogflowSlotParser
{
    
    /**
     * @var IIntentAndEntityLocator
     */
    private $_locator;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    

    public function __construct( $logger, $locator)
    {
        $this->_logger      =   $logger;
        $this->_locator     =   $locator;
    }


    /**
     * @param string $intentName
     * @param array $data
     * @return array
     */
    public function parseSlotValues( $intentName, $data)
	{
		$values	=	array();
		
		$this->_logger->debug( 'Searching for intent ['.$intentName.']');
		
		$intent_model = $this->_locator->getIntentModel( 'dialogflow_es', $intentName);
		
	    foreach ( $data as $key=>$slot)
		{
			if ( empty( $slot)) {
				continue;
			}

			$newKey = $this->_replaceWithUnderscoreKeyName( $key);
			
			if ( !$this->_isSlotValid( $newKey, $slot)) {
				$this->_logger->debug( 'Found not valid slot ['.$newKey.']');
				continue;
			}
			
			$entity_type = $intent_model->getEntityTypeBySlot( $newKey);
		    
			$this->_logger->debug( 'Searching for entity type ['.$entity_type.'] for slot ['.$newKey.']');
			
			$entity = $this->_locator->getEntityModel( 'dialogflow_es', $entity_type);
			
			$value              =   $this->_useOriginalISlotValuefExists( $newKey, $slot);
			$values[$newKey]	=	$entity->parseValue( $value);

// 				if ( is_array( $slot) && isset( $slot['name'])) {
// 				    $values[$newKey]	=	$slot['name'];
// 				} else {
// 				    $values[$newKey]	=	$this->_useOriginalISlotValuefExists( $newKey, $slot);
// 				}

			$this->_logger->debug( 'Parsed slot value ['.$newKey.'] => ['.print_r($values[$newKey], true).']');
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
