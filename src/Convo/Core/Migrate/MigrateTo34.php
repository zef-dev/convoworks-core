<?php


namespace Convo\Core\Migrate;

class MigrateTo34 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 34;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
            if (!isset($config["amazon"]["availability"])) {
                $config["amazon"]["availability"]['automatic_distribution'] = true;
            }
        }
        return parent::migrateConfig($config);
    }
}
