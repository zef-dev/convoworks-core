<?php


namespace Convo\Core\Migrate;


class MigrateTo23 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 23;
    }

    public function migrateConfig($config)
    {
        if (isset($config["dialogflow"])) {
            if (!isset($config["dialogflow"]["default_timezone"])) {
                $config["dialogflow"]["default_timezone"] = "Europe/Madrid";
            }
        }
        return parent::migrateConfig($config);
    }
}
