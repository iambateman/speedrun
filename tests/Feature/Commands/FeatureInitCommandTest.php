<?php

use Iambateman\Speedrun\Commands\FeatureInitCommand;
use Iambateman\Speedrun\Services\DirectoryManager;
use Illuminate\Support\Facades\File;
use Iambateman\Speedrun\Tests\TestCase;

class FeatureInitCommandTest extends TestCase
{

    /** @test */
    public function it_creates_feature_directory_and_markdown_file()
    {
        $slug = 'test-feature';
        
        $this->artisan('speedrun:feature:init', ['slug' => $slug])
            ->expectsOutput("✅ Feature '{$slug}' initialized successfully!")
            ->assertExitCode(0);

        // Verify directory was created
        $featurePath = $this->testBasePath . '/wip/' . $slug;
        $this->assertTrue(File::exists($featurePath));
        $this->assertTrue(File::isDirectory($featurePath));

        // Verify markdown file was created
        $markdownPath = "{$featurePath}/_{$slug}.md";
        $this->assertTrue(File::exists($markdownPath));

        // Verify markdown content
        $content = File::get($markdownPath);
        $this->assertStringContainsString("feature_name: {$slug}", $content);
        $this->assertStringContainsString('phase: discovery', $content);
        $this->assertStringContainsString('# Overview', $content);
        $this->assertStringContainsString('# Requirements', $content);
    }

    /** @test */
    public function it_validates_slug_format()
    {
        $invalidSlug = 'Invalid_Slug!';
        
        $this->artisan('speedrun:feature:init', ['slug' => $invalidSlug])
            ->expectsOutput("Invalid slug format: '{$invalidSlug}'")
            ->expectsOutput('Slugs must be kebab-case (lowercase letters, numbers, and hyphens only).')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_accepts_valid_kebab_case_slugs()
    {
        $validSlugs = [
            'simple-feature',
            'feature-with-numbers-123',
            'a',
            'feature-with-multiple-hyphens',
        ];

        foreach ($validSlugs as $slug) {
            $this->artisan('speedrun:feature:init', ['slug' => $slug])
                ->assertExitCode(0);

            $markdownPath = $this->testBasePath . '/wip/' . $slug . '/_' . $slug . '.md';
            $this->assertTrue(File::exists($markdownPath), "Failed for slug: {$slug}");
        }
    }

    /** @test */
    public function it_fails_when_directory_already_exists()
    {
        $slug = 'existing-feature';
        $featurePath = $this->testBasePath . '/wip/' . $slug;
        
        // Create directory first
        File::makeDirectory($featurePath, 0755, true);
        
        $this->artisan('speedrun:feature:init', ['slug' => $slug])
            ->expectsOutput("❌ Feature slug '{$slug}' is already in use.")
            ->expectsOutput("AI: Please inform the user that the slug '{$slug}' is already in use and ask them to choose a different slug.")
            ->assertExitCode(1);
    }


    /** @test */
    public function it_displays_ai_instructions()
    {
        $slug = 'ai-test-feature';
        
        $this->artisan('speedrun:feature:init', ['slug' => $slug])
            ->expectsOutput('AI Instructions:')
            ->expectsOutput("Work with the user to build out a full description of the '{$slug}' feature.")
            ->expectsOutput('Ask questions to understand:')
            ->expectsOutput('  • What the feature should do')
            ->expectsOutput('  • Who will use it')
            ->expectsOutput('  • Technical requirements')
            ->expectsOutput('  • Dependencies and integrations')
            ->expectsOutput('  • Success criteria')
            ->expectsOutput('IMPORTANT: You are in a planning phase. Do NOT recommend creating new project code at this stage.')
            ->assertExitCode(0);
    }
}