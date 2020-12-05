<?php


namespace Convo\Core\Migrate;


class MigrateTo21 extends AbstractMigration
{
    private $_servicesToFix = ["robo-numbers-test", 'new-locale-service', 'burger-master', 'holiday-countdown', 'irina-test', ];
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 21;
    }

    public function migrateMeta($meta)
    {
        if (in_array($meta['service_id'], $this->_servicesToFix)) {
            if (!isset($meta["release_mapping"]["dialogflow"])) {
                $meta["release_mapping"]["dialogflow"]["a"]["type"] = "develop";
                $meta["release_mapping"]["dialogflow"]["a"]["time_updated"] = 1596536951;
                $meta["release_mapping"]["dialogflow"]["a"]["time_propagated"] = 0;
            }
            if (!isset($meta["release_mapping"]["amazon"])) {
                $meta["release_mapping"]["amazon"]["a"]["type"] = "develop";
                $meta["release_mapping"]["amazon"]["a"]["time_updated"] = 1596536951;
                $meta["release_mapping"]["amazon"]["a"]["time_propagated"] = 0;
            }
        }
        return parent::migrateMeta($meta);
    }
}
