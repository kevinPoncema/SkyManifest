<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $projectTypes = [
            'Landing Page',
            'Portfolio Personal',
            'E-commerce Store',
            'Blog Corporativo',
            'Dashboard Admin',
            'Sitio Web Empresa',
            'App Documentation',
            'Marketing Site',
            'SaaS Platform',
            'Community Forum'
        ];

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($projectTypes) . ' - ' . fake()->company(),
            'description' => fake()->optional(0.7)->paragraph(2),
        ];
    }

    /**
     * Create a project with git configuration.
     */
    public function withGit(): static
    {
        return $this->hasGitConfig(1);
    }

    /**
     * Create a project with domains.
     */
    public function withDomains(int $count = 1): static
    {
        return $this->hasDomains($count);
    }

    /**
     * Create a project with deploys.
     */
    public function withDeploys(int $count = 1): static
    {
        return $this->hasDeploys($count);
    }
}
