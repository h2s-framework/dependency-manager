<?php

namespace Siarko\DependencyManager\Exceptions;

use Throwable;

class CouldNotResolveNamespace extends \Exception
{
    public function __construct($path, $code = 0, Throwable $previous = null)
    {
        parent::__construct("Could not resolve namespace for file: ".$path, $code, $previous);
    }


}