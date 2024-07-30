<?php

namespace Siarko\DependencyManager\Exceptions;

use JetBrains\PhpStorm\Pure;
use Throwable;

class ScopeProviderNotSetException extends \Exception
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
     */
    #[Pure] public function __construct(
        string $message = "Scope provider has not been set",
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }


}