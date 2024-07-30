<?php

namespace Siarko\DependencyManager\Config\Init\Modifier\Builder;

use Siarko\DependencyManager\Config\DMKeys;
use Siarko\DependencyManager\Type\TypedValue;

class TypeInjector
{

    /**
     * @param array $config
     * @param string $typeName
     * @param string $typeDefinition
     * @param string|null $tag
     * @return array
     */
    public function injectType(array $config, string $typeName, string $typeDefinition, ?string $tag = null): array
    {
        if(!array_key_exists(DMKeys::TYPES, $config)) {
            $config[DMKeys::TYPES] = [];
        }
        if($tag){
            $typeDefinition = new TypedValue($tag, $typeDefinition);
        }
        $config[DMKeys::TYPES][$typeName] = $typeDefinition;
        return $config;
    }
}