<?php

namespace Iambateman\Speedrun\Actions;

use Iambateman\Speedrun\Actions\RequestAICompletion;
use Iambateman\Speedrun\Exceptions\ConfusedLLMException;
use Iambateman\Speedrun\Helpers\Helpers;
use Iambateman\Speedrun\Speedrun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lorisleiva\Actions\Concerns\AsAction;
use function Laravel\Prompts\text;

class MakeBrief {

    use AsAction;

    public string $commandSignature = 'speedrun:make-brief';

    protected string $name;
    protected string $description;
    public bool $success = false;

    public function handle($name, $description): void
    {
        $this->name = $name;
        $this->description = $description;

        $prompt = $this->buildPrompt();

        $response = GetAIWithFallback::run($prompt);
        $response = Speedrun::filterYaml($response);

        $this->placeFile($response);
    }

    public function asCommand(Command $command)
    {
        $name = text(label: 'What is the name of the app?', required: true);
        $description = text(label: 'What is the name of the description?', required: true);

        $this->handle(
            $name,
            $description
        );

        ($this->success) ?
            $command->info('Success') : $command->info('Fail');
    }

    protected function buildPrompt()
    {
        $prompt = <<<END

You are creating a yaml file to describe the structured data of a Laravel app. A working example is like this:
```yaml
Name: Aviator

Overview: >
  The app helps business owners send emails using a third-party email API. 
  The app has lists of contacts in groups who receive email campaigns.

Models:
  Business:
      - Name *
      - Website (nullable)
      - Twitter (nullable)
      - Facebook (nullable)
      - Instagram (nullable)
  Contact:
      - First Name (nullable)
      - Last Name (nullable)
      - Email *
      - Data (json field)
  Tag:
      - Name *
  Campaign:
      - Name *
      - sent_at (nullable)

Relationships:
  - Contact BelongsTo Business
  - Tag BelongsTo Business
  - Campaign BelongsTo Business
  - Contact BelongsToMany Tag
  - Campaign BelongsTo Business
  - Campaign BelongsToMany Contacts
  - Contacts BelongsToMany Campaigns
  - Tags BelongsToMany Contact
  - Tags BelongsToMany Contacts
  - Tags BelongsToMany Campaigns
  - Contacts BelongsToMany Tags
```
END;

        $prompt .= "Now, the app I'm creating is called {$this->name}. The overview is this: {$this->description} ";
        $prompt .= "Please create a similar yaml file for {$this->name}. Guess what models, fields, and relationships will be needed.";

        return $prompt;
    }

    public function placeFile(string $response)
    {
        $date = now()->format('Y_m_d_His');
        $path = base_path("_ai/{$date}_brief.yaml");

        $this->success = File::put($path, $response);
    }

}