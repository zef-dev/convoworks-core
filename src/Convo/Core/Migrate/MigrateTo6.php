<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo6 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 6;
    }
    
    protected function _migrateComponent( $componentData)
    {
        if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Processors\\ProcessorSubroutine') {
            if ( empty( $componentData['properties']['processor'])) {
                $componentData['properties']['processors']  =   [];
            } else {
                $componentData['properties']['processors']  =   [$componentData['properties']['processor']];
                unset( $componentData['properties']['processor']);
            }
        }
        else if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Processors\\SimpleProcessor') {
            if ( empty( $componentData['properties']['ok'])) {
                $componentData['properties']['ok']  =   [];
            } else {
                $componentData['properties']['ok']  =   $this->_getTrueChildren( $componentData['properties']['ok']);
            }
        }
        else if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\SimpleIfElement') {
            if ( empty( $componentData['properties']['then'])) {
                $componentData['properties']['then']  =   [];
            } else {
                $componentData['properties']['then']  =   $this->_getTrueChildren( $componentData['properties']['then']);
            }
            if ( empty( $componentData['properties']['else'])) {
                $componentData['properties']['else']  =   [];
            } else {
                $componentData['properties']['else']  =   $this->_getTrueChildren( $componentData['properties']['else']);
            }
        }
        else if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\SwitchCaseElement') {
            if ( empty( $componentData['properties']['then'])) {
                $componentData['properties']['then']  =   [];
            } else {
                $componentData['properties']['then']  =   $this->_getTrueChildren( $componentData['properties']['then']);
            }
        }
        else if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Processors\\SingleIntentCrossProcessor') {
            if ( empty( $componentData['properties']['ok'])) {
                $componentData['properties']['ok']  =   [];
            } else {
                $componentData['properties']['ok']  =   $this->_getTrueChildren( $componentData['properties']['ok']);
            }
        }
        
        return $componentData;
    }
    
}