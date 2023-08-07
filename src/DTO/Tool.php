<?php

namespace Iambateman\Speedrun\DTO;

class Tool {

    public function __construct(
        public string $description,
        public string $command,
        public ?array $parameters = [], // key is name, value is description

    )
    {}
}