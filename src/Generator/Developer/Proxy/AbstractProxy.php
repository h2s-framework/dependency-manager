<?php

namespace Siarko\DependencyManager\Generator\Developer\Proxy;

use Siarko\DependencyManager\DependencyManager;
use Siarko\DependencyManager\Exceptions\CircularDependencyException;
use Siarko\DependencyManager\Exceptions\ClassNotInstantiable;
use Siarko\DependencyManager\Exceptions\DmServiceException;
use Siarko\DependencyManager\Exceptions\ParameterNotConstructable;
use Siarko\Utils\Exceptions\TypeCastException;

/**
 * Class AbstractProxy
 * Generic proxy class for dynamic proxy generation
 */
class AbstractProxy
{
    public const TYPENAME_PROPERTY = 'TYPENAME';

    protected static string $TYPENAME = '';

    private ?object $__subject = null;

    /**
     * @param DependencyManager $__dependencyManager
     */
    public function __construct(
        private readonly \Siarko\DependencyManager\DependencyManager $__dependencyManager
    )
    {
    }

    /**
     * @return object
     * @throws CircularDependencyException
     * @throws ClassNotInstantiable
     * @throws DmServiceException
     * @throws ParameterNotConstructable
     * @throws TypeCastException
     */
    protected function __getSubject(): object
    {
        if (!$this->__subject) {
            $this->__subject = $this->__dependencyManager->get(static::$TYPENAME);
        }
        return $this->__subject;
    }
}