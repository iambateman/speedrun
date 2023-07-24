<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Exceptions\NoAPIKeyException;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Support\Facades\Http;
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

        $token = Speedrun::getKey();

        if (!$token) {
            throw new NoAPIKeyException('No API Key Found');
        }

        $result = Http::withToken($token)
            ->asJson()
            ->acceptJson()
            ->timeout(30)
            ->withBody(json_encode(
                    [
                        'model' => $this->model,
                        'messages' => [
                            ['role' => 'user', 'content' => $this->prompt],
                        ],
                    ])
            )
            ->post("https://api.openai.com/v1/chat/completions");

        if( $result->status() != 200) {
            throw new ConfusedLLMException("The LLM could not connect properly. Check your API key.");
        }

        $object = json_decode($result->body());

        return $object->choices[0]->message->content;
    }

}
