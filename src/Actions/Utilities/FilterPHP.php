<?php

namespace Iambateman\Speedrun\Actions\Utilities;

use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsAction;

class FilterPHP {

    use AsAction;

    public string $commandSignature = 'speedrun:filter-php {response}';

    public bool $success = false;
    public string $message = '';
    public string $response;

    public function handle($response): string
    {
        $stringedResponse = str($response);

        // *****
        // If it just sent back the file, we are good.
        if ($stringedResponse->startsWith('<?php')) {
            return $stringedResponse;
        }

        // *****
        // Failure case, there is apparently no PHP file.
        if (!$stringedResponse->contains('```')) {
            info($stringedResponse);
            throw new ConfusedLLMException('The LLM doesnt appear to have sent back the correct information. We logged it.');
        }

        // Get the stuff between
        $filtered = $stringedResponse->between('```php', '```');


        if (!$filtered) {
            $filtered = $stringedResponse->between('```', '```');
        }

        // Remove any extra notes.
        if ($filtered->contains('```')) {
            $filtered = $filtered->before('```');
        }

        // *****
        // PHP files *must* begin with <?php
        // And there can't be any white space.
        $filtered = $filtered->remove('<?php');
        $filtered = $filtered->start('<?php');

        return $filtered;
    }

    public function asCommand(Command $command)
    {
        $response = $command->argument('response');
        $this->handle($response);

        $command->info('Filtered PHP');
    }

}