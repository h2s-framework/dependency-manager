<?php

namespace Siarko\DependencyManager\Config\Init;

/**
 * Class BootConfiguration
 * Contains the configuration for the dependency manager, created by the boot.php file
 */
class BootConfiguration
{

    private static array $config = [];

    /**
     * @param array $config
     * @return void
     */
    public static function register(array $config): void
    {
        self::$config = array_replace_recursive(self::$config, $config);
    }

    /**
     * Returns the configuration
     *
     * @return array
     */
    public static function getConfig(): array
    {
        return self::$config;
    }
}