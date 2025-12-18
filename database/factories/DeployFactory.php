<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\GitConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deploy>
 */
class DeployFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['pending', 'processing', 'success', 'failed'];
        $status = fake()->randomElement($statuses);
        $sourceType = fake()->randomElement(['git', 'zip']);
        
        $logMessages = $this->generateLogMessages($status, $sourceType);
        
        return [
            'project_id' => Project::factory(),
            'git_config_id' => $sourceType === 'git' ? GitConfig::factory() : null,
            'status' => $status,
            'source_type' => $sourceType,
            'commit_hash' => $sourceType === 'git' ? fake()->sha1() : null,
            'log_messages' => $logMessages,
            'path' => '/var/www/deploys/' . fake()->uuid(),
            'duration_ms' => fake()->numberBetween(1000, 30000),
        ];
    }

    /**
     * Create a Git deploy.
     */
    public function git(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'git',
            'commit_hash' => fake()->sha1(),
            'git_config_id' => GitConfig::factory(),
        ]);
    }

    /**
     * Create a ZIP deploy.
     */
    public function zip(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => 'zip',
            'commit_hash' => null,
            'git_config_id' => null,
        ]);
    }

    /**
     * Create a successful deploy.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'log_messages' => $this->generateLogMessages('success', $attributes['source_type'] ?? 'zip'),
            'duration_ms' => fake()->numberBetween(2000, 15000),
        ]);
    }

    /**
     * Create a failed deploy.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'log_messages' => $this->generateLogMessages('failed', $attributes['source_type'] ?? 'zip'),
            'duration_ms' => fake()->numberBetween(500, 8000),
        ]);
    }

    /**
     * Generate realistic log messages based on status and source type.
     */
    private function generateLogMessages(string $status, string $sourceType): array
    {
        $logs = [];
        
        if ($sourceType === 'git') {
            $logs[] = '🔄 Cloning repository...';
            $logs[] = '✅ Repository cloned successfully';
            $logs[] = '🔄 Checking out branch...';
            $logs[] = '✅ Branch checked out: main';
        } else {
            $logs[] = '📦 Extracting ZIP file...';
            $logs[] = '✅ ZIP file extracted';
        }
        
        $logs[] = '🔄 Installing dependencies...';
        
        if ($status === 'success') {
            $logs[] = '✅ Dependencies installed';
            $logs[] = '🔄 Building application...';
            $logs[] = '✅ Build completed successfully';
            $logs[] = '🔄 Sanitizing files...';
            $logs[] = '✅ Files sanitized';
            $logs[] = '🔄 Configuring web server...';
            $logs[] = '✅ Web server configured';
            $logs[] = '🚀 Deployment completed successfully!';
        } else if ($status === 'failed') {
            if (fake()->boolean(50)) {
                $logs[] = '❌ Failed to install dependencies';
                $logs[] = 'Error: Package not found in registry';
            } else {
                $logs[] = '✅ Dependencies installed';
                $logs[] = '🔄 Building application...';
                $logs[] = '❌ Build failed';
                $logs[] = 'Error: Compilation error in main.js';
            }
        } else if ($status === 'processing') {
            $logs[] = '🔄 Installing dependencies...';
        }
        
        return $logs;
    }
}
