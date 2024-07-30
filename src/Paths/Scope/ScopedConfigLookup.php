<?php

namespace Siarko\DependencyManager\Paths\Scope;

use Siarko\Files\FileFactory;
use Siarko\Files\Lookup\DirectoryLookup;
use Siarko\Paths\Provider\ProjectPathProvider;

class ScopedConfigLookup extends DirectoryLookup
{
    /**
     * @param ProjectPathProvider $pathProvider
     * @param FileFactory $fileFactory
     */
    public function __construct(
        ProjectPathProvider $pathProvider,
        FileFactory $fileFactory
    )
    {
        parent::__construct($pathProvider, $fileFactory);
    }

}