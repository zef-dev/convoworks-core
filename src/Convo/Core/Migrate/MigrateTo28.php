<?php


namespace Convo\Core\Migrate;


class MigrateTo28 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 28;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
            if (isset($config["amazon"]["default_locale"])) {
                unset($config["amazon"]["default_locale"]);
            }

            if (isset($config["amazon"]["supported_locales"])) {
                unset($config["amazon"]["supported_locales"]);
            }

            if (isset($config["amazon"]["propagate_to_all_english"])) {
                unset($config["amazon"]["propagate_to_all_english"]);
            }
        }

        if (isset($config["dialogflow"])) {
            if (isset($config["dialogflow"]["default_locale"])) {
                unset($config["dialogflow"]["default_locale"]);
            }

            if (isset($config["dialogflow"]["supported_locales"])) {
                unset($config["dialogflow"]["supported_locales"]);
            }
        }

        return parent::migrateConfig($config);
    }

    public function migrateMeta($meta)
    {
        if (!isset($meta["default_language"])) {
            $meta["default_language"] = 'en';
        }
        return parent::migrateMeta($meta);
    }
}
