<?php

namespace Siarko\DependencyManager\Generator\Developer\Factory;

use Nette\PhpGenerator\ClassType;
use Siarko\Api\Factory\AbstractFactory;
use Siarko\DependencyManager\DependencyManager;
use Siarko\DependencyManager\Generator\Exception\BaseClassNotFound;
use Siarko\DependencyManager\Generator\IGenerator;

class FactoryGenerator implements IGenerator
{

    public const SUFFIX = 'Factory';


    /**
     * @param DependencyManager $dependencyManager
     */
    public function __construct(
        private readonly DependencyManager $dependencyManager
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
        $createMethod = $factoryClass->addMethod('create');
        $createMethod->setReturnType('\\'.$returnType);
        $createMethod->addParameter('data', [])->setType('array');
        $createMethod->setBody('return parent::_create(\\'.$baseClassName.'::class, $data);');
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
}