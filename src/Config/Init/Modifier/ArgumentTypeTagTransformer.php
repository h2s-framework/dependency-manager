<?php

namespace Siarko\DependencyManager\Config\Init\Modifier;

use Siarko\ConfigFiles\Api\Modifier\ModifierInterface;
use Siarko\ConfigFiles\Api\Modifier\ModifierManagerInterface;
use Siarko\ConfigFiles\Modifier\ModifierManager;
use Siarko\DependencyManager\Config\DMKeys;
use Siarko\DependencyManager\Type\TypedArgument;
use Siarko\DependencyManager\Type\TypedValue;
use Siarko\Files\Api\FileInterface;

class ArgumentTypeTagTransformer implements ModifierInterface
{

    /**
     * @param ModifierManager $manager
     * @param FileInterface $file
     * @param array $config
     * @return array
     */
    public function apply(ModifierManagerInterface $manager, FileInterface $file, array $config): array
    {
        if(array_key_exists(DMKeys::ARGUMENTS, $config)) {
            $config[DMKeys::ARGUMENTS] = $this->transformArguments($config[DMKeys::ARGUMENTS]);
        }
        return $config;
    }

    /**
     * @param mixed $argument
     * @return mixed
     */
    private function transformArguments(mixed $argument): mixed
    {
        if(is_array($argument)){
            $result = [];
            foreach ($argument as $index => $value) {
                $result[$index] = $this->transformArguments($value);
            }
            return $result;
        }elseif($this->isTypeTag($argument)){
            return new TypedArgument($this->getTagType($argument), $argument->getValue());
        }
        return $argument;
    }

    /**
     * @param TypedValue $value
     * @return string
     */
    private function getTagType(TypedValue $value): string
    {
        return substr($value->getTypeName(), strlen(TypedArgument::TAG_TYPE)+1);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function isTypeTag(mixed $value): bool
    {
        return $value instanceof TypedValue && str_starts_with($value->getTypeName(), TypedArgument::TAG_TYPE);
    }

    /**
     * @return array
     */
    public function getDependencyOrder(): array
    {
        return [];
    }
}