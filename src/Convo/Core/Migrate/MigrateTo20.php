<?php


namespace Convo\Core\Migrate;


class MigrateTo20 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 20;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
            if (!isset($config["amazon"]["default_locale"])) {
                $config["amazon"]["default_locale"] = 'en-US';
            }

            if (!isset($config["amazon"]["supported_locales"])) {
                $config["amazon"]["supported_locales"] = ['en-US'];
            }

            if (!isset($config["amazon"]["propagate_to_all_english"])) {
                $config["amazon"]["propagate_to_all_english"] = false;
            }
        }

        if (isset($config["dialogflow"])) {
            if (!isset($config["dialogflow"]["default_locale"])) {
                $config["dialogflow"]["default_locale"] = 'en';
            }

            if (!isset($config["dialogflow"]["supported_locales"])) {
                $config["dialogflow"]["supported_locales"] = ['en'];
            }
        }

        return parent::migrateConfig($config);
    }
}
