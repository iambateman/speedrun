<?php

namespace Iambateman\Speedrun\Exceptions;

use Exception;

class FeatureLockedException extends Exception
{
    public function __construct(string $featureName, string $lockedBy)
    {
        $message = "Feature '{$featureName}' is currently locked by '{$lockedBy}'";
        parent::__construct($message);
    }
}