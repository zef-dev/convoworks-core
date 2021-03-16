<?php


namespace Convo\Core\Migrate;

class MigrateTo33 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 33;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
            if (!isset($config["amazon"]["account_linking_mode"])) {
                $config["amazon"]["account_linking_mode"] = 'installation';
            }
        }
        return parent::migrateConfig($config);
    }
}
