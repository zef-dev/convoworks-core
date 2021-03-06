<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Filters;


class PlatformIntentReader extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Intent\IIntentAdapter
{
    private $_values;

    private $_rename;

    private $_intent;
    
    private $_id;
    
    public function __construct( $config)
    {
        parent::__construct( $config);
        
        $this->_intent = $config['intent'];

        $this->_values = $config['values'] ?? [];
        $this->_rename = $config['rename'] ?? [];
        $this->_id     = $config['_component_id'] ?? ''; // todo generate default id
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getPlatformIntentName( $platformId)
    {
        return $this->_intent;
    }

    public function read( \Convo\Core\Workflow\IIntentAwareRequest $request)
    {
        $result =   new \Convo\Core\Workflow\DefaultFilterResult();
        
        $result->setSlotValue( 'intentName', $this->_intent); // quickfix??
        
        $slots  =   $request->getSlotValues();

        foreach ( $slots as $key => $value)
        {
            if ( isset( $this->_rename[$key])) {
                $result->setSlotValue( $this->_rename[$key], $value);
                continue;
            }

            $result->setSlotValue( $key, $value);
        }

        foreach ( $this->_values as $key => $value)
        {
            $result->setSlotValue( $key, $this->getService()->evaluateString( $value));
        }

        return $result;
    }
    
    // UTIL
    public function __toString()
    {
        return get_class( $this).'['.$this->_intent.']['.$this->_id.']';
    }

}