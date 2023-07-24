<?php

namespace Iambateman\Speedrun;

class Speedrun
{
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
}
