<?php

namespace App\Services;

use App\Models\Deploy;
use App\Models\GitConfig;
use App\Jobs\GitHubFetchSourceCodeJob;
use App\Jobs\PrepareStaticFilesJob;
use App\Jobs\ExtractZipJob;
use App\Jobs\ConfigureCaddyJob;
use App\Repositories\DeployRepo;
use App\Repositories\GitConfigRepo;
use App\Repositories\DomainRepo;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class DeployService
{
    public function __construct(
        private DeployRepo $deployRepo,
        private GitConfigRepo $gitConfigRepo,
        private DomainRepo $domainRepo
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

    /**
     * Performs complete deployment process from GitHub
     * 1. Gets Git configuration
     * 2. Gets active domains
     * 3. Validates that data is valid
     * 4. Creates Deploy record
     * 5. Triggers job chain
     * 
     * @throws RuntimeException if configuration or domains are missing
     */
    public function deployWithGithub(int $projectId, string $projectName): Deploy
{
    Log::info("Iniciando despliegue GitHub para proyecto: $projectId");
    $gitConfig = $this->gitConfigRepo->findByProjectId($projectId);
    if (!$gitConfig || empty($gitConfig->repository_url)) {
        throw new RuntimeException('Repositorio Git no configurado.');
    }

    $domains = $this->domainRepo->findActiveByProjectId($projectId);
    if ($domains->isEmpty()) {
        throw new RuntimeException('No hay dominios activos para desplegar.');
    }
    $deploymentPath = "www." . Str::slug($projectName);

    $deploy = Deploy::create([
        'project_id' => $projectId,
        'git_config_id' => $gitConfig->id,
        'status' => 'pending',
        'source_type' => 'git',
        'path' => $deploymentPath,
        'log_messages' => ['[' . now()->toTimeString() . '] Despliegue iniciado desde GitHub'],
    ]);

    try {
        Bus::chain([
            new GitHubFetchSourceCodeJob($deploy, $gitConfig, $deploymentPath),
            new PrepareStaticFilesJob($deploy, $deploymentPath, $gitConfig),
            new ConfigureCaddyJob($deploy, $deploymentPath, $domains),
        ])->dispatch();

        Log::info("Jobs despachados para Deploy ID: {$deploy->id}");

    } catch (\Exception $e) {
        $deploy->status = 'failed';
        $deploy->log_messages = array_merge($deploy->log_messages, ['Error inicial: ' . $e->getMessage()]);
        $deploy->save();
        
        throw $e;
    }

    return $deploy;
}

    /**
     * Performs complete deployment process from ZIP
     * 1. Gets active domains
     * 2. Validates that domains exist
     * 3. Creates Deploy record
     * 4. Triggers job chain
     * 
     * @throws RuntimeException if no domains are configured
     */
   public function deployWithZip(int $projectId, string $projectName, string $zipFilePath): Deploy
    {
        Log::info('Iniciando proceso de despliegue desde ZIP', ['project' => $projectName]);

        $domains = $this->domainRepo->findActiveByProjectId($projectId);
        if ($domains->isEmpty()) {
            throw new RuntimeException('No active domains configured for this project.');
        }

        $deploymentPath = "www." . Str::slug($projectName);

        $deploy = Deploy::create([
            'project_id' => $projectId,
            'status' => 'pending',
            'source_type' => 'zip',
            'path' => $deploymentPath,
            'log_messages' => ['[' . now()->toTimeString() . '] Deployment started from ZIP file'],
        ]);

        try {
            Bus::chain([
                new ExtractZipJob($deploy, $zipFilePath, $deploymentPath),
                new PrepareStaticFilesJob($deploy, $deploymentPath, null),
                new ConfigureCaddyJob($deploy, $deploymentPath, $domains),
            ])->dispatch();

            Log::info("Jobs ZIP despachados para Deploy ID: {$deploy->id}");

        } catch (\Exception $e) {
            $deploy->status = 'failed';
            $deploy->log_messages = array_merge($deploy->log_messages, ['Error inicial: ' . $e->getMessage()]);
            $deploy->save();
            Storage::delete($zipFilePath);
            
            throw $e;
        }

        return $deploy;
    }

    /**
     * Updates deployment status
     */
    public function updateDeployStatus(Deploy $deploy, string $status, array $logMessages = []): Deploy
    {
        Log::info('Actualizando estado de despliegue', [
            'deploy_id' => $deploy->id,
            'status' => $status
        ]);

        $deploy->status = $status;
        if (!empty($logMessages)) {
            $deploy->log_messages = array_merge($deploy->log_messages ?? [], $logMessages);
        }
        $deploy->save();

        return $deploy;
    }

    /**
     * Marks deployment as completed
     */
    public function markAsCompleted(Deploy $deploy, array $logMessages = []): Deploy
    {
        return $this->updateDeployStatus($deploy, 'completed', $logMessages);
    }

    /**
     * Marks deployment as failed
     */
    public function markAsFailed(Deploy $deploy, string $error): Deploy
    {
        Log::error('Deployment failed', [
            'deploy_id' => $deploy->id,
            'error' => $error
        ]);

        return $this->updateDeployStatus($deploy, 'failed', ["Error: {$error}"]);
    }
}
