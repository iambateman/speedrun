<?php

namespace Iambateman\Speedrun;

use Iambateman\Speedrun\DTO\Tool;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Speedrun {

    public static array $tools = [];


    /**
     * Get the AI service. Eventually we hope to support multiple LLM's.
     */
    public static function getService(): string
    {
        return 'open-ai';
    }

    /**
     * Get the GPT model used for processing requests.
     * Defaults to gpt-3.5-turbo, but you can use any valid OpenAI model, like 'gpt-4'.
     * Just a heads up, GPT-4 is way slower
     */
    public static function getModel(): string
    {
        return 'gpt-3.5-turbo';
    }

    /**
     * Get the GPT API key used for processing requests.
     */
    public static function getKey(): ?string
    {
        return env('SPEEDRUN_OPENAI_API_KEY', '');
    }

    public static function dieInProduction(): bool
    {
        return config('speedrun.dieInProduction');
    }

    public static function includeAppCommands(): bool
    {
        return config('speedrun.includeAppCommands');
    }

    public static function doubleConfirm(): bool
    {
        return config('speedrun.doubleConfirm');
    }


    public static function registerTools(array $tools)
    {
        // Require all tools to be instance of Tool.
        $tools = collect($tools)
            ->filter(fn($tool)=> $tool instanceof Tool)
            ->toArray();

        static::$tools = array_merge(
            static::$tools,
            $tools
        );

        return new static;
    }


    public static function getTools(): array
    {
        return static::$tools;
    }


    public static function getOverview(): string
    {
        $brief = Speedrun::getBrief();

        $overview = "You are building a Laravel app called {$brief['Name']}. ";
        $overview .= " {$brief['Overview']}";

        return $overview;
    }


    public static function getBrief(): array|null
    {
        $path = base_path('_ai/brief.yml');
        return Speedrun::getSchemaFromFile($path);
    }

    protected static function getSchemaFromFile($path): array|null
    {
        if (File::isFile($path)) {
            return Yaml::parseFile($path);
        }

        // try .yaml as well
        $path = Str::of($path)->replaceLast('yml', 'yaml');
        if (File::isFile($path)) {
            return Yaml::parseFile($path);
        }

        return null;
    }

    public static function runPreflightSafetyChecks()
    {
        echo 'Running migrations';
        Artisan::call('speedrun:run-migrations');
        echo Artisan::output();
        echo 'Running tests';
        Artisan::call('speedrun:run-tests');
        echo Artisan::output();
    }

    public static function filterPhp(string $response): string
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


        info('ran filter!');
        return $filtered;

    }

    public static function filterYaml(string $response): string
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

        info('ran filter!');
        return $filtered;

    }

}
