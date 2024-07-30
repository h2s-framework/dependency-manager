<?php

namespace Siarko\DependencyManager\Config\Init\Modifier\Builder;

use Siarko\DependencyManager\Config\DMKeys;

class ArgumentInjector
{

    /**
     * @param array $config
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @return array
     */
    public function injectArgument(array $config, string $type, string $name, mixed $value): array
    {
        if(!array_key_exists(DMKeys::ARGUMENTS, $config)){
            $config[DMKeys::ARGUMENTS] = [];
        }
        if(!array_key_exists($type, $config[DMKeys::ARGUMENTS])){
            $config[DMKeys::ARGUMENTS][$type] = [];
        }
        if(is_array($config[DMKeys::ARGUMENTS][$type][$name] ?? '')){
            $config[DMKeys::ARGUMENTS][$type][$name] = array_replace_recursive(
                $config[DMKeys::ARGUMENTS][$type][$name],
                $value
            );
        }else{
            $config[DMKeys::ARGUMENTS][$type][$name] = $value;
        }

        return $config;
    }

    /**
     * @param array $config
     * @param string $type
     * @param array $arguments
     * @return array
     */
    public function injectArgumentArray(array $config, string $type, array $arguments): array
    {
        foreach ($arguments as $argName => $argValue) {
            $config = $this->injectArgument($config, $type, $argName, $argValue);
        }
        return $config;
    }
}