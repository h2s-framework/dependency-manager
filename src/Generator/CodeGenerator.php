<?php

namespace Siarko\DependencyManager\Generator;

use Siarko\Paths\Provider\AbstractPathProvider;
use Siarko\Files\Path\PathInfo;

class CodeGenerator
{

    /**
     * @param AbstractPathProvider $rootPathProvider
     * @param PathInfo $pathInfo
     * @param Autoload $autoload
     * @param IGenerator[] $generators
     * @throws \Exception
     */
    public function __construct(
        private readonly AbstractPathProvider $rootPathProvider,
        private readonly PathInfo $pathInfo,
        private readonly Autoload $autoload,
        private array $generators = []
    )
    {
        $this->autoload->register($this);
    }

    /**
     * @param IGenerator $generator
     */
    public function registerGenerator(IGenerator $generator){
        $this->generators[] = $generator;
    }

    /**
     * Dynamically generate class if possible (e.g. Factories)
     * @param string $className
     * @return bool
     */
    public function generateClassByName(string $className): bool
    {
        if(class_exists($className)){
            return true;
        }
        if(!str_contains($className, '\\')) {
            return false;
        }
        $targetPath = $this->getFilePath($className);
        if(file_exists($targetPath)){
            $this->autoload->registerClassFile($className, $targetPath);
            return true;
        }
        foreach ($this->generators as $generator) {
            if($generator->canGenerate($className)){
                $classCode = $generator->generate($className);
                $path = $this->generateFile($classCode, $targetPath);
                $this->autoload->registerClassFile($className, $path->getFullPath());
                return true;
            }
        }
        return false;
    }

    /**
     * Generate new file and place class code inside
     * @param string $classCode
     * @param string $targetFilePath
     * @return PathInfo
     */
    private function generateFile(string $classCode, string $targetFilePath): PathInfo{
        $pathinfo = $this->pathInfo->read($targetFilePath);
        if(!is_dir($pathinfo->getDirname())){
            mkdir($pathinfo->getDirname(), 0755, true);
        }
        file_put_contents($pathinfo->getFullPath(), $classCode);
        return $pathinfo;
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getFilePath(string $className): string
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        return $this->rootPathProvider->getConstructedPath(DIRECTORY_SEPARATOR.$path.'.php');
    }

    /**
     * @return AbstractPathProvider
     */
    public function getGenerationPath(): AbstractPathProvider
    {
        return $this->rootPathProvider;
    }

}