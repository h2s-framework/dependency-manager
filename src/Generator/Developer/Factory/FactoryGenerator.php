<?php

namespace Siarko\DependencyManager\Generator\Developer\Factory;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Parameter;
use Siarko\Api\Factory\AbstractFactory;
use Siarko\Api\Factory\FactoryArgument;
use Siarko\DependencyManager\DependencyManager;
use Siarko\DependencyManager\Generator\Exception\BaseClassNotFound;
use Siarko\DependencyManager\Generator\IGenerator;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Code\Type\ArrayType;
use Siarko\Utils\Code\Type\SimpleType;
use Siarko\Utils\Code\Type\TypeInterface;
use Siarko\Utils\Code\Type\UnionType;

class FactoryGenerator implements IGenerator
{

    public const SUFFIX = 'Factory';


    /**
     * @param DependencyManager $dependencyManager
     * @param ClassStructureProvider $classStructureProvider
     */
    public function __construct(
        private readonly DependencyManager $dependencyManager,
        private readonly ClassStructureProvider $classStructureProvider
    ){
    }

    /**
     * Generate class file code and return as string
     * @param string $fullClassName
     * @return string
     * @throws BaseClassNotFound
     */
    function generate(string $fullClassName): string
    {
        $baseClassName = substr($fullClassName, 0, strpos($fullClassName, self::SUFFIX));
        $aliasedType = $this->dependencyManager->findAlias($baseClassName);
        if(!class_exists($aliasedType)){
            throw new BaseClassNotFound($fullClassName);
        }
        $classNameParts = explode('\\', $fullClassName);
        $classCode = $this->getClassCode(array_pop($classNameParts), $aliasedType, $baseClassName);
        $namespace = implode('\\', $classNameParts);
        return $this->getClassFileContents($namespace, $classCode);
    }

    /**
     * Get full class file content
     * @param string $namespace
     * @param string $classCode
     * @return string
     */
    private function getClassFileContents(string $namespace, string $classCode): string{
        $fileContents = "<?php\n\nnamespace ".$namespace.";\n\n";
        $fileContents .= $classCode;
        return $fileContents;
    }

    /**
     * Get only class code
     * @param string $className
     * @param string $baseClassName
     * @param string $returnType
     * @return string
     */
    private function getClassCode(string $className, string $baseClassName, string $returnType): string{
        $factoryClass = new ClassType($className);
        $factoryClass->setExtends('\\'.AbstractFactory::class);
        $this->generateCreateMethod($factoryClass, $baseClassName, $returnType);
        $this->generateCreateNamedMethod($factoryClass, $baseClassName, $returnType);
        return $factoryClass->__toString();
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
     * @param ClassType $factoryClass
     * @param string $baseClassName
     * @param string $returnType
     * @return void
     */
    private function generateCreateMethod(ClassType $factoryClass, string $baseClassName, string $returnType): void
    {
        $createMethod = $factoryClass->addMethod('create');
        $createMethod->setReturnType('\\'.$returnType);
        $createMethod->addParameter('data', [])->setType('array');
        $createMethod->setBody('return parent::_create(\\'.$baseClassName.'::class, $data);');
    }

    /**
     * @param ClassType $factoryClass
     * @param string $baseClassName
     * @param string $returnType
     * @return void
     */
    private function generateCreateNamedMethod(ClassType $factoryClass, string $baseClassName, string $returnType): void
    {
        $createNamedMethod = $factoryClass->addMethod('createNamed');
        $createNamedMethod->setReturnType('\\'.$returnType);
        foreach ($this->getConstructorParameters($baseClassName) as $index => $parameter) {
            $param = $createNamedMethod->addParameter($parameter->getName());
            $this->setParamType($param, $parameter->getType());
            if($parameter->isDefaultValueAvailable()){
                $param->setDefaultValue($parameter->getDefaultValue());
            }else{
                $param->setDefaultValue(FactoryArgument::NONE);
            }
        }
        $arguments = array_map(fn($p) => '"'.$p.'" => $'.$p, array_keys($createNamedMethod->getParameters()));
        $createNamedMethod->setBody('return parent::_createNamed(\\'.$baseClassName.'::class, ['.implode(',', $arguments).']);');
    }

    /**
     * @param string $baseClassName
     * @return array
     */
    private function getConstructorParameters(string $baseClassName): array{
        try{
            $structure = $this->classStructureProvider->get($baseClassName);
            $constructor = $structure->getConstructor();
            return $constructor?->getParameters() ?? [];
        }catch (\ReflectionException $e){
            return [];
        }
    }

    /**
     * @param Parameter $param
     * @param TypeInterface $type
     * @return void
     */
    private function setParamType(Parameter $param, TypeInterface $type): void
    {
        $param->setType($this->getTypeString($type).'|\\'.FactoryArgument::class);
        $param->setNullable($type->isNullable());
    }

    /**
     * @param TypeInterface $type
     * @return string
     */
    private function getTypeString(TypeInterface $type): string{
        if($type instanceof SimpleType){
            return ($type->isObjectType() ? '\\' : '').$type->getName();
        }
        if($type instanceof ArrayType){
            return 'array';
        }
        if($type instanceof UnionType){
            return implode('|', array_map(
                fn($t) => $this->getTypeString($t),
                $type->getTypes()
            ));
        }
        return 'mixed';
    }
}