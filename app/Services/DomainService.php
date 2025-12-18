<?php

namespace App\Services;

use App\Models\Domain;
use App\Repositories\DomainRepo;
use Illuminate\Database\Eloquent\Collection;

class DomainService
{
    public function __construct(
        private DomainRepo $domainRepo
    ) {}

    /**
     * Get all domains for a project.
     */
    public function getByProject(int $projectId): Collection
    {
        return $this->domainRepo->findByProjectId($projectId);
    }

    /**
     * Create a new domain for a project.
     */
    public function create(int $projectId, array $data): Domain
    {
        $data['project_id'] = $projectId;
        return $this->domainRepo->create($data);
    }

    /**
     * Delete a domain by ID.
     */
    public function delete(int $domainId): void
    {
        $domain = $this->domainRepo->findById($domainId);
        $this->domainRepo->delete($domain);
    }

    /**
     * Get active domains for a project.
     */
    public function getActiveByProject(int $projectId): Collection
    {
        return $this->domainRepo->findActiveByProjectId($projectId);
    }

    /**
     * Check if domain URL is available.
     */
    public function isDomainAvailable(string $url): bool
    {
        return $this->domainRepo->findByUrl($url) === null;
    }
}
