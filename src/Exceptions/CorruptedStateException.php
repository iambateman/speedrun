<?php

namespace Iambateman\Speedrun\Exceptions;

use Exception;

class CorruptedStateException extends Exception
{
    public function __construct(string $path, string $reason = 'Invalid YAML frontmatter')
    {
        $message = "Feature file is corrupted at '{$path}': {$reason}";
        parent::__construct($message);
    }
}