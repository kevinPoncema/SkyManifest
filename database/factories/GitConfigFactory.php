<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GitConfig>
 */
class GitConfigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $usernames = ['frontend-dev', 'webapp-builder', 'startup-team', 'dev-agency', 'codemaster'];
        $repoNames = [
            'landing-page', 'portfolio-site', 'ecommerce-web', 'company-website',
            'blog-platform', 'admin-dashboard', 'marketing-site', 'saas-frontend',
            'react-app', 'vue-project', 'nextjs-site', 'gatsby-blog'
        ];

        $branches = ['main', 'master', 'develop', 'production'];
        $baseDirectories = ['/', '/dist', '/build', '/public', '/docs'];

        return [
            'project_id' => Project::factory(),
            'repository_url' => sprintf(
                'https://github.com/%s/%s.git',
                fake()->randomElement($usernames),
                fake()->randomElement($repoNames)
            ),
            'branch' => fake()->randomElement($branches),
            'base_directory' => fake()->randomElement($baseDirectories),
        ];
    }
}
