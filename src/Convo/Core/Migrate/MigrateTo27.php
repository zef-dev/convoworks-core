<?php


namespace Convo\Core\Migrate;


class MigrateTo27 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 27;
    }

    protected function _migrateComponent( $componentData) {
        if ( $componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\MediaBlock') {
            if ( !isset( $componentData['properties']['additional_processors'])) {
                $componentData['properties']['additional_processors']  =   [];
            }
        }

        return $componentData;
    }
}
