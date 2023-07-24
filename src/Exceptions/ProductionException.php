<?php

namespace Iambateman\Speedrun\Exceptions;

use Exception;
use Symfony\Component\Console\Exception\ExceptionInterface;

class ProductionException extends Exception implements ExceptionInterface
{
}
