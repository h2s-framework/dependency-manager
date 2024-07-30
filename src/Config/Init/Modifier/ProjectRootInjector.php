<?php

namespace Siarko\DependencyManager\Config\Init\Modifier;

use Siarko\ConfigFiles\Api\Modifier\ModifierInterface;
use Siarko\ConfigFiles\Api\Modifier\ModifierManagerInterface;
use Siarko\DependencyManager\Config\Init\Modifier\Builder\VariableInjector;
use Siarko\Files\Api\FileInterface;
use Siarko\Paths\Exception\RootPathNotSet;
use Siarko\Paths\RootPath;

class ProjectRootInjector implements ModifierInterface
{

    protected const VAR_NAME = '__PROJECT_ROOT__';

    /**
     * @param RootPath $rootPath
     * @param VariableInjector $variableInjector
     */
    public function __construct(
        private readonly RootPath $rootPath,
        private readonly VariableInjector $variableInjector
    )
    {
    }

    /**
     * @return array|\string[][]
     */
    public function getDependencyOrder(): array
    {
        return [];
    }

    /**
     * @param ModifierManagerInterface $manager
     * @param FileInterface $file
     * @param array $config
     * @return array
     * @throws RootPathNotSet
     */
    public function apply(ModifierManagerInterface $manager, FileInterface $file, array $config): array
    {
        $this->variableInjector->injectVariable($config, self::VAR_NAME, $this->rootPath->get());
        return $config;
    }
}