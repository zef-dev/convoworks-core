<?php declare(strict_types=1);

namespace Convo\Core\Factory;

interface IComponentProvider
{
    /**
     * Returns component definition.
     *
     * @param string $class
     * @return \Convo\Core\Factory\ComponentDefinition
     * @throws \Convo\Core\ComponentNotFoundException
     */
    public function getComponentDefinition($class);

    /**
     * @param \Convo\Core\ConvoServiceInstance $service
     * @param \Convo\Core\Factory\PackageProvider $packageProvider
     * @param array $componentData
     * @return \Convo\Core\Workflow\IBasicServiceComponent
     * @throws \Convo\Core\ComponentNotFoundException
     *
     * Will create package component.
     */
    public function createPackageComponent(\Convo\Core\ConvoServiceInstance $service, \Convo\Core\Factory\PackageProvider $packageProvider, $componentData);


    /**
     * Gets the HTML help document for the given component
     *
     * @param string $componentName
     * @return string
     * @throws \Convo\Core\ComponentNotFoundException
     */
    public function getComponentHelp($componentName);
}
