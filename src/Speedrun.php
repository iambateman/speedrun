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

        $app_name = config('app.name');
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


}
