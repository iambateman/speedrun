<?php

namespace Iambateman\Speedrun\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;

class NoAPIKeyException extends Exception implements ExceptionInterface
{
}
