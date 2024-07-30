<?php

namespace Siarko\DependencyManager\Generator\Developer\Proxy;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Siarko\DependencyManager\Generator\IGenerator;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Code\MethodStructure;

/**
 * Class ProxyGenerator
 * Dynamic proxy generator
 */
class ProxyGenerator implements IGenerator
{

    public const SUFFIX = 'Proxy';
    const CONSTRUCTOR_NAME = '__construct';

    /**
     * @param ClassStructureProvider $classStructureProvider
     */
    public function __construct(
        private readonly ClassStructureProvider $classStructureProvider,
    )
    {
    }

    /**
     * @param string $className
     * @return bool
     */
    function canGenerate(string $className): bool
    {
        return str_ends_with($className, self::SUFFIX);
    }

    /**
     * @param string $fullClassName
     * @return string
     * @throws \ReflectionException
     */
    function generate(string $fullClassName): string
    {
        $baseClassName = substr($fullClassName, 0, -strlen(self::SUFFIX));
        $file = new PhpFile();
        $class = $file->addClass($fullClassName);
        $class->setExtends(AbstractProxy::class);
        $this->generateClass($class, $baseClassName);
        return $file;
    }

    /**
     * @param $class
     * @param $baseClassName
     * @return void
     * @throws \ReflectionException
     */
    private function generateClass($class, $baseClassName): void
    {
        $structure = $this->classStructureProvider->get($baseClassName);
        $this->storeBaseTypeName($class, $baseClassName);
        foreach ($structure->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() == self::CONSTRUCTOR_NAME) {
                continue;
            }
            $this->generateMethod($class, $method);
        }
    }

    /**
     * @param ClassType $class
     * @param MethodStructure $method
     * @return void
     */
    private function generateMethod(ClassType $class, \Siarko\Utils\Code\MethodStructure $method): void
    {
        $newMethod = $this->generateMethodStructure($class, $method);
        $params = array_map(fn($param) => '$' . $param->getName(), $method->getNativeReflection()->getParameters());
        $params = implode(',', $params);

        $returnType = $method->getNativeReflection()->getReturnType();
        $returnsData = ($returnType && $returnType->getName() !== 'void');

        $pluginExecutionCode = ($returnsData ? 'return ' : '') .
            '$this->__getSubject()->' . $method->getName() . '(' . $params . ');';
        $newMethod->setBody($pluginExecutionCode);
    }

    /**
     * @param ClassType $class
     * @param MethodStructure $method
     * @return Method
     */
    private function generateMethodStructure(ClassType $class, MethodStructure $method): Method
    {
        $methodReflection = $method->getNativeReflection();
        $newMethod = $class->addMethod($method->getName());
        $newMethod->setPublic();
        if (($returnType = $methodReflection->getReturnType())) {
            $newMethod->setReturnType($returnType->getName());
        }
        foreach ($methodReflection->getParameters() as $parameter) {
            $this->generateParam($newMethod, $parameter);
        }
        $newMethod->setComment($this->getDocBlock($method));
        return $newMethod;
    }

    /**
     * @param Method $newMethod
     * @param \ReflectionParameter $parameter
     * @return void
     */
    private function generateParam(Method $newMethod, \ReflectionParameter $parameter): void
    {
        $newParam = $newMethod->addParameter($parameter->getName());
        if ($parameter->isDefaultValueAvailable()) {
            $newParam->setDefaultValue($parameter->getDefaultValue());
        }
        $paramType = $parameter->getType();
        if ($paramType) {
            $newParam->setType($paramType->getName());
        }
    }

    /**
     * @param MethodStructure $method
     * @return string
     */
    private function getDocBlock(MethodStructure $method): string
    {
        $docBlock = $method->getDocBlock();
        $result = '';
        foreach ($docBlock->children as $child) {
            $result .= $child . "\n";
        }
        return $result;
    }

    /**
     * @param ClassType $class
     * @param string $baseClassName
     * @return void
     */
    private function storeBaseTypeName(ClassType $class, string $baseClassName): void
    {
        $property = $class->addProperty(AbstractProxy::TYPENAME_PROPERTY);
        $property->setProtected();
        $property->setStatic();
        $property->setType('string');
        $property->setValue($baseClassName);
    }
}