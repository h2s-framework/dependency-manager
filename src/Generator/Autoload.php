<?php

namespace Siarko\DependencyManager\Generator;

class Autoload
{

    /**
     * Contains classes that are done generating
     * @var array
     */
    private array $generated = [];

    /**
     * Register class as ready for autoload
     * @param string $className
     * @param string $path
     */
    public function registerClassFile(string $className, string $path): void
    {
        $this->generated[$className] = $path;
    }

    /**
     * Custom autoloader for dynamically generated files
     * @throws \Exception
     */
    public function register(CodeGenerator $generator): void {
        $message = "Unknown error";
        $result = false;
        try{
            $result = spl_autoload_register($this->getAutoloader($generator), true, false);
        }catch (\Exception $exception){
            $message = $exception->getMessage();
        }
        if(!$result){
            throw new \Exception("Error happened during generated code autoload register: ".$message);
        }
    }

    /**
     * @param CodeGenerator $generator
     * @return callable
     */
    public function getAutoloader(CodeGenerator $generator): callable{
        return function(string $className) use ($generator) {
            if(array_key_exists($className, $this->generated)){
                include_once $this->generated[$className];
            }else{
                if($generator->generateClassByName($className)){
                    include_once $this->generated[$className];
                }
            }
        };
    }
}