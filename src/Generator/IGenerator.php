<?php

namespace Siarko\DependencyManager\Generator;

interface IGenerator
{
    function canGenerate(string $className): bool;

    function generate(string $fullClassName): string;

}