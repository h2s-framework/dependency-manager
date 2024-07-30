<?php

namespace Siarko\DependencyManager\Config\Runtime\Argument;

use Siarko\DependencyManager\Exceptions\ParameterNotConstructable;
use Siarko\Utils\Code\ClassStructure;
use Siarko\Utils\Code\MethodParameterStructure;
use Siarko\Utils\Code\MethodStructure;
use Siarko\Utils\Code\Type\SimpleType;

class ArgumentResolver
{

    /**
     * Contains bound objects for specific classes arguments
     * @var array
     */
    private array $boundArguments = [];

    /**
     * @param ArgumentResolverInterface[] $customResolvers
     */
    public function __construct(
        private readonly array $customResolvers = []
    )
    {
    }

    /**
     * Resolves arguments for method using ObjectProvider when required
     * @param MethodStructure $method
     * @param callable $objectProvider receives string $typeName and returns value object
     * @param array $readyArguments arguments that are already resolved/provided
     * @return array
     * @throws ParameterNotConstructable
     * @throws \ReflectionException
     */
    public function resolve(MethodStructure $method, callable $objectProvider, array $readyArguments = []): array
    {
        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), $readyArguments)) {
                $arguments[$parameter->getName()] = $readyArguments[$parameter->getName()];
                continue;
            }
            $arguments[$parameter->getName()] = $this->resolveArgument($method, $objectProvider, $parameter);
        }
        return $arguments;
    }

    /**
     * Resolve single argument
     * @param MethodStructure $method
     * @param callable $objectProvider
     * @param MethodParameterStructure $parameter
     * @return mixed
     * @throws ParameterNotConstructable
     * @throws \ReflectionException
     */
    protected function resolveArgument(MethodStructure $method, callable $objectProvider, MethodParameterStructure $parameter): mixed
    {
        $type = $parameter->getType();
        $classStructure = $this->getClassesStructure($method->getDeclaringClass());
        if ($this->hasBoundValue($classStructure, $parameter->getName())) {
            $value = $this->getDedicatedBoundValue($classStructure, $parameter->getName());
        } else {
            if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } elseif ($type instanceof SimpleType && $type->isObjectType()) {
                $value = $objectProvider($type->getName());
            } else {
                throw new ParameterNotConstructable("Could not construct parameter " . $parameter->getName());
            }
        }
        return $value;
    }

    /**
     * @param ClassStructure $reflectionClass
     * @return array
     */
    protected function getClassesStructure(ClassStructure $reflectionClass): array
    {
        $result = [$reflectionClass->getName()];
        array_push($result, ...$reflectionClass->getParentClasses());
        return $result;
    }

    /**
     * @param array $classNames
     * @param string $paramName
     * @return bool
     */
    private function hasBoundValue(array $classNames, string $paramName): bool
    {
        foreach ($classNames as $className) {
            if (array_key_exists($className, $this->boundArguments) &&
                array_key_exists($paramName, $this->boundArguments[$className])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $classNames
     * @param string $paramName
     * @return mixed
     */
    private function getDedicatedBoundValue(array $classNames, string $paramName): mixed
    {
        if (count(array_unique($classNames)) == 1) {
            return $this->boundArguments[$classNames[0]][$paramName];
        } else {
            $result = null;
            foreach (array_reverse($classNames) as $className) {
                if (!$this->hasBoundArguments($className)) {
                    continue;
                }
                if (array_key_exists($paramName, $this->getBoundArguments($className))) {
                    $result = $this->getBoundArguments($className)[$paramName];
                }
            }
            return $result;
        }
    }

    /**
     * @param string $typeName
     * @return bool
     */
    public function hasBoundArguments(string $typeName): bool
    {
        return array_key_exists(ltrim($typeName, '\\'), $this->boundArguments);
    }

    /**
     * @param string $typeName
     * @return mixed
     */
    public function getBoundArguments(string $typeName): mixed
    {
        return $this->boundArguments[ltrim($typeName, '\\')];
    }

    /**
     * @param string $typeName
     * @param string $argumentName
     * @param mixed $value
     * @return void
     */
    public function bindArgument(string $typeName, string $argumentName, mixed $value): void
    {
        if (!$this->hasBoundArguments($typeName)) {
            $this->boundArguments[$typeName] = [];
        }
        $this->boundArguments[$typeName][$argumentName] = $value;
    }

}