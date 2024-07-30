<?php

namespace Siarko\DependencyManager\Config\Runtime\Alias;

interface AliasProviderInterface
{

    public function getAlias(string $className, string $foundName): string;

}