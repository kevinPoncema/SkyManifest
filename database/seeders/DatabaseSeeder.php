<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\GitConfig;
use App\Models\Domain;
use App\Models\Deploy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding SkyManifest database...');

        // 1. Create Admin User for testing
        $admin = User::factory()->create([
            'name' => 'SkyManifest Admin',
            'email' => 'admin@skymanifest.local',
            'email_verified_at' => now(),
        ]);

        $this->command->info('👤 Admin user created: admin@skymanifest.local');

        // 2. Create Admin's Projects with specific scenarios
        $this->createAdminProjects($admin);

        // 3. Create additional users with random projects for data isolation testing
        $this->createRandomUsers();

        $this->command->info('✅ Database seeded successfully!');
    }

    /**
     * Create specific test scenarios for the admin user.
     */
    private function createAdminProjects(User $admin): void
    {
        // Project 1: Complete Git Project
        $gitProject = Project::factory()->create([
            'user_id' => $admin->id,
            'name' => 'Portfolio React App',
            'description' => 'Personal portfolio built with React and deployed via Git integration.',
        ]);

        // Git configuration for Project 1
        GitConfig::factory()->create([
            'project_id' => $gitProject->id,
            'repository_url' => 'https://github.com/skymanifest/portfolio-react.git',
            'branch' => 'main',
            'base_directory' => '/build',
        ]);

        // Active domain for Project 1
        Domain::factory()->active()->create([
            'project_id' => $gitProject->id,
            'url' => 'portfolio.skymanifest.cloud',
        ]);

        // 5 Deploys for Project 1 (mixed success/failure from Git)
        Deploy::factory()->git()->successful()->create(['project_id' => $gitProject->id]);
        Deploy::factory()->git()->failed()->create(['project_id' => $gitProject->id]);
        Deploy::factory()->git()->successful()->create(['project_id' => $gitProject->id]);
        Deploy::factory()->git()->successful()->create(['project_id' => $gitProject->id]);
        Deploy::factory()->git()->successful()->create(['project_id' => $gitProject->id]);

        $this->command->info('📁 Git Project created with 5 deploys and 1 domain');

        // Project 2: ZIP-only Project
        $zipProject = Project::factory()->create([
            'user_id' => $admin->id,
            'name' => 'Landing Page Marketing',
            'description' => 'Static landing page for marketing campaigns, deployed via ZIP upload.',
        ]);

        // Domain for Project 2
        Domain::factory()->active()->create([
            'project_id' => $zipProject->id,
            'url' => 'landing.skymanifest.io',
        ]);

        // 2 ZIP Deploys for Project 2
        Deploy::factory()->zip()->successful()->create(['project_id' => $zipProject->id]);
        Deploy::factory()->zip()->successful()->create(['project_id' => $zipProject->id]);

        $this->command->info('📦 ZIP Project created with 2 deploys and 1 domain');

        // Project 3: Empty Project (freshly created)
        $emptyProject = Project::factory()->create([
            'user_id' => $admin->id,
            'name' => 'New E-commerce Store',
            'description' => 'E-commerce project in planning phase.',
        ]);

        $this->command->info('🆕 Empty project created (no deploys or domains)');
    }

    /**
     * Create additional users with random projects for testing data isolation.
     */
    private function createRandomUsers(): void
    {
        User::factory(5)->create()->each(function (User $user) {
            // Each user gets 1-2 random projects
            $projectCount = fake()->numberBetween(1, 2);

            Project::factory($projectCount)->create(['user_id' => $user->id])->each(function (Project $project) {
                // 50% chance of having Git config
                if (fake()->boolean(50)) {
                    GitConfig::factory()->create(['project_id' => $project->id]);
                }

                // Each project gets 0-2 domains
                $domainCount = fake()->numberBetween(0, 2);
                if ($domainCount > 0) {
                    Domain::factory($domainCount)->create(['project_id' => $project->id]);
                }

                // Each project gets 0-3 deploys
                $deployCount = fake()->numberBetween(0, 3);
                if ($deployCount > 0) {
                    Deploy::factory($deployCount)->create(['project_id' => $project->id]);
                }
            });
        });

        $this->command->info('👥 5 additional users created with random projects');
    }
}
