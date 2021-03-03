<?php


namespace Convo\Core\Migrate;


class MigrateTo30 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 30;
    }

    public function migrateMeta($meta)
    {
        if (!isset($meta["default_locale"])) {
            $meta["default_language"] = 'en-US';
        }

        if (!isset($meta["supported_locales"])) {
            $meta["default_language"] = ['en-US'];
        }

        return parent::migrateMeta($meta);
    }
}
