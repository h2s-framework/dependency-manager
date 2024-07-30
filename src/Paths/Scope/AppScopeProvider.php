<?php

namespace Siarko\DependencyManager\Paths\Scope;

use Siarko\Api\State\AppState;
use Siarko\Paths\Api\Scope\AppScopeProviderInterface;

class AppScopeProvider implements AppScopeProviderInterface
{

    /**
     * @param AppState $appState
     */
    public function __construct(
        private readonly AppState $appState
    )
    {
    }

    /**
     * @return string
     */
    public function getScopeName(): string
    {
        return $this->appState->getAppScope();
    }
}