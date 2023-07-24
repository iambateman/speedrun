<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Speedrun;
use OpenAI\Laravel\Facades\OpenAI;

class RequestAICompletion {

    protected string $model;
    protected string $prompt;

    public function __construct(string $prompt, string $model = null)
    {
        $this->prompt = $prompt;

        $this->model = ($model) ? $model : Speedrun::getModel();
        
    }

    public static function from(...$arguments): string
    {
        return (new static(...$arguments))->execute();
    }

    public function execute(): string
    {
        $result = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $this->prompt],
            ],
        ]);

        return $result['choices'][0]['message']['content'];
    }

}