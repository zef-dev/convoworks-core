<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo37 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 37;
    }

    protected function _migrateComponent($componentData)
    {
		if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\SetParamElement') {
			if (!isset($componentData['properties']['parameters'])) {
				$componentData['properties']['parameters'] = 'service';
			}
		}

        return $componentData;
    }
}
