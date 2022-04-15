<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Filters;

use Convo\Core\Util\StrUtil;
use Convo\Core\Workflow\IIntentAwareRequest;

class PlatformIntentReader extends \Convo\Core\Workflow\AbstractWorkflowComponent implements \Convo\Core\Intent\IIntentAdapter
{
    private $_values;

    private $_rename;

    private $_intent;

    private $_disable;

    private $_id;

    public function __construct( $config)
    {
        parent::__construct( $config);

        $this->_intent = $config['intent'];
        $this->_disable = $config['disable'] ?? false;

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
        // return $this->evaluateString($this->_intent); throws error on preview, cannot get component params outside of request scope
    }

    public function accepts(IIntentAwareRequest $request)
    {
        $disable = $this->evaluateString( $this->_disable, $request->getSlotValues());
        if (!empty($this->_disable) && $disable) {
            $this->_logger->info('Ignoring accept in PlatformIntentReader [' . $disable . ']');
            return false;
        }

        return $request->getIntentName() === $this->_intent;
    }

    public function read( \Convo\Core\Workflow\IIntentAwareRequest $request)
    {
        $result = new \Convo\Core\Workflow\DefaultFilterResult();
        $intent = $this->evaluateString($this->_intent);

        $result->setSlotValue('intentName', $intent); // quickfix??

        $slots  =   $request->getSlotValues();

        if (!is_array($this->_rename) && is_string($this->_rename) && StrUtil::startsWith($this->_rename, '${')) {
            $rename = $this->evaluateString($this->_rename);
            $this->_logger->debug('Rename evaluated to ['.print_r($rename, true).']');
        }
        else if (is_array($this->_rename)) {
            $rename = $this->_rename;
        }

        foreach ( $slots as $key => $value)
        {
            if (isset($rename[$key])) {
                $this->_logger->info('Renaming incoming slot ['.$key.'] to ['.$rename[$key].']');
                $result->setSlotValue($rename[$key], $value);
                continue;
            }

            $result->setSlotValue( $key, $value);
        }

        if (!is_array($this->_values) && is_string($this->_values) && StrUtil::startsWith($this->_values, '${')) {
            /** @var array $values */
            $values = $this->evaluateString($this->_values);

            foreach ($values as $key => $value)
            {
                $result->setSlotValue($key, $value);
            }
        }
        else if (is_array($this->_values)) {
            foreach ($this->_values as $key => $value)
            {
                $result->setSlotValue($key, $this->getService()->evaluateString($value));
            }
        }

        return $result;
    }

    // UTIL
    public function __toString()
    {
        return get_class( $this).'['.$this->_intent.']['.$this->_id.']';
    }
}
