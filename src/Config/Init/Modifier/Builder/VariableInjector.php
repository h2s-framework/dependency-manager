<?php

namespace Siarko\DependencyManager\Config\Init\Modifier\Builder;

class VariableInjector
{

    /**
     * Used for injecting variables into config
     * @param array $config
     * @param string $varName
     * @param string $value
     */
    public function injectVariable(array &$config, string $varName, string $value): void
    {
        foreach ($config as $key => $val) {
            if(is_string($val)){
                $config[$key] = str_replace($varName, $value, $val);
            }
            if(is_array($val)){
                $this->injectVariable($config[$key], $varName, $value);
            }
        }
    }
}