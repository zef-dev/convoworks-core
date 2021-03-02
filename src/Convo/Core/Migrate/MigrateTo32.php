<?php


namespace Convo\Core\Migrate;


use Convo\Core\Adapters\Alexa\AmazonSkillManifest;

class MigrateTo32 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 32;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
            if (!isset($config["amazon"]["enable_account_linking"])) {
                $config["amazon"]["enable_account_linking"] = false;
            }
        }
        return parent::migrateConfig($config);
    }

    private function _invocationToName($invocation)
    {
        return ucwords($invocation);
    }
}
