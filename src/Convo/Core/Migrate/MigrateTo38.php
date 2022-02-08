<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

use Convo\Core\Workflow\IRunnableBlock;

class MigrateTo38 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 38;
    }

    protected function _migrateComponent($componentData) {
        $block_id = $componentData['properties']['block_id'] ?? null;
        
        if ($block_id && !isset($componentData['properties']['role'])) {
            $componentData['properties']['role'] = IRunnableBlock::ROLE_CONVERSATION_BLOCK;
        }
        
        return $componentData;
    }
}
