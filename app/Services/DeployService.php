<?php

namespace App\Services;

use App\Models\Deploy;
use App\Models\GitConfig;
use App\Jobs\GitHubFetchSourceCodeJob;
use App\Jobs\PrepareStaticFilesJob;
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
     * Realiza todo el proceso de despliegue desde GitHub
     * 1. Obtiene la configuración de Git
     * 2. Obtiene los dominios activos
     * 3. Valida que los datos sean válidos
     * 4. Crea el registro de Deploy
     * 5. Dispara la cadena de jobs
     * 
     * @throws RuntimeException si falta configuración o dominios
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
     * Realiza todo el proceso de despliegue desde ZIP
     * 1. Obtiene los dominios activos
     * 2. Valida que existan dominios
     * 3. Crea el registro de Deploy
     * 4. Dispara la cadena de jobs
     * 
     * @throws RuntimeException si no hay dominios configurados
     */
    public function deployWithZip(int $projectId, string $projectName): Deploy
    {
        Log::info('Iniciando proceso de despliegue desde ZIP', [
            'project_id' => $projectId,
            'project_name' => $projectName
        ]);

        // Obtener dominios activos
        $domains = $this->domainRepo->findActiveByProjectId($projectId);
        if ($domains->isEmpty()) {
            throw new RuntimeException('No hay dominios activos configurados para este proyecto.');
        }

        // Generate deployment path
        $deploymentPath = "www." . str_slug($projectName);

        // Crear registro de Deploy
        $deploy = Deploy::create([
            'project_id' => $projectId,
            'status' => 'pending',
            'source_type' => 'zip',
            'path' => $deploymentPath,
            'log_messages' => ['Despliegue iniciado desde archivo ZIP'],
        ]);

        Log::info('Registro de Deploy creado exitosamente', [
            'deploy_id' => $deploy->id,
            'project_id' => $projectId,
            'deployment_path' => $deploymentPath,
        ]);

        try {
            // Disparar cadena de jobs (sin GitHubFetchSourceCodeJob)
            Bus::chain([
                new PrepareStaticFilesJob($deploy, $deploymentPath),
                new ConfigureCaddyJob($deploy, $deploymentPath, $domains),
            ])->dispatch();

            Log::info('Cadena de jobs despachada exitosamente', [
                'deploy_id' => $deploy->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al disparar jobs de despliegue', [
                'deploy_id' => $deploy->id,
                'error' => $e->getMessage()
            ]);

            // Marcar deploy como fallido
            $this->markAsFailed($deploy, 'Error al iniciar los jobs de despliegue: ' . $e->getMessage());
            
            throw new RuntimeException('Error al iniciar los jobs de despliegue: ' . $e->getMessage());
        }

        return $deploy;
    }

    /**
     * Actualiza el estado de un despliegue
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
     * Marca un despliegue como completado
     */
    public function markAsCompleted(Deploy $deploy, array $logMessages = []): Deploy
    {
        return $this->updateDeployStatus($deploy, 'completed', $logMessages);
    }

    /**
     * Marca un despliegue como fallido
     */
    public function markAsFailed(Deploy $deploy, string $error): Deploy
    {
        Log::error('Despliegue fallido', [
            'deploy_id' => $deploy->id,
            'error' => $error
        ]);

        return $this->updateDeployStatus($deploy, 'failed', ["Error: {$error}"]);
    }
}
