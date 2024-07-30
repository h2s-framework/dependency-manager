<?php

namespace Siarko\DependencyManager;

use Siarko\DependencyManager\Attributes\InjectField;
use Siarko\DependencyManager\Config\Runtime\Alias\AliasManager;
use Siarko\DependencyManager\Config\Runtime\Argument\ArgumentResolver;
use Siarko\DependencyManager\Exceptions\CircularDependencyException;
use Siarko\DependencyManager\Exceptions\ClassNotInstantiable;
use Siarko\DependencyManager\Exceptions\DmServiceException;
use Siarko\DependencyManager\Exceptions\ParameterNotConstructable;
use Siarko\DependencyManager\Type\TypedValue;
use Siarko\Utils\Code\ClassStructure;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Code\MethodStructure;
use Siarko\Utils\Code\Type\ArrayType;
use Siarko\Utils\Code\Type\TypeInterface;
use Siarko\Utils\Code\Type\UnionType;
use Siarko\Utils\Exceptions\TypeCastException;
use Siarko\Utils\TypeManager;

class DependencyManager
{

    /**
     * Contains className->Object
     * @var array
     */
    private array $objects = [];

    /**
     * Call stack array - every class that is constructed is put on the stack\
     * after top get/create execution is completed, stack if cleared
     * @var array
     */
    private array $callStack = [];

    /**
     * Objects that are created when tracking is enabled
     * @var array
     */
    protected array $trackedObjects = [];

    /**
     * Tracking enabled flag
     * @var bool
     */
    protected bool $trackingEnabled = false;

    /**
     * Constructor with initial objects required for basic configuration
     */
    public function __construct()
    {
        $this->nuke();
    }

    /**
     * Clear all stored objects and reset state
     * After this, internal services are cleared and need to be re-initialized
     *
     * @return void
     */
    public function nuke(): void
    {
        $this->objects = [];
        $this->callStack = [];
        $this->trackedObjects = [];
        $this->trackingEnabled = false;
        $this->bindObject(DependencyManager::class, $this);
    }

    /**
     * Store object or callable that returns object under typename key
     * @param string $typeName
     * @param callable|object $object
     * @return DependencyManager
     */
    public function bindObject(string $typeName, callable|object $object): DependencyManager
    {
        $this->_storeObject($typeName, $object);
        return $this;
    }

    /**
     * Create class-dedicated bind - object will be given to specific class constructor/field
     * overriding global binds/aliases
     * @param string $typeName
     * @param string $fieldName
     * @param object $object
     * @return DependencyManager
     * @throws \Exception
     */
    public function bindArgument(string $typeName, string $fieldName, mixed $object): static
    {
        $typeName = ltrim($typeName, '\\');
        $this->service(ArgumentResolver::class)->bindArgument($typeName, $fieldName, $object);
        return $this;
    }

    /**
     * Alias class name with another (interface/abstract -> implementation)
     * @param string $typeName
     * @param string $alias
     * @return DependencyManager
     * @throws \Exception
     */
    public function alias(string $typeName, string $alias): DependencyManager
    {
        $this->service(AliasManager::class)->set($typeName, $alias);
        return $this;
    }

    /**
     * Generate new instance or get stored if not existing
     * @param string $className
     * @return object|null
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    public function get(string $className): ?object
    {
        $className = ltrim($className, '\\');
        $className = $this->findAlias($className);
        if (!$this->_instanceExists($className)) {
            $this->_storeObject($className, $this->create($className));
        }
        return $this->_retrieveObject($className);
    }

    /**
     * Generate new instance of class
     * @param string $className
     * @param array $arguments Arguments passed to constructor
     * @return object|null
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     * @throws \Exception
     */
    public function create(string $className, array $arguments = []): ?object
    {
        $className = ltrim($className, '\\');
        $className = $this->findAlias($className);
        $reflection = $this->service(ClassStructureProvider::class)->get($className);
        if (!$reflection->isInstantiable()) {
            throw new ClassNotInstantiable("Class is not instantiable: " . $className, $this->getCallStack());
        }
        if ($this->isOnStack($className)) {
            throw new CircularDependencyException("Circular object dependency detected", $this->getCallStack());
        }
        $this->putOnStack($className);
        if ($reflection->hasCustomConstructor()) {
            $arguments = $this->resolveMethodArguments($reflection->getConstructor(), $arguments);
            $object = $reflection->createInstance($arguments);
        } else {
            $object = $reflection->createInstance();
        }
        $this->injectFields($reflection, $object);

        $this->popStack();
        return $object;
    }

    /**
     * Start tracking object that are created
     */
    public function startObjectTracking(): void
    {
        $this->trackingEnabled = true;
        $this->trackedObjects = [];
    }

    /**
     * Stop tracking created objects and return list
     * @return array
     */
    public function stopObjectTracking(): array
    {
        $this->trackingEnabled = false;
        return array_unique($this->trackedObjects);
    }

    /**
     * Flush stored instances
     * @param array $typeNames
     */
    public function flushInstances(array $typeNames): void
    {
        $typeNames = array_unique($typeNames);
        foreach ($typeNames as $typeName) {
            if (array_key_exists($typeName, $this->objects)) {
                unset($this->objects[$typeName]);
            }
        }
    }

    /**
     * @param string $className
     * @return string
     * @throws DmServiceException
     */
    public function findAlias(string $className): string
    {
        if ($className === AliasManager::class || !$this->_instanceExists(AliasManager::class)) {
            return $className;
        }
        return $this->service(AliasManager::class)->find($className);
    }

    /**
     * Return call stack
     *
     * @return array
     */
    public function getCallStack(): array
    {
        return array_reverse($this->callStack);
    }

    /**
     * Find and return service by type name
     *
     * @param string $typeName
     * @return object
     * @throws DmServiceException
     */
    private function service(string $typeName): object
    {
        try {
            return $this->get($typeName);
        }catch (\Exception $e){
            throw new DmServiceException(
                "DM internal exception for service ".$typeName. ": ".$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Check if instance of class is already stored
     * @param string $typeName
     * @return bool
     */
    private function _instanceExists(string $typeName): bool
    {
        return array_key_exists(ltrim($typeName, '\\'), $this->objects);
    }

    /**
     * Store object under typename key (can be callable that returns object)
     * @param string $typeName
     * @param $object callable|object
     */
    private function _storeObject(string $typeName, callable|object $object)
    {
        $this->objects[ltrim($typeName, '\\')] = $object;
    }

    /**
     * Retrieve object by typename - call if callable to get object
     * @param string $typeName
     * @return mixed
     */
    private function _retrieveObject(string $typeName)
    {
        $object = $this->objects[ltrim($typeName, '\\')];
        return is_callable($object) ? $object() : $object;
    }

    /**
     * As expected -> creates list of objects for argument
     * @param MethodStructure $method
     * @param array $readyArguments
     * @return array
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    private function resolveMethodArguments(MethodStructure $method, array $readyArguments = []): array
    {
        $arguments = $this->service(ArgumentResolver::class)->resolve($method, function (string $typeName) {
            return $this->get($typeName);
        }, $readyArguments);
        return $this->castParams($method, $arguments);
    }

    /**
     * @param MethodStructure $method
     * @param array $arguments
     * @return array
     * @throws DmServiceException
     * @throws TypeCastException
     */
    private function castParams(MethodStructure $method, array $arguments): array
    {
        $paramTypes = [];
        foreach ($method->getParameters() as $parameter) {
            $paramType = $parameter->getType();
            if ($paramType instanceof UnionType || $paramType === null) {
                continue;
            }
            $paramTypes[$parameter->getName()] = $paramType;
        }
        foreach ($arguments as $name => $value) {
            $arguments[$name] = $this->castParam($value, $paramTypes[$name] ?? null);
        }
        return $arguments;
    }

    /**
     * @param mixed $value
     * @param TypeInterface|null $type
     * @return mixed
     * @throws DmServiceException
     * @throws TypeCastException
     */
    private function castParam(mixed $value, ?TypeInterface $type): mixed
    {
        if ($value instanceof TypedValue) {
            $type = $value->getTypeName();
            $value = $value->getValue();
        }
        if (is_array($value)) {
            $result = [];
            $subtype = null;
            if ($type instanceof ArrayType) {
                $subtype = $type->getType();
            }
            foreach ($value as $key => $item) {
                $result[$key] = $this->castParam($item, $subtype);
            }
            return $result;
        }
        if (!$type) {
            return $value;
        }
        return $this->service(TypeManager::class)->cast($type, $value);
    }


    /**
     * Put class on call stack
     * @param string $className
     */
    private function putOnStack(string $className): void
    {
        $this->callStack[] = $className;
    }

    /**
     * Check if class is already on call stack -> for circular dependency check and dumps
     * @param string $className
     * @return bool
     */
    private function isOnStack(string $className): bool
    {
        return in_array($className, $this->callStack);
    }

    /**
     * Clear last element of call stack
     */
    private function popStack(): void
    {
        if ($this->trackingEnabled) {
            $this->trackedObjects[] = end($this->callStack);
        }
        array_pop($this->callStack);
    }

    /**
     * Inject values for fields of create object
     * @param ClassStructure $reflection
     * @param object $object
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    private function injectFields(ClassStructure $reflection, object $object): void
    {
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(InjectField::class);
            if (!empty($attributes)) {
                $type = $property->getType();
                if ($type->isObjectType()) {
                    $property->getNativeReflection()->setValue($object, $this->get($type->getName()));
                }
            }
        }
    }
}