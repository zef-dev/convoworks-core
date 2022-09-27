<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo40 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 40;
    }

    protected function _migrateComponent($componentData)
    {
		if ($componentData['class'] === '\\Convo\\Pckg\\Filesystem\\FilesystemMediaContext') {
			if (!isset($componentData['properties']['min_match_percentage'])) {
				$componentData['properties']['min_match_percentage'] = '80';
			}
		}

        return $componentData;
    }
}
