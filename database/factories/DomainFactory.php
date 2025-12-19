<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domainTypes = [
            fake()->domainName(),
            fake()->randomElement(['app', 'www', 'admin', 'api', 'staging', 'dev']) . '.' . fake()->domainName(),
            fake()->lexify('????') . '.skymanifest.cloud',
            fake()->lexify('???-???') . '.skymanifest.io',
            fake()->randomElement(['my', 'get', 'use', 'try']) . fake()->lexify('????') . '.com',
        ];

        $sslStatuses = ['issued', 'pending', 'failed', 'expired'];

        return [
            'project_id' => Project::factory(),
            'url' => fake()->randomElement($domainTypes),
            'is_active' => fake()->boolean(80), // 80% chance to be active
            'ssl_status' => fake()->randomElement($sslStatuses),
        ];
    }

    /**
     * Create an active domain.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'ssl_status' => 'issued',
        ]);
    }

    /**
     * Create an inactive domain.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a domain with SkyManifest subdomain.
     */
    public function skymanifest(): static
    {
        return $this->state(fn (array $attributes) => [
            'url' => fake()->lexify('???-???') . '.skymanifest.cloud',
            'is_active' => true,
            'ssl_status' => 'issued',
        ]);
    }
}
