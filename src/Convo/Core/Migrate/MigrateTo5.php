<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo5 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 5;
    }

    protected function _migrateService($serviceData)
    {
        $serviceData = parent::_migrateService($serviceData);

        foreach ($serviceData['blocks'] as &$block)
        {
            if (!isset($block['properties']['default'])) {
                $block['properties']['default'] = [];
            }

            $default = null;
            $found = false;
            
            foreach ($block['properties']['processors'] as &$processor)
            {
                if (isset($processor['properties']['nok']))
                {
                    if (!$found) {
                        $default = [$processor['properties']['nok']];
                        $found = true;
                    }

                    unset($processor['properties']['nok']);
                }
            }

            if ($default)
            {
                $block['properties']['default'] = $default;
            }
        }

        return $serviceData;
    }
}