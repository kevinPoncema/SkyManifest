<?php
namespace App\Repositories;
use App\Models\GitConfig;
class GitConfigRepo{
    public function create(array $data): GitConfig
    {
        return GitConfig::create($data);
    }

    public function findById(int $id): ?GitConfig
    {
        return GitConfig::findOrFail($id);
    }

    public function update(GitConfig $gitConfig, array $data): GitConfig
    {
        $gitConfig->update($data);
        return $gitConfig;
    }

    public function delete(GitConfig $gitConfig): void
    {
        $gitConfig->delete();
    }

    public function findByProjectId(int $projectId): ?GitConfig
    {
        return GitConfig::where('project_id', $projectId)->first();
    }
}
