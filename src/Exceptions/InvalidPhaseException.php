<?php

namespace Iambateman\Speedrun\Exceptions;

use Exception;
use Iambateman\Speedrun\Enums\FeaturePhase;

class InvalidPhaseException extends Exception
{
    public function __construct(FeaturePhase $from, FeaturePhase $to)
    {
        $message = "Invalid phase transition from {$from->value} to {$to->value}";
        parent::__construct($message);
    }
}