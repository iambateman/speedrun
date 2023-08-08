<?php

namespace Iambateman\Speedrun\Actions\Utilities;

use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class FilterYAML {

    use AsAction;

    public string $commandSignature = 'speedrun:filter-yaml {response}';

    public function handle($response): string
    {
        $stringedResponse = str($response);

        // *****
        // Failure case, there is apparently no structured data.
        if (!$stringedResponse->contains('```')) {
            info($stringedResponse);
            throw new ConfusedLLMException('The LLM doesnt appear to have sent back the correct information. We logged it.');
        }

        // Get the stuff between
        $filtered = $stringedResponse->between('```yaml', '```');


        if (!$filtered) {
            $filtered = $stringedResponse->between('```', '```');
        }


        // Remove any extra notes.
        if ($filtered->contains('```')) {
            $filtered = $filtered->before('```');
        }

        return $filtered;
    }

    public function asCommand(Command $command)
    {
        $response = $command->argument('response');
        $this->handle($response);

        $command->info('Filtered YAML');
    }

}