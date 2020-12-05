<?php


namespace Convo\Core\Migrate;


use Convo\Core\Adapters\Alexa\AmazonSkillManifest;

class MigrateTo29 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 29;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
            if (!isset($config["amazon"]["endpoint_ssl_certificate_type"])) {
                $config["amazon"]["endpoint_ssl_certificate_type"] = AmazonSkillManifest::CERTIFICATE_TYPE_WILDCARD;
            }
        }
        return parent::migrateConfig($config);
    }
}
