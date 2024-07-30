<?php

namespace Siarko\DependencyManager\Generator\Exception;

use Throwable;

class BaseClassNotFound extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("Could not find Base class for code generation: ".$message, $code, $previous);
    }


}