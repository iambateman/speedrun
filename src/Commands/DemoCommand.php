<?php

namespace Iambateman\Speedrun\Commands;

use Illuminate\Console\Command;

class DemoCommand extends Command {

    public $signature = 'speedrun:demo';

    public $description = 'Demo of Speedrun';

    protected string $prompt;

    protected string $response;

    protected int $sleepy_time = 220000;

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
        $this->slowInfo("SPEED");
        $this->slowInfo("RUN");
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

        sleep(2);

        $this->askWithCompletion("have you already aliased the package?");

        $this->runIterator();

        return self::SUCCESS;
    }

    public function slowInfo($text, $type = 'info')
    {
        // center the text.
        $length = str($text)->length();
        $padding = round((30 - $length) / 2, 0);

        usleep($this->sleepy_time);

        $this->$type(">>>>" . str_repeat(' ', $padding) . $text . str_repeat(' ', $padding));
    }

    public function runIterator()
    {
        $choiceCollection = collect($this->choices);

        if ($choiceCollection->isEmpty()) {
            return $this->closeDemo();
        }

        $nextWord = ($choiceCollection->count() == 3) ? 'first' : 'next';

        $choice = ($choiceCollection->count() > 1) ?
            $this->choice("What do you want to see {$nextWord}?", [...$this->choices, "Robot's Choice"]) :
            $choiceCollection->first();

        if (($key = array_search($choice, $this->choices)) !== false) {
            unset($this->choices[$key]);
        }

        $this->pickNext($choice);

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
        $this->slowInfo("this package");
        $this->slowInfo("lives at");
        $this->slowInfo("php artisan speedrun", 'comment');
        $this->slowInfo("");
        sleep(1);
        $this->slowInfo("But that is no fun to type");
        $this->slowInfo("so we recommend using");
        $this->slowInfo("sr", 'comment');
        sleep(1);

        $this->slowInfo("Add this to your .zshrc file");
        $this->slowInfo("alias sr=\"php artisan speedrun\"", 'comment');

    }

    public function lazyArtisan()
    {
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
        } elseif($response != '') {
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
        sleep(1);
        
        $this->runIterator();
    }

    public function queryToddler()
    {
        $this->slowInfo("Ok!");
        $this->slowInfo("Let's ask the database");
        $this->slowInfo("a real question");
        $this->slowInfo("");
        $this->slowInfo("↓↓↓↓↓");
        $this->slowInfo("↓↓↓");
        $this->slowInfo("↓");
        $this->slowInfo("");
        $this->slowInfo("Heads up, this will be real.");
        $this->slowInfo("");
        $this->slowInfo("sr start queue", 'comment');
        $this->slowInfo("(or anything similar)");
        $this->slowInfo("");
        $this->slowInfo("");
        sleep(1);

        $response = $this->ask("QUIZ! practice typing 'sr start queue' (or press enter).");
        $this->call('speedrun:run-query-command', ['input' => $this->inputText]);


        if ($response == 'sr start queue') {
            $this->slowInfo("✅ ✅ ✅ ✅ ✅");
            $this->slowInfo("✅ ✅ ✅");
            $this->slowInfo("✅");
            $this->slowInfo("You are a star student! 🤠");
            $this->slowInfo("");
        } elseif($response != '') {
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
        sleep(1);

        $this->runIterator();
    }

    public function composerPackage()
    {

        $this->runIterator();
    }

    public function robotsChoice()
    {
        $choice = array_rand($this->choices);
        $this->pickNext($choice);
    }

    public function closeDemo()
    {
        $this->info('Thanks for checking out the demo of Speedrun. I hope it works really well for you.');

        $this->runIterator();
    }


}
