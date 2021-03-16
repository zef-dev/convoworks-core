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
                $config["amazon"]["account_linking_mode"] = 'amazon';
            }

            if (!isset($config["amazon"]["account_linking_config"])) {
                $config["amazon"]["account_linking_config"]['skip_on_enablement'] = true;
                $config["amazon"]["account_linking_config"]['authorization_url'] = 'https://www.amazon.com/ap/oa';
                $config["amazon"]["account_linking_config"]['access_token_url'] = 'https://api.amazon.com/auth/o2/token';
                $config["amazon"]["account_linking_config"]['client_id'] = '';
                $config["amazon"]["account_linking_config"]['client_secret'] = '';
                $config["amazon"]["account_linking_config"]['scopes'] = '';
                $config["amazon"]["account_linking_config"]['domains'] = '';
            }
        }
        return parent::migrateConfig($config);
    }
}
