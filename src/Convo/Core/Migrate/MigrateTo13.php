<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo13 extends AbstractMigration
{
    private $_rename = [
        "\\Convo\\Pckg\\Core\\Elements\\ElementsSubroutine" => "\\Convo\\Pckg\\Core\\Elements\\ElementsFragment",
        "\\Convo\\Pckg\\Core\\Elements\\SetStateElement" => "\\Convo\\Pckg\\Core\\Elements\\GoToElement",
        "\\Convo\\Pckg\\Core\\Elements\\SimpleIfElement" => "\\Convo\\Pckg\\Core\\Elements\\IfElement",
        "\\Convo\\Pckg\\Core\\Elements\\ReadElementsSubroutine" => "\\Convo\\Pckg\\Core\\Elements\\ReadElementsFragment",
        "\\Convo\\Pckg\\Core\\Elements\\SimpleTextResponse" => "\\Convo\\Pckg\\Core\\Elements\\TextResponseElement",
        "\\Convo\\Pckg\\Core\\Filters\\CrossIntentRequestFilter" => "\\Convo\\Pckg\\Core\\Filters\\IntentRequestFilter",
        "\\Convo\\Pckg\\Core\\Filters\\CrossIntentReader" => "\\Convo\\Pckg\\Core\\Filters\\PlatformIntentReader",
        "\\Convo\\Pckg\\Core\\Processors\\ProcessorSubroutine" => "\\Convo\\Pckg\\Core\\Processors\\ProcessorFragment",
        "\\Convo\\Pckg\\Core\\Processors\\ProcessProcessorSubroutine" => "\\Convo\\Pckg\\Core\\Processors\\ProcessProcessorFragment"
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 13;
    }


    protected function _migrateService( $serviceData)
    {
        $serviceData = parent::_migrateService($serviceData);

        if (isset( $serviceData['subroutines'])) {
            $this->_logger->debug( 'Renaming subroutines array to fragments array');
            $serviceData['fragments'] = $serviceData['subroutines'];
            unset($serviceData['subroutines']);
        }

        foreach ($serviceData['blocks'] as &$block) {
            if (isset($block['properties']['default'])) {
                $block['properties']['fallback'] = $block['properties']['default'];
                unset($block['properties']['default']);
            }
        }

        return $serviceData;
    }

    protected function _migrateComponent($componentData)
    {
        // property migration phase
        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\ElementsSubroutine') {
            $componentData['properties']['fragment_id'] = $componentData['properties']['subroutine_id'];
            unset($componentData['properties']['subroutine_id']);
        }

        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\ReadElementsSubroutine') {
            $componentData['properties']['fragment_id'] = $componentData['properties']['subroutine_id'];
            unset($componentData['properties']['subroutine_id']);
        }

        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Processors\\ProcessProcessorSubroutine') {
            $componentData['properties']['fragment_id'] = $componentData['properties']['subroutine_id'];
            unset($componentData['properties']['subroutine_id']);
        }

        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Processors\\ProcessorSubroutine') {
            $componentData['properties']['fragment_id'] = $componentData['properties']['subroutine_id'];
            unset($componentData['properties']['subroutine_id']);
        }

        // rename classes phase
        if (isset($this->_rename[$componentData['class']])) {
            $componentData['class'] = $this->_rename[$componentData['class']];
        }

        return $componentData;
    }
}