<?php

namespace Siarko\DependencyManager\Config\Init\Apply;

use Siarko\DependencyManager\Config\DMKeys;
use Siarko\DependencyManager\Config\Runtime\Argument\ArgumentResolver;
use Siarko\DependencyManager\DependencyManager;
use Siarko\DependencyManager\Type\TypedType;

class Applicator
{

    /**
     * @param DependencyManager $dependencyManager
     * @param ArgumentResolver $argumentResolver
     */
    public function __construct(
        protected readonly DependencyManager $dependencyManager,
        protected readonly ArgumentResolver $argumentResolver
    )
    {
    }

    public function nukeAndApply(array $config): void
    {

    }

    /**
     * @param array $config
     * @return void
     */
    public function apply(array $config): void
    {
        //Bind arguments for types
        foreach ($config[DMKeys::ARGUMENTS] as $className => $argumentData) {
            foreach ($argumentData as $name => $data) {
                $this->dependencyManager->bindArgument($className, $name, $data);
            }
        }
        //register aliases
        foreach ($config[DMKeys::TYPES] as $typeName => $alias) {
            if(is_string($alias)){
                $this->dependencyManager->alias($typeName, $alias);
                unset($config[DMKeys::TYPES][$typeName]);
            }
        }
        //create new instances
        foreach ($config[DMKeys::TYPES] as $typeName => $instanceInfo) {
            if($instanceInfo instanceof TypedType){
                if($instanceInfo->getTypeName() == TypedType::TYPE_NEW){
                    $this->bindTypeNewInstance($instanceInfo->getValue(), $typeName);
                }
            }
        }
    }

    /**
     * Bind new Instance to type (using !new tag in dm config)
     * @param string $typeName Type of original class
     * @param string $instanceName New virtual class name
     * @return void
     */
    protected function bindTypeNewInstance(string $typeName, string $instanceName): void
    {
        $this->dependencyManager->bindObject(
            $this->dependencyManager->findAlias($instanceName),
            function() use ($typeName, $instanceName){
                $arguments = [];
                if($this->argumentResolver->hasBoundArguments($instanceName)){
                    $arguments = $this->argumentResolver->getBoundArguments($instanceName);
                }
                return $this->dependencyManager->create($typeName, $arguments);
            }
        );
    }

}