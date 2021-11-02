<?php


namespace Convo\Core\Migrate;

class MigrateTo35 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 35;
    }

    public function migrateConfig($config)
    {
        if (isset($config["amazon"])) {
			if (!isset($config["amazon"]["upload_own_skill_icons"])) {
				if (empty($config["amazon"]["skill_preview_in_store"]['small_skill_icon']) && empty($config["amazon"]["skill_preview_in_store"]['large_skill_icon'])) {
					$config["amazon"]["upload_own_skill_icons"] = false;
				} else {
					$config["amazon"]["upload_own_skill_icons"] = true;
				}
			}
        }
        return parent::migrateConfig($config);
    }
}
