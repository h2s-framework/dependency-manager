<?php

use Siarko\Api\Factory\FactoryProviderInterface;
use Siarko\Api\Factory\ObjectCreatorInterface;
use Siarko\CacheFiles\CacheSet;
use Siarko\ConfigCache\Provider\CachedConfigProvider;
use Siarko\ConfigFiles\Api\ConfigMergerInterface;
use Siarko\ConfigFiles\Api\ConfigPlacementStrategyInterface;
use Siarko\ConfigFiles\Api\Modifier\ModifierManagerInterface;
use Siarko\ConfigFiles\Api\PrioritySorterInterface;
use Siarko\ConfigFiles\Modifier\ModifierManager;
use Siarko\ConfigFiles\Placement\IdConfigPlacementStrategy;
use Siarko\ConfigFiles\Provider\File\ScopedConfigLookup;
use Siarko\ConfigFiles\Provider\ScopedProvider;
use Siarko\ConfigFiles\Sorter\TopologicalSort;
use Siarko\DependencyManager\Config\DMKeys;
use Siarko\DependencyManager\Config\Init\Merge\ConfigMerger;
use Siarko\DependencyManager\Config\Init\Modifier\ArgumentTypeTagTransformer;
use Siarko\DependencyManager\Config\Init\Modifier\DirnameInjector;
use Siarko\DependencyManager\Config\Init\Modifier\ProjectRootInjector;
use Siarko\DependencyManager\Config\Init\Modifier\TypeNewTagTransformer;
use Siarko\DependencyManager\Generator\Developer\Factory\FactoryProvider;
use Siarko\DependencyManager\Generator\Developer\Factory\ObjectCreator;
use Siarko\DependencyManager\Paths\Files\Parsers\YamlConfigParser;
use Siarko\DependencyManager\Type\TypedArgument;
use Siarko\DependencyManager\Type\TypedType;
use Siarko\Files\Parse\ParserManager;
use Siarko\Paths\Provider\ProjectPathProvider;
use Siarko\Serialization\Json\JsonSerializer;

\Siarko\DependencyManager\Config\Init\BootConfiguration::register([
    DMKeys::TYPES => [
        FactoryProviderInterface::class => FactoryProvider::class,
        ObjectCreatorInterface::class => ObjectCreator::class,
        ModifierManagerInterface::class => ModifierManager::class,
        PrioritySorterInterface::class => TopologicalSort::class,
        ConfigPlacementStrategyInterface::class => IdConfigPlacementStrategy::class,
        ConfigMergerInterface::class => ConfigMerger::class,
        'V\Siarko\DependencyManager\Config\Cache\DirectoryProvider' =>
            new TypedType(TypedType::TYPE_NEW, ProjectPathProvider::class),
        'V\Siarko\DependencyManager\Config\Cache' =>
            new TypedType(TypedType::TYPE_NEW, CacheSet::class),
        'V\Siarko\DependencyManager\Config\Provider' =>
            new TypedType(TypedType::TYPE_NEW, ScopedProvider::class),
        'V\Siarko\DependencyManager\Config\CachedConfigProvider' =>
            new TypedType(TypedType::TYPE_NEW, CachedConfigProvider::class),
        'V\Siarko\DependencyManager\Config\Provider\Lookup' =>
            new TypedType(TypedType::TYPE_NEW, ScopedConfigLookup::class),
        'V\Siarko\DependencyManager\Config\Provider\Sorter' =>
            new TypedType(TypedType::TYPE_NEW, TopologicalSort::class)
    ],
    DMKeys::ARGUMENTS => [
        \Siarko\Api\State\AppState::class => [
            'config' => new TypedArgument('provider', \Siarko\BootConfig\BootConfig::class. '::getAllData')
        ],
        ModifierManager::class => [
            'modifiers' => [
                DirnameInjector::class,
                ProjectRootInjector::class,
                TypeNewTagTransformer::class,
                ArgumentTypeTagTransformer::class
            ]
        ],
        ParserManager::class => [
            'parsers' => [
                'dm_config' => [
                    YamlConfigParser::class => [
                        'mimeTypes' => ['application/yaml', 'text/yaml'],
                        'parser' => new TypedArgument('object', YamlConfigParser::class)
                    ]
                ]
            ]
        ],
        CacheSet::class => [
            'serializer' => JsonSerializer::class
        ],
        'V\Siarko\DependencyManager\Config\Cache\DirectoryProvider' => [
            'path' => 'generated/cache/dependency-injection'
        ],
        'V\Siarko\DependencyManager\Config\Cache' => [
            'cacheDirectory' => 'V\Siarko\DependencyManager\Config\Cache\DirectoryProvider'
        ],
        'V\Siarko\DependencyManager\Config\Provider\Lookup' => [
            'pathProvider' => ProjectPathProvider::class
        ],
        'V\Siarko\DependencyManager\Config\Provider\Sorter' => [
            'dependencyKey' => DMKeys::DEPENDENCIES
        ],
        'V\Siarko\DependencyManager\Config\Provider' => [
            'fileLookup' => 'V\Siarko\DependencyManager\Config\Provider\Lookup',
            'prioritySorter' => 'V\Siarko\DependencyManager\Config\Provider\Sorter',
            'configMerger' => ConfigMerger::class,
            'fileParserType' => 'dm_config'
        ],
        'V\Siarko\DependencyManager\Config\CachedConfigProvider' => [
            'cache' => 'V\Siarko\DependencyManager\Config\Cache',
            'configProvider' => 'V\Siarko\DependencyManager\Config\Provider',
            'providerType' => \Siarko\DependencyManager\Config\Init\Configurator::DM_CONFIG_TYPE
        ]
    ]
]);