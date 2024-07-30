<?php

namespace Siarko\DependencyManager\Exceptions;

use JetBrains\PhpStorm\Pure;
use Throwable;

class AbstractDependencyException extends \Exception
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
     */
    #[Pure] public function __construct(string $message = "", array $callStack = [])
    {
        $index = 0;
        $callStack = array_map( function($v) use (&$index) { return '#'.$index++.' '.$v; }, $callStack);
        $message .= " \n## Dependency call stack: \n" . implode("\n", $callStack);
        parent::__construct($message);
    }




}