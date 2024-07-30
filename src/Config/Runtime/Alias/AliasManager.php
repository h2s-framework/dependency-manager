<?php

namespace Siarko\DependencyManager\Config\Runtime\Alias;

class AliasManager
{

    private array $aliases = [];

    /**
     * @param AliasProviderInterface[] $aliasProviders
     */
    public function __construct(
        private readonly array $aliasProviders = []
    )
    {
    }

    /**
     * @param string $className
     * @param string $alias
     * @return void
     */
    public function set(string $className, string $alias): void
    {
        $this->aliases[ltrim($className, '\\')] = ltrim($alias, '\\');
    }

    /**
     * @param string $className
     * @return string
     */
    public function find(string $className): string
    {
        return $this->executeProviders($className, $this->recursiveFind($className));
    }

    /**
     * @param string $className
     * @return string
     */
    protected function recursiveFind(string $className): string
    {
        if (array_key_exists($className, $this->aliases)) {
            $alias = $this->aliases[$className];
            return $this->recursiveFind($alias);
        }
        return $className;
    }

    /**
     * @param string $className
     * @param string $result
     * @return string
     */
    protected function executeProviders(string $className, string $result): string
    {
        foreach ($this->aliasProviders as $aliasProvider) {
            $result = $aliasProvider->getAlias($className, $result);
        }
        return $result;
    }

}