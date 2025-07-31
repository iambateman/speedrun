<?php

namespace Iambateman\Speedrun;

class Speedrun {

    public function directory(string|null $slug = null): string
    {
        return base_path(config('speedrun.directory') . $slug);
    }
}
