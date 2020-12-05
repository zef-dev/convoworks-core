<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo4 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 4;
    }

    protected function _migrateComponent($componentData)
    {
        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\IfElement') {
            foreach ($componentData['properties']['cases'] as &$case) {
                $test_string = $case['properties']['test']['properties']['test'];
                
                $this->_logger->debug('Going to replace if test with simple string ['.$test_string.']');
                
                $case['properties']['test'] = $test_string;
            }
        }

        return $componentData;
    }
}