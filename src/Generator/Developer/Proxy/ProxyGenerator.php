<?php

namespace Siarko\DependencyManager\Generator\Developer\Proxy;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Siarko\Api\Factory\ObjectCreatorInterface;
use Siarko\DependencyManager\Generator\IGenerator;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Code\MethodStructure;

/**
 * Class ProxyGenerator
 * Dynamic proxy generator
 */
class ProxyGenerator implements IGenerator
{

    public const SUFFIX = '\\Proxy';
    const CONSTRUCTOR_NAME = '__construct';

    private const FIELD_TYPENAME = '__PROXY_TARGET_TYPE_NAME';
    private const FIELD_INSTANCE = '__proxyInstance';
    private const FIELD_OBJECT_CREATOR = '__proxyObjectCreator';

    private const METHOD_PROXY = '__proxyGetSubject';

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
        $this->generateClass($class, $baseClassName);
        return $file;
    }

    /**
     * @param $class
     * @param $baseClassName
     * @return void
     * @throws \ReflectionException
     */
    private function generateClass(ClassType $class, $baseClassName): void
    {
        $class->setExtends($baseClassName);
        $structure = $this->classStructureProvider->get($baseClassName);
        $this->storeBaseTypeName($class, $baseClassName);
        $this->createClassFields($class, $baseClassName);
        $this->generateProxyMethod($class, $structure);
        foreach ($structure->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() == self::CONSTRUCTOR_NAME) {
                $this->generateConstructor($class, $method);
            }else{
                $this->generateMethod($class, $method);
            }
        }
    }

    private function generateConstructor(ClassType $class, MethodStructure $method): void
    {
        $newMethod = $class->addMethod($method->getName());
        $newMethod->setPublic();
        $newMethod->setComment($this->getDocBlock($method));

        $objectCreatorParam = $newMethod->addParameter(self::FIELD_OBJECT_CREATOR);
        $objectCreatorParam->setType(ObjectCreatorInterface::class);

        $parentCall = '$this->'.self::FIELD_OBJECT_CREATOR.' = $'.self::FIELD_OBJECT_CREATOR.';';
        $newMethod->setBody($parentCall);
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

        $pluginExecutionCode = ($returnsData ? 'return ' : '') . '$this->'.self::METHOD_PROXY.'()->' . $method->getName() . '(' . $params . ');';
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
        $newParam->setNullable($parameter->allowsNull());
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
        foreach ($docBlock?->children ?? [] as $child) {
            $result .= $child . "\n";
        }
        return $result;
    }

    private function generateProxyMethod(ClassType $class, \Siarko\Utils\Code\ClassStructure $structure): void
    {
        $proxyMethodBody = 'if( !$this->'.self::FIELD_INSTANCE." ){\n\t\$this->".
            self::FIELD_INSTANCE.' = $this->'.self::FIELD_OBJECT_CREATOR. "->createObject(self::" .
            self::FIELD_TYPENAME. ");\n}\n";
        $proxyMethodBody .= 'return $this->'.self::FIELD_INSTANCE.';';
        $method = $class->addMethod(self::METHOD_PROXY);
        $method->setPrivate();
        $method->setReturnType($structure->getName());
        $method->setBody($proxyMethodBody);
        $method->addComment('Returns instance of the proxied object');
    }

    /**
     * @param ClassType $class
     * @param string $baseClassName
     * @return void
     */
    private function storeBaseTypeName(ClassType $class, string $baseClassName): void
    {
        $class->addConstant(self::FIELD_TYPENAME, $baseClassName)
            ->addComment('Stores base class name for this proxy');
    }

    private function createClassFields(ClassType $class, string $baseClassName): void
    {
        $class->addProperty(self::FIELD_OBJECT_CREATOR)
            ->setPrivate()
            ->setType(ObjectCreatorInterface::class)
            ->addComment('Stores object creator for this proxy');
        $class->addProperty(self::FIELD_INSTANCE)
            ->setPrivate()
            ->setType($baseClassName)
            ->setValue(null)
            ->addComment('Stores instance of the proxied object');
    }

}