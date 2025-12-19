<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\ProjectRepo;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function __construct(
        private ProjectRepo $projectRepo
    ) {}

    /**
     * Get all projects for a user.
     */
    public function getAllByUser(int $userId): Collection
    {
        return $this->projectRepo->allForUserWithStatus($userId);
    }

    /**
     * Create a new project.
     */
    public function create(array $data): Project
    {
        return $this->projectRepo->create($data);
    }

    /**
     * Update a project by ID.
     */
    public function update(int $id, array $data): Project
    {
        $project = $this->projectRepo->findById($id);
        return $this->projectRepo->update($project, $data);
    }

    /**
     * Delete a project by ID.
     */
    public function delete(int $id): void
    {
        $project = $this->projectRepo->findById($id);
        $this->projectRepo->delete($project);
    }

    /**
     * Get project by ID.
     */
    public function getById(int $id): ?Project
    {
        return $this->projectRepo->findById($id);
    }
}
