<?php

namespace Iambateman\Speedrun\Actions\Tasks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\Yaml\Yaml;

class GetTask {

    use AsAction;

    public function handle(?string $path = ''): ?array
    {
        if($path && str($path)->startsWith('/')) {
            return static::getSchemaFromFile($path);
        }

        return $this->getIncompleteTasks()->first();
    }

    public function getIncompleteTasks(): Collection
    {
        return $this->getTasks()->where('Complete', '!=', true);
    }

    public function getTasks(): Collection
    {
        $path = base_path('_ai/tasks');

        $paths = $this->glob_recursive($path, '*.{yml,yaml}', GLOB_BRACE);

        return collect($paths)->map(fn($path) => $this->getSchemaFromFile($path));
    }

    protected static function getSchemaFromFile($path): array|null
    {
        if (File::isFile($path)) {
            $file = Yaml::parseFile($path);
            $file['Path'] = $path;
            return $file;
        }

        // try .yaml as well
        $path = Str::of($path)->replaceLast('yml', 'yaml');
        if (File::isFile($path)) {
            return Yaml::parseFile($path);
        }

        return null;
    }

    protected function glob_recursive($base, $pattern, $flags = 0)
    {
        $flags = $flags & ~GLOB_NOCHECK;

        if (substr($base, - 1) !== DIRECTORY_SEPARATOR) {
            $base .= DIRECTORY_SEPARATOR;
        }

        $files = glob($base . $pattern, $flags);
        if (!is_array($files)) {
            $files = [];
        }

        $dirs = glob($base . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
        if (!is_array($dirs)) {
            return $files;
        }

        foreach ($dirs as $dir) {
            $dirFiles = $this->glob_recursive($dir, $pattern, $flags);
            $files = array_merge($files, $dirFiles);
        }

        return $files;
    }


}