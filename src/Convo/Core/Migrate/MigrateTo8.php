<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo8 extends AbstractMigration
{
    private $_rename = [
        '\\Convo\\Pckg\\CrossIntentReader' => '\\Convo\\Pckg\\Core\\CrossIntentReader',
        '\\Convo\\Pckg\\CrossIntentRequestFilter' => '\\Convo\\Pckg\\Core\\CrossIntentRequestFilter',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 8;
    }

    protected function _migrateComponent($componentData)
    {
        if (isset($this->_rename[$componentData['class']])) {
            $componentData['class'] = $this->_rename[$componentData['class']];
        }

        return $componentData;
    }
}