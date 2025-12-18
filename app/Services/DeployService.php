<?php

namespace App\Services;

use App\Models\Deploy;
use App\Repositories\DeployRepo;
use Illuminate\Database\Eloquent\Collection;

class DeployService
{
    public function __construct(
        private DeployRepo $deployRepo
    ) {}

    /**
     * Get deployment history for a project.
     */
    public function getHistoryByProject(int $projectId): Collection
    {
        return $this->deployRepo->findByProjectId($projectId);
    }

    /**
     * Get deployment details by ID.
     */
    public function getDeployDetails(int $deployId): ?Deploy
    {
        return $this->deployRepo->findById($deployId);
    }

    /**
     * Get latest successful deployment for a project.
     */
    public function getLatestSuccessful(int $projectId): ?Deploy
    {
        return $this->deployRepo->findLatestSuccessful($projectId);
    }

    /**
     * Check if project has pending deployments.
     */
    public function hasPendingDeploy(int $projectId): bool
    {
        return $this->deployRepo->hasPendingDeploy($projectId);
    }

    /**
     * Get deploys by git config ID.
     */
    public function getByGitConfig(int $gitConfigId): Collection
    {
        return $this->deployRepo->findByGitConfigId($gitConfigId);
    }
}
