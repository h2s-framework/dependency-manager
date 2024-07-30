<?php

namespace Siarko\DependencyManager\Api\Config;

use Siarko\DependencyManager\DependencyManager;

interface ConfiguratorInterface
{

    /**
     * Method that will bootstrap dependency manager
     *
     * @param DependencyManager $instance
     * @param string $projectRoot
     * @return void
     */
    public function configure(DependencyManager $instance, string $projectRoot): void;

}