<?php


namespace Convo\Core\Migrate;


use Convo\Core\Workflow\IRunnableBlock;

class MigrateTo25 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 25;
    }

    protected function _migrateComponent( $componentData) {
        $block_id   =   $componentData['properties']['block_id'] ?? null;
        if ( $block_id === '__sessionStart') {
            $componentData['properties']['role'] = IRunnableBlock::ROLE_SESSION_START;
            $componentData['properties']['name'] = 'Session start';
        } else if ( $block_id === '__sessionEnd') {
            $componentData['properties']['role'] = IRunnableBlock::ROLE_SESSION_ENDED;
            $componentData['properties']['name'] = 'Session ended';
        } else if ( $block_id === '__serviceProcessors') {
            $componentData['properties']['role'] = IRunnableBlock::ROLE_SERVICE_PROCESSORS;
            $componentData['properties']['name'] = 'Service processors';
        } else if ( $block_id === '__mediaControls') {
            $componentData['properties']['role'] = IRunnableBlock::ROLE_MEDIA_PLAYER;
            $componentData['properties']['name'] = 'Media player';
        }
        return $componentData;
    }
}
