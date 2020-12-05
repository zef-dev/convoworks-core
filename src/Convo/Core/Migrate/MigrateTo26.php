<?php


namespace Convo\Core\Migrate;


class MigrateTo26 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 26;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
            if (!isset($config["amazon"]["interaction_model_sensitivity"])) {
                $config["amazon"]["interaction_model_sensitivity"] = "LOW";
            }
        }
        return parent::migrateConfig($config);
    }
}
