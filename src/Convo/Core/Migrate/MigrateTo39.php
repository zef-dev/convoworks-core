<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo39 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 39;
    }

    protected function _migrateComponent($componentData)
    {
		if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\MediaBlock') {
			if (!isset($componentData['properties']['last_media_info_var'])) {
				$componentData['properties']['last_media_info_var'] = 'last_media_info';
			}
		}

        return $componentData;
    }
}
