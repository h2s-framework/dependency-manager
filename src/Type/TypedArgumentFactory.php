<?php

namespace Siarko\DependencyManager\Type;

use Siarko\Api\Factory\AbstractFactory;

class TypedArgumentFactory extends AbstractFactory
{
    /**
     * @param array $data
     * @return TypedArgument
     */
    public function create(array $data = []): TypedArgument
    {
        return parent::_create(TypedArgument::class, $data);
    }
}