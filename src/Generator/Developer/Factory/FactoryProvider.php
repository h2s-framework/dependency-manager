<?php

namespace Siarko\DependencyManager\Generator\Developer\Factory;

use Siarko\DependencyManager\DependencyManager;
use Siarko\Api\Factory\FactoryInterface;
use Siarko\Api\Factory\FactoryProviderInterface;

class FactoryProvider implements FactoryProviderInterface
{

    /**
     * @param DependencyManager $dependencyManager
     */
    public function __construct(
        private readonly DependencyManager $dependencyManager
    )
    {
    }

    /**
     * @param string $class
     * @return FactoryInterface
     */
    public function getFactory(string $class): FactoryInterface
    {
        return $this->dependencyManager->get($class.FactoryGenerator::SUFFIX);
    }
}