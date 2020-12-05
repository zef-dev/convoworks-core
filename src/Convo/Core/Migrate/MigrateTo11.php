<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo11 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 11;
    }

    protected function _migrateComponent($componentData)
    {
        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\HttpQueryElement')
        {
            if (empty($componentData['properties']['ok'])) {
                $componentData['properties']['ok'] = [];
            }

            if (empty($componentData['properties']['nok'])) {
                $componentData['properties']['nok'] = [];
            }
        }

        return $componentData;
    }
}