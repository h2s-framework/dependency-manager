<?php

namespace Siarko\DependencyManager\Type;

use Siarko\Api\Factory\AbstractFactory;

class TypedTypeFactory extends AbstractFactory
{
    /**
     * @param array $data
     * @return TypedType
     */
    public function create(array $data = []): TypedType
    {
        return parent::_create(TypedType::class, $data);
    }
}