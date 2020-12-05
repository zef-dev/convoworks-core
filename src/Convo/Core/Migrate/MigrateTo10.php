<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo10 extends AbstractMigration
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
        return 10;
    }

    protected function _migrateComponent($componentData)
    {
        if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Processors\\YesNoProcessor') {
            if ( empty( $componentData['properties']['yes'])) {
                $componentData['properties']['yes']  =   [];
            } else {
                $componentData['properties']['yes']  =   $this->_getTrueChildren( $componentData['properties']['yes']);
            }
            if ( empty( $componentData['properties']['no'])) {
                $componentData['properties']['no']  =   [];
            } else {
                $componentData['properties']['no']  =   $this->_getTrueChildren( $componentData['properties']['no']);
            }
        }
        if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\RunOnceElement') {
            if ( empty( $componentData['properties']['yes'])) {
                $componentData['properties']['child']  =   [];
            } else {
                $componentData['properties']['child']  =   $this->_getTrueChildren( $componentData['properties']['child']);
            }
        }

        return $componentData;
    }
}