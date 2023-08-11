<?php

namespace Iambateman\Speedrun\DTO;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class BladeComponent {

    protected string $element_reference; // <x-table.cell>
    protected bool $hasSlot;
    protected ?Collection $props; // ['foo', 'bar', 'baz']

    public function __construct(
        public string $path, // /Users/stephenbateman...
    )
    {
        $this->setElementReference();
        $this->setProps();
    }

    protected function setElementReference(): self
    {
        $path_to_remove = resource_path('views/components/');

        $this->element_reference = str($this->path)
            ->remove($path_to_remove)
            ->remove('.blade.php')
            ->replace('/', '.');

        return $this;
    }

    protected function setProps(): self
    {
        $file = File::get($this->path);

        if ($propsString = str($file)->match('/@props\([^)]*\)/')) {
            $propsString = $propsString->remove('@props(')->remove(')')->replace('\'', '"');
            $this->props = collect(json_decode($propsString));
        }

        $this->hasSlot = str($file)->contains("\$slot");

        return $this;
    }

    /*
     * Convert the element into
     * <x-table.cell :value="">
     */
    public function getElement(): string
    {
        $elementString = "<x-{$this->element_reference}";

        if($this->props) {
            $elementString .= " " . $this->props
                ->map(fn($prop) => ":$prop=''")
                ->implode(' ');
        }

        if($this->hasSlot) {
            $elementString .= "></x-{$this->element_reference}>";
        } else {
            $elementString .= "/>";
        }

        return $elementString;
    }

    public function getProps(): array
    {
        return $this->props;
    }

}