<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class RunTests {

    use AsAction;

    public string $commandSignature = 'speedrun:run-tests';

    public bool $success = false;
    public Collection $testResults;

    public function handle(): bool
    {
        exec("./vendor/bin/pest && echo 'All successful'", $result);

        if (str(implode($result))->endsWith('All successful')) {
            return $this->success = true;
        }

        $this->testResults = $this->getPestResults($result);

        $this->success = ($this->testResults->isEmpty());

        return $this->success;
    }

    public function getPestResults(array $testResults): Collection
    {
        // Build prompt
        $prompt = "Review the test results below. Return only a JSON array of failed tests. If no tests failed, return an empty JSON array. \n";
        $prompt .= " Respond only with `[]`, do not include a 'failed_tests' key. \n";
        foreach ($testResults as $testResult) {
            $prompt .= $testResult . "\n";
        }

        // Assess the test results
        $assessment = GetAIWithFallback::run($prompt);

        // Return a collection of test results.
        return collect(json_decode($assessment));

    }

    public function asCommand(Command $command)
    {
        $this->handle();

        ($this->success) ?
            $command->info('Success') : $command->info('Tests Failed');
    }

}