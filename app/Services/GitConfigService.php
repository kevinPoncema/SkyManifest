<?php

namespace App\Services;

use App\Models\GitConfig;
use App\Repositories\GitConfigRepo;

class GitConfigService
{
    public function __construct(
        private GitConfigRepo $gitConfigRepo
    ) {}

    /**
     * Get git configuration by project ID.
     */
    public function getByProject(int $projectId): ?GitConfig
    {
        return $this->gitConfigRepo->findByProjectId($projectId);
    }

    /**
     * Create or update git configuration for a project.
     */
    public function createOrUpdate(int $projectId, array $data): GitConfig
    {
        $data['project_id'] = $projectId;

        $existingConfig = $this->gitConfigRepo->findByProjectId($projectId);

        if ($existingConfig) {
            return $this->gitConfigRepo->update($existingConfig, $data);
        }

        return $this->gitConfigRepo->create($data);
    }

    /**
     * Delete git configuration by project ID.
     */
    public function deleteByProject(int $projectId): void
    {
        $gitConfig = $this->gitConfigRepo->findByProjectId($projectId);

        if ($gitConfig) {
            $this->gitConfigRepo->delete($gitConfig);
        }
    }
}
