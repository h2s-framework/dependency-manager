<?php

namespace Siarko\DependencyManager\Generator;

use Siarko\CacheFiles\Api\CacheSetInterface;
use Siarko\Files\Path\PathInfo;

class CodeCache implements CacheSetInterface
{

    public function __construct(
        protected readonly CodeGenerator $generator,
        protected readonly PathInfo $pathInfo
    )
    {
    }

    /**
     * Dummy implementations since they are not required for code generator
     * */
    public function exists(string $type): bool{return true;}

    public function get(string $type): array{return [];}

    public function set(string $type, array $data){}

    /**
     * Files will be deleted but in this session they are already loaded.
     * Script must be rerun to generate and load new files
     */
    public function clear(?string $type = null): void
    {
        $pathInfo = $this->pathInfo->read($this->generator->getGenerationPath()->getConstructedPath());
        $files = $pathInfo->readDirectoryFiles('/.+\.php$/');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}