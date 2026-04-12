<?php

namespace Siarko\DependencyManager\Events;

use Siarko\DependencyManager\DependencyManager;
use Siarko\Events\Api\EventInterface;

class DependencyManagerConfigured implements EventInterface
{

    public function __construct(
        private readonly DependencyManager $dependencyManager,
    )
    {
    }

    public function getDependencyManager(): DependencyManager
    {
        return $this->dependencyManager;
    }



}