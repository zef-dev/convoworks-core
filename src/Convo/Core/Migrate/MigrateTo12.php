<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo12 extends AbstractMigration
{
    private $_rename = [
        "\\Convo\\Pckg\\Core\\ConvoIntentReader" => "\\Convo\\Pckg\\Core\\Filters\\ConvoIntentReader",
        "\\Convo\\Pckg\\Core\\CrossIntentReader" => "\\Convo\\Pckg\\Core\\Filters\\CrossIntentReader",
        "\\Convo\\Pckg\\Core\\CrossIntentRequestFilter" => 
        "\\Convo\\Pckg\\Core\\Filters\\CrossIntentRequestFilter"
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 12;
    }

    protected function _migrateComponent($componentData)
    {
        if (isset($this->_rename[$componentData['class']])) {
            $componentData['class'] = $this->_rename[$componentData['class']];
        }

        return $componentData;
    }
}