<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo18 extends AbstractMigration
{
    private $_target = 'properties';
    private $_targetProperties = ['_interface', '_preview_angular', '_workflow', '_system'];

    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 18;
    }

    protected function _migrateComponent($componentData)
    {
        if (isset($componentData[$this->_target])) {
            foreach ($this->_targetProperties as $targetProp) {
                if (isset($componentData[$this->_target][$targetProp])) {
                    unset($componentData[$this->_target][$targetProp]);
                }
            }
        }

        return $componentData;
    }
}
