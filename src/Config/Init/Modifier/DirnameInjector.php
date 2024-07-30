<?php

namespace Siarko\DependencyManager\Config\Init\Modifier;

use Siarko\ConfigFiles\Api\Modifier\ModifierInterface;
use Siarko\ConfigFiles\Api\Modifier\ModifierManagerInterface;
use Siarko\DependencyManager\Config\Init\Modifier\Builder\VariableInjector;
use Siarko\Files\Api\FileInterface;

class DirnameInjector implements ModifierInterface
{

    public const VAR_NAME = '__DIR__';


    /**
     * @param VariableInjector $variableInjector
     */
    public function __construct(
        private readonly VariableInjector $variableInjector
    )
    {
    }

    /**
     * @param ModifierManagerInterface $manager
     * @param FileInterface $file
     * @param array $config
     * @return array
     */
    public function apply(ModifierManagerInterface $manager, FileInterface $file, array $config): array
    {
        $this->variableInjector->injectVariable($config, self::VAR_NAME, dirname($file->getPath()));
        return $config;
    }

    /**
     * @return string[]
     */
    public function getDependencyOrder(): array
    {
        return [];
    }
}