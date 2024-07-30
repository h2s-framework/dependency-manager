<?php

namespace Siarko\DependencyManager\Config\Init\Modifier;

use Siarko\ConfigFiles\Api\Modifier\ModifierInterface;
use Siarko\ConfigFiles\Api\Modifier\ModifierManagerInterface;
use Siarko\DependencyManager\Config\DMKeys;
use Siarko\DependencyManager\Type\TypedType;
use Siarko\DependencyManager\Type\TypedValue;
use Siarko\Files\Api\FileInterface;

class TypeNewTagTransformer implements ModifierInterface
{

    /**
     * Transform !tag used in yaml (Symfony TaggedValue doesn't like serialization)
     * @param ModifierManagerInterface $manager
     * @param FileInterface $file
     * @param array $config
     * @return array
     */
    public function apply(ModifierManagerInterface $manager, FileInterface $file, array $config): array
    {
        if(array_key_exists(DMKeys::TYPES, $config)){
            $config[DMKeys::TYPES] = array_map(function ($type){
                return (($type instanceof TypedValue) ? new TypedType($type->getTypeName(), $type->getValue()) : $type);
            }, $config[DMKeys::TYPES]);
        }
        return $config;
    }

    /**
     * @return array
     */
    public function getDependencyOrder(): array
    {
        return [];
    }
}