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
}
