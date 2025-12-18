<?php
namespace App\Repositories;
use App\Models\Project;
class ProjectRepo
{
    public function allForUser(int $userId)
    {
        return Project::where('user_id', $userId)->get();
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function findById(int $id): ?Project
    {
        return Project::findOrFail($id);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function allForUserWithStatus(int $userId)
    {
        return Project::where('user_id', $userId)
                    ->with(['domains', 'latestDeploy'])
                    ->get();
    }
}
