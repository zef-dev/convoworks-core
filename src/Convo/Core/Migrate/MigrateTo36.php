<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo36 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 36;
    }

    protected function _migrateComponent($componentData)
    {
		if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\ElementRandomizer') {
			if (!isset($componentData['properties']['loop'])) {
				$componentData['properties']['loop'] = true;
			}

			if (isset($componentData['properties']['namespace'])) {
				unset($componentData['properties']['namespace']);
			}
		}

        return $componentData;
    }
}
