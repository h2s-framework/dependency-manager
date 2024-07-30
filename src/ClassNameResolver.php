<?php

namespace Siarko\DependencyManager;

use Siarko\DependencyManager\Exceptions\CouldNotResolveNamespace;

class ClassNameResolver
{

    private const PHP_CLASS_FILE_EXTENSIONS = ['.php', '.class.php', '.inc'];

    /**
     * Cached namespace -> path pairs
     * @var array
     */
    private array $paths = [];

    /**
     * Get class namespace from file path
     * Uses composer autoload data for figuring out what namespaces are defined
     * @throws CouldNotResolveNamespace
     */
    public function resolveFromFilePath(string $filePath): string{
        if(($namespace = $this->getNamespace($filePath)) == null){
            $pathNamespace = $this->findPathNamespace($filePath);
            if($pathNamespace == null){
                throw new CouldNotResolveNamespace($filePath);
            }
            $this->paths = array_merge($this->paths, $pathNamespace);
            $namespace = $this->getNamespace($filePath);
        }
        return $namespace;
    }

    /**
     * Get actual namespace from path
     * Finds cached namespace-path pair in cache and tries to match it with supplied path
     * Then some string operations to create correct namespace
     * @param string $path
     * @return string|null
     */
    private function getNamespace(string $path): ?string{
        $result = null;
        foreach ($this->paths as $p => $namespace) {
            if(str_starts_with($path, $p)){
                $result = str_replace($p, $namespace, $path);
            }
        }
        if($result != null){
            $result = str_replace(DIRECTORY_SEPARATOR, '\\', $result);
            $result = str_replace(self::PHP_CLASS_FILE_EXTENSIONS, '', $result);
        }
        return $result;
    }

    /**
     * Find for namespace definition in directory and parent directories
     * @param string $path
     * @return array|null
     */
    private function findPathNamespace(string $path): ?array{
        if(!is_dir($path)){
            $path = dirname($path);
        }
        if(!file_exists($this->getComposerPath($path))){
            return $this->findPathNamespace(dirname($path));
        }
        return $this->readComposerJson($this->getComposerPath($path));
    }

    /**
     * @param string $path
     * @return string
     */
    private function getComposerPath(string $path): string
    {
        return $path.DIRECTORY_SEPARATOR.'composer.json';
    }

    /**
     * Read json in directory and extract autoload data
     * @param string $path
     * @return array|null
     */
    private function readComposerJson(string $path): ?array{
        $data = json_decode(file_get_contents($path), true);
        if(!array_key_exists('autoload', $data)){
            return null;
        }
        //FIXME add psr-0 handling
        $data = $data['autoload']['psr-4'];
        $pathSuffix = str_replace('/', DIRECTORY_SEPARATOR, reset($data));
        return [dirname($path).DIRECTORY_SEPARATOR.$pathSuffix => key($data)];

    }
}