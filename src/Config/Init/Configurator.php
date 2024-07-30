<?php

namespace Siarko\DependencyManager\Config\Init;

use Siarko\Api\State\AppState;
use Siarko\Api\State\AppStateInterface;
use Siarko\Api\State\Scope\ScopeProviderRegistry;
use Siarko\CacheFiles\Api\CacheSetInterface;
use Siarko\ConfigCache\Api\Provider\CachedConfigProviderInterface;
use Siarko\DependencyManager\Api\Config\ConfiguratorInterface;
use Siarko\DependencyManager\Config\Init\Apply\Applicator;
use Siarko\DependencyManager\Config\Runtime\Alias\AliasManager;
use Siarko\DependencyManager\Config\Runtime\Argument\ArgumentResolver;
use Siarko\DependencyManager\DependencyManager;
use Siarko\DependencyManager\Exceptions\CircularDependencyException;
use Siarko\DependencyManager\Exceptions\ClassNotInstantiable;
use Siarko\DependencyManager\Exceptions\DmServiceException;
use Siarko\DependencyManager\Exceptions\ParameterNotConstructable;
use Siarko\DependencyManager\Generator\CodeGenerator;
use Siarko\Events\EventManager;
use Siarko\Paths\RootPath;
use Siarko\Utils\Code\ClassStructureProvider;
use Siarko\Utils\Exceptions\TypeCastException;
use Siarko\Utils\TypeManager;

class Configurator implements ConfiguratorInterface
{

    public const DM_CONFIG_TYPE = 'dm';

    public const DM_CONFIG_CACHE_TYPE = 'V\Siarko\DependencyManager\Config\CachedConfigProvider';


    /**
     * @param DependencyManager $instance
     * @param string $projectRoot
     * @return void
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    public function configure(DependencyManager $instance, string $projectRoot): void
    {
        //BIND INITIAL OBJECTS
        $instance->nuke();
        $this->bindServices($instance);

        $instance->get(RootPath::class)->set($projectRoot);

        // READ CONFIGS AND APPLY
        $loadedFromCache = $this->phaseOneLoad($instance); //Initial configuration load (without saving cache)
        if(!$loadedFromCache){ // looks like config was not cached
            $this->phaseTwoLoad($instance); //reading config again and cache it
        }
        $eventManager = $instance->get(EventManager::class);
        $eventManager->dispatch('h2s.dm.configured', $instance);
    }

    /**
     * @param DependencyManager $instance
     * @return bool
     * @throws \Siarko\DependencyManager\Exceptions\CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    private function phaseOneLoad(DependencyManager $instance): bool
    {
        $this->loadBasicConfig($instance);
        $appState = $instance->get(AppState::class);
        $instance->startObjectTracking();
        //Config provider init
        /** @var CachedConfigProviderInterface $configProvider */
        $configProvider = $instance->get(self::DM_CONFIG_CACHE_TYPE);

        $cacheExists = $configProvider->exists($appState->getAppScope());
        $config = $configProvider->fetch($appState->getAppScope());

        $createdInstances = $instance->stopObjectTracking();
        if (!$cacheExists) {
            $instance->flushInstances($createdInstances);
        }
        $this->applyConfig($instance, $config);
        $instance->get(CodeGenerator::class); //init code generator - it registers itself as codeloader
        return $cacheExists;
    }

    /**
     * @param DependencyManager $instance
     * @return void
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     * @throws CircularDependencyException
     */
    private function phaseTwoLoad(DependencyManager $instance): void
    {
        /** @var CachedConfigProviderInterface $configProvider */
        /** @var CacheSetInterface $configCache */
        /** @var AppStateInterface $appState */
        $appState = $instance->get(AppState::class);
        $configProvider = $instance->get(self::DM_CONFIG_CACHE_TYPE);
        $configProvider->clear($appState->getAppScope());
        $config = $configProvider->fetch($appState->getAppScope());

        $this->applyConfig($instance, $config);
    }

    /**
     * @param DependencyManager $instance
     * @return void
     */
    private function bindServices(DependencyManager $instance): void
    {
        $instance->bindObject(ClassStructureProvider::class, new ClassStructureProvider());
        $instance->bindObject(ArgumentResolver::class, new ArgumentResolver());
        $instance->bindObject(AliasManager::class, new AliasManager());
        $instance->bindObject(TypeManager::class, new TypeManager($instance));
    }

    /**
     * @param DependencyManager $instance
     * @return void
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    private function loadBasicConfig(DependencyManager $instance): void
    {
        $config = BootConfiguration::getConfig();
        $this->applyConfig($instance, $config);
    }

    /**
     * @param DependencyManager $instance
     * @param array $config
     * @return void
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    private function applyConfig(DependencyManager $instance, array $config): void
    {
        $applier = $instance->get(Applicator::class);
        $instance->flushInstances([AliasManager::class]);
        $applier->apply($config);
    }

}