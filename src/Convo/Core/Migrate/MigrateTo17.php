<?php declare(strict_types=1);

namespace Convo\Core\Migrate;

class MigrateTo17 extends AbstractMigration
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getVersion()
	{
		return 17;
	}

    protected function _migrateComponent($componentData)
    {
        // property migration phase
        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\CardElement') {
            if (!isset($componentData['properties']['_help'])) {
                $componentData['properties']['_help'] = array(
                    'type' => 'file',
                    'filename' => 'card-element.html'
                );
            }
        }

        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\ListElement') {
            if (!isset($componentData['properties']['_help'])) {
                $componentData['properties']['_help'] = array(
                    'type' => 'file',
                    'filename' => 'list-element.html'
                );
            }
        }

        if ($componentData['class'] === '\\Convo\\Pckg\\Core\\Elements\\HttpQueryElement') {
            if (!isset($componentData['properties']['_help'])) {
                $componentData['properties']['_help'] = array(
                    'type' => 'html',
                    'template' => '<div><b>Example usage after element execution: ${resultName.body.dataFromBody}</b></div>'
                );
            }
        }

        return $componentData;
    }
}
