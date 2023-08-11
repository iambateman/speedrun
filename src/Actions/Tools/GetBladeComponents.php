<?php

namespace Iambateman\Speedrun\Actions\Tools;

use Iambateman\Speedrun\DTO\BladeComponent;
use Iambateman\Speedrun\Traits\AsTool;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use Symfony\Component\Finder\SplFileInfo;

class GetBladeComponents {

    use AsAction;
    use AsTool;

    public Collection $componentFiles;
    public Collection $components;

    public string $commandSignature = 'speedrun:get-blade-components';

    public function handle(): Collection
    {

        if (!$this->confirmComponentDirectory()) {
            $this->message = 'No components found';
            return collect([]);
        }

        return $this
            ->getComponentsFiles()
            ->buildComponentList()
            ->setSuccess()
            ->returnComponents();

    }

    public function asCommand(Command $command)
    {
        $this->handle();

        if ($this->message) {
            $command->info($this->message);
        }
        ($this->success) ?
            $command->info("Successfully got Blade components") :
            $command->warn("Failed getting Blade Components");
    }

    protected function getComponentsFiles(): self
    {
        $this->componentFiles = collect(File::allFiles(resource_path('views/components')));

        return $this;
    }

    protected function buildComponentList(): self
    {

        $this->components = $this->componentFiles
            ->filter(function (SplFileInfo $file) {
                return str($file->getPathname())->endsWith('.blade.php');
            })
            ->map(function (SplFileInfo $file) {
                return new BladeComponent(
                    path: $file->getPathname()
                );
            });

        return $this;
    }

    protected function setSuccess(): self
    {
        $this->success = true;
        return $this;
    }

    protected function returnComponents(): Collection
    {
        return $this->components;
    }

    protected function confirmComponentDirectory(): bool
    {
        return File::isDirectory(resource_path('views/components'));
    }

}