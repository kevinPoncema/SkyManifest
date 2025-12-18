<?php
namespace App\Repositories;
use App\Models\Domain;
class DomainRepo{
    public function create(array $data): Domain
    {
        return Domain::create($data);
    }

    public function findById(int $id): ?Domain
    {
        return Domain::findOrFail($id);
    }

    public function update(Domain $domain, array $data): Domain
    {
        $domain->update($data);
        return $domain;
    }

    public function delete(Domain $domain): void
    {
        $domain->delete();
    }

    public function findByProjectId(int $projectId)
    {
        return Domain::where('project_id', $projectId)->get();
    }

    public function findActiveByProjectId(int $projectId)
    {
        return Domain::where('project_id', $projectId)
                    ->where('is_active', true)
                    ->get();
    }

    public function findByUrl(string $url): ?Domain
    {
        return Domain::where('url', $url)->first();
    }

    public function findByUserId(int $userId)
    {
        return Domain::whereHas('project', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();
    }
}
