<?php

namespace Siarko\DependencyManager\Type;


use Siarko\Serialization\Api\Attribute\Serializable;

class TypedValue
{

    /**
     * @param string $typeName
     * @param mixed $value
     */
    public function __construct(
        #[Serializable] private readonly string $typeName = '',
        #[Serializable] private readonly mixed $value = null
    )
    {
    }

    /**
     * @return string
     */
    public function getTypeName(): string
    {
        return $this->typeName;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

}