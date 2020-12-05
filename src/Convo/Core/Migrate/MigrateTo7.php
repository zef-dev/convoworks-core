<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo7 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 7;
    }

    protected function _migrateComponent($componentData)
    {
        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\SimpleIfElement')
        {
            if (empty($componentData['properties']['else_if'])) {
                $componentData['properties']['else_if'] = [];
            }
        }

        return $componentData;
    }
}