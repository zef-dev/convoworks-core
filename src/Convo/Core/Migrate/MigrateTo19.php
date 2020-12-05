<?php


namespace Convo\Core\Migrate;


class MigrateTo19 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 19;
    }

    protected function _migrateComponent($componentData)
    {
        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\TextResponseElement') {
            if (!isset($componentData['properties']['alexa_domain'])) {
                $componentData['properties']['alexa_domain'] = 'normal';
            }
            if (!isset($componentData['properties']['alexa_emotion'])) {
                $componentData['properties']['alexa_emotion'] = 'neutral';
            }
            if (!isset($componentData['properties']['alexa_emotion_intensity'])) {
                $componentData['properties']['alexa_emotion_intensity'] = 'medium';
            }
        }

        return $componentData;
    }
}