<?php


namespace Convo\Core\Migrate;


class MigrateTo24 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 24;
    }

    public function migrateConfig($config)
    {
        if (isset($config["dialogflow"])) {
            if (!isset($config["dialogflow"]["interfaces"])) {
                $config["dialogflow"]["interfaces"] = [];
            }
        }
        return parent::migrateConfig($config);
    }
}
