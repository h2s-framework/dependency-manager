<?php

namespace Siarko\DependencyManager\Generator\Developer\Factory;

use Siarko\Api\Factory\ObjectCreatorInterface;
use Siarko\DependencyManager\DependencyManager;

class ObjectCreator implements ObjectCreatorInterface
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
     * @param string $className
     * @param array $data
     * @return object|null
     */
    public function createObject(string $className, array $data = []): object
    {
        return $this->dependencyManager->create($className, $data);
    }
}