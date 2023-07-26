<?php

namespace Iambateman\Speedrun\Commands;

use Iambateman\Speedrun\Exceptions\NoAPIKeyException;
use Iambateman\Speedrun\Facades\Speedrun;
use Illuminate\Console\Command;

class DemoCommand extends Command {

    public $signature = 'speedrun:demo';

    public $description = 'Demo of Speedrun';

    protected string $prompt;

    protected string $response;

    protected int $sleepy_time = 220000; // 220000

    protected array $choices = [
        'How to be lazy with artisan',
        'How to write a query like a toddler',
        'How to screw up a composer package and have it still work'
    ];

    public function handle(): int
    {
        $this->slowInfo("Thanks for trying out");
        $this->slowInfo("↓↓↓↓↓");
        $this->slowInfo("↓↓↓");
        $this->slowInfo("↓");
        $this->slowInfo("");
        $this->slowInfo("SPEED", 'warn');
        $this->slowInfo("RUN", 'warn');
        $this->slowInfo("");

        $this->slowInfo("   ╬═╬   ");
        $this->slowInfo("   ╬═╬   ");
        $this->slowInfo("   ╬═╬   ");
        $this->slowInfo("   ╬═╬☻/ ");
        $this->slowInfo("   ╬═╬/▌ ");
        $this->slowInfo("   ╬═╬/ \\");
        $this->slowInfo("");
        $this->slowInfo("The easiest way");
        $this->slowInfo("to write");
        $this->slowInfo("Laravel commands");
        $this->slowInfo("using normal language.");
        $this->slowInfo("");
        $this->slowInfo("");

        if (!Speedrun::getKey()) {
            throw new NoAPIKeyException('Please add OPENAI_API_KEY to .env, then re-run php artisan speedrun:demo.');
        }

        sleep(1);

        if (!$this->confirm("Have you already added the sr alias for the package?", true)) {
            $this->aliasStep();
        }

        $this->runIterator();

        return self::SUCCESS;
    }

    public function slowInfo($text, $type = 'info')
    {
        // center the text.
        $length = str($text)->length();
        $padding = round((32 - $length) / 2, 0);

        usleep($this->sleepy_time);

        $this->$type(">>>>" . str_repeat(' ', $padding) . $text . str_repeat(' ', $padding));
    }

    public function runIterator()
    {
        $choiceCollection = collect($this->choices);

        if ($choiceCollection->isEmpty()) {
            $this->closeDemo();
            return false;
        }

        $nextWord = ($choiceCollection->count() == 3) ? 'first' : 'next';

        $choice = ($choiceCollection->count() > 1) ?
            $this->choice("What do you want to see {$nextWord}?", [...$this->choices, "Robot's Choice"]) :
            $choiceCollection->first();

        if($choiceCollection->count() == 1) {
          $this->transitionToLast();
        };

        if (($key = array_search($choice, $this->choices)) !== false) {
            unset($this->choices[$key]);
        }


        $this->pickNext($choice);

    }

    public function transitionToLast()
    {
        sleep(1);
        $this->slowInfo("------------");
        $this->slowInfo("------------------");
        $this->slowInfo("-------------------------");
        $this->slowInfo("Now, the last thing!");
        $this->slowInfo("-------------------------");
        $this->slowInfo("------------------");
        $this->slowInfo("------------");
        sleep(1);
    }

    public function pickNext($choice)
    {
        match ($choice) {
            'How to be lazy with artisan' => $this->lazyArtisan(),
            'How to write a query like a toddler' => $this->queryToddler(),
            'How to screw up a composer package and have it still work' => $this->composerPackage(),
            default => $this->robotsChoice()
        };
    }

    public function aliasStep()
    {
        $this->slowInfo("");
        $this->slowInfo("ALIASING");
        $this->slowInfo("");
        $this->slowInfo("this package lives at");
        $this->slowInfo("php artisan speedrun", 'comment');
        $this->slowInfo("But that is no fun to type. 🙄");
        $this->slowInfo("");
        $this->slowInfo("So we recommend using");
        $this->slowInfo("sr", 'comment');
        $this->slowInfo("as an alias.");
        sleep(1);
        $this->slowInfo("");
        $this->slowInfo("add...");
        $this->slowInfo("alias sr=\"php artisan speedrun\"", 'comment');
        $this->slowInfo("to your .zshrc file.");

        $this->ask("ready to proceed? (press enter)", true);
        $this->slowInfo("");
        $this->slowInfo("");
        $this->slowInfo("Great!");
        $this->slowInfo("Now, to the fun stuff...");
        $this->slowInfo("");
        $this->slowInfo("");

        sleep(1);

    }

    public function lazyArtisan()
    {
        $this->slowInfo("");
        $this->slowInfo("ARTISAN");
        $this->slowInfo("");
        $this->slowInfo("Great!");
        $this->slowInfo("Let's be lazy");
        $this->slowInfo("with Artisan");
        $this->slowInfo("");
        $this->slowInfo("↓↓↓↓↓");
        $this->slowInfo("↓↓↓");
        $this->slowInfo("↓");
        $this->slowInfo("");
        $this->slowInfo("Instead of...");
        $this->slowInfo("php artisan queue:work", 'comment');
        $this->slowInfo("");
        $this->slowInfo("you can now write..");
        $this->slowInfo("sr start queue", 'comment');
        $this->slowInfo("(or anything similar)");
        $this->slowInfo("");
        $this->slowInfo("");
        sleep(1);

        $response = $this->ask("QUIZ! practice typing 'sr start queue' (or press enter).");

        if ($response == 'sr start queue') {
            $this->slowInfo("✅ ✅ ✅ ✅ ✅");
            $this->slowInfo("✅ ✅ ✅");
            $this->slowInfo("✅");
            $this->slowInfo("You are a star student! 🤠");
            $this->slowInfo("");
        } elseif ($response != '') {
            $this->slowInfo("🤔 🤔 🤔 🤔 🤔");
            $this->slowInfo("😦 😦 😦");
            $this->slowInfo("🫣");
            $this->slowInfo("Well, you didn't pass.");
            $this->slowInfo("");
            $this->slowInfo("");
        }

        $this->slowInfo("The point is that you can");
        $this->slowInfo("write whatever command");
        $this->slowInfo("and we will figure it out.");
        $this->slowInfo("");
        $this->slowInfo("");

        sleep(1);

        $this->slowInfo("This is most useful for");
        $this->slowInfo("custom commands, since they");
        $this->slowInfo("are harder to remember.");
        $this->slowInfo("");
        $this->slowInfo("");
        sleep(2);

        $this->runIterator();
    }

    public function queryToddler($expanded = true)
    {
        if ($expanded) {
            $this->slowInfo("");
            $this->slowInfo("QUERYING");
            $this->slowInfo("");
        }

        $this->slowInfo("Ok!");
        $this->slowInfo("Let's ask the database");
        $this->slowInfo("a real question");
        $this->slowInfo("using normal text");
        $this->slowInfo("");

        if ($expanded) {

            $this->slowInfo("↓↓↓↓↓");
            $this->slowInfo("↓↓↓");
            $this->slowInfo("↓");
            $this->slowInfo("");
            $this->slowInfo("(this will be real!)");
            $this->slowInfo("");
            $this->slowInfo("GPT automatically gets");
            $this->slowInfo("all your apps models,");
            $this->slowInfo("relationships, and fields");
            $this->slowInfo("so it knows how to answer");
            $this->slowInfo("\"intelligently.\"");
            $this->slowInfo("");
            sleep(1);

            $this->slowInfo("");
            $this->slowInfo("\"sr what is user 1 name?\"", 'comment');
            $this->slowInfo("is a good place to start");
            $this->slowInfo("");
            sleep(1);
        }

        $response = $this->ask("What would you like to ask?", "sr what is user 1 name?");
        $filtered = str($response)->remove('sr');
        $this->call('speedrun:run-query-command', ['input' => $filtered]);

        sleep(1);
        $this->slowInfo("");
        $this->slowInfo("Nice!");

        if ($this->confirm("would you like to try another query?", false)) {
            $this->queryToddler();
        }

        $this->slowInfo("There are tips for getting");
        $this->slowInfo("good input available at");
        $this->slowInfo("iambateman.com/speedrun");
        $this->slowInfo("");

        sleep(1);

        $this->runIterator();
    }

    public function composerPackage()
    {
        $this->slowInfo("");
        $this->slowInfo("COMPOSER");
        $this->slowInfo("");
        $this->slowInfo("📦");
        $this->slowInfo("📦");
        $this->slowInfo("📦");
        $this->slowInfo("I LOVE this feature.");
        $this->slowInfo("");
        $this->slowInfo("");
        $this->slowInfo("and its also so easy...");
        sleep(1);
        $this->slowInfo("");
        $this->slowInfo("");
        $this->slowInfo("type...");
        $this->slowInfo("sr install laravel debugbar", 'comment');
        $this->slowInfo("");
        $this->slowInfo("and Speedrun will generate");
        $this->slowInfo("the composer require statement");
        $this->slowInfo("to install the package.");
        $this->slowInfo("");
        $this->slowInfo("");
        sleep(1);

        $response = $this->ask("What would you like to install?", "laravel debugbar");
        $filtered = str($response)->remove('sr install');
        $this->call('speedrun:install-composer-package', ['input' => $filtered]);


        $this->runIterator();
    }

    public function robotsChoice()
    {
        $choice = array_rand($this->choices);
        $this->pickNext($choice);
    }

    public function closeDemo()
    {
        $this->slowInfo("");
        $this->slowInfo("");
        $this->slowInfo("");
        $this->slowInfo('Thanks for checking out');
        $this->slowInfo('Speedrun');
        $this->slowInfo("");
        $this->slowInfo('I hope it works');
        $this->slowInfo('well for you!');
        $this->slowInfo('');
        $this->slowInfo('');
        $this->slowInfo('And...last thing!');
        $this->slowInfo('');
        $this->slowInfo('');
        $this->slowInfo('#######################');
        $this->slowInfo('#                     #');
        $this->slowInfo('#          please     #');
        $this->slowInfo('#           share     #');
        $this->slowInfo('#        Speedrun     #', 'warn');
        $this->slowInfo('#            with     #');
        $this->slowInfo('#        a friend     #');
        $this->slowInfo('#                     #');
        $this->slowInfo('#######################');

        return static::SUCCESS;
    }


}
