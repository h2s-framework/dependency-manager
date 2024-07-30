<?php

namespace Siarko\DependencyManager\Type;

use Siarko\Api\Factory\AbstractFactory;

class TypedValueFactory extends AbstractFactory
{
    /**
     * @param array $data
     * @return TypedValue
     */
    public function create(array $data = []): TypedValue
    {
        return parent::_create(TypedValue::class, $data);
    }

}