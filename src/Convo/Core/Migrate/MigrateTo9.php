<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo9 extends AbstractMigration
{
    private $_rename = [
        "Convo\\Pckg\\Core\\Filters\\Filt\\StriposFilter" => "\\Convo\\Pckg\\Text\\Filters\\Filt\\StriposFilter",
        "\\Convo\\Pckg\\Core\\Filters\\Flt\\RegexFilter" => "\\Convo\\Pckg\\Text\\Filters\\Flt\\RegexFilter",
        "\\Convo\\Pckg\\Core\\Filters\\PlainTextRequestFilter" => 
        "\\Convo\\Pckg\\Text\\Filters\\PlainTextRequestFilter"
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 9;
    }

    protected function _migrateComponent($componentData)
    {
        if (isset($this->_rename[$componentData['class']])) {
            $componentData['class'] = $this->_rename[$componentData['class']];
        }

        return $componentData;
    }
}