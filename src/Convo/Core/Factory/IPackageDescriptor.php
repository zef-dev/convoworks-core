<?php declare(strict_types=1);

namespace Convo\Core\Factory;

/**
 * @author Tole
 * Package descriptor enables us to have all packages info without needing to instantiate them if not required.
 */
interface IPackageDescriptor
{
    
    /**
     * Returns package instance.
     * @return IPackageDefinition
     */
    public function getPackageInstance();

    /**
     * Returns package metainformation
     * @todo Add class const with meta array structure
     * @return array
     */
    public function getPackageMeta();
}
