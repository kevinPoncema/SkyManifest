<?php
namespace App\Repositories;
use App\Models\Deploy;
class DeployRepo{
    public function create(array $data): Deploy
    {
        return Deploy::create($data);
    }

    public function findById(int $id): ?Deploy
    {
        return Deploy::findOrFail($id);
    }

    public function update(Deploy $deploy, array $data): Deploy
    {
        $deploy->update($data);
        return $deploy;
    }

    public function delete(Deploy $deploy): void
    {
        $deploy->delete();
    }

    public function findByGitConfigId(int $gitConfigId)
    {
        return Deploy::where('git_config_id', $gitConfigId)->get();
    }

    public function findByProjectId(int $projectId)
    {
        return Deploy::where('project_id', $projectId)->get();
    }

    public function findLatestSuccessful(int $projectId): ?Deploy
    {
        return Deploy::where('project_id', $projectId)
                    ->where('status', 'success')
                    ->latest()
                    ->first();
    }

    public function hasPendingDeploy(int $projectId): bool
    {
        return Deploy::where('project_id', $projectId)
                    ->whereIn('status', ['pending', 'processing'])
                    ->exists();
    }
}
