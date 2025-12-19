<?php

namespace App\Jobs;

use App\Models\Deploy;
use App\Models\GitConfig;
use App\Services\GitHubServices;
use App\Traits\InteractsWithDeployLogs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GitHubFetchSourceCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithDeployLogs;

    public function __construct(
        public Deploy $deploy,
        protected GitConfig $gitConfig,
        protected string $deploymentPath
    ) {}

    public function handle(GitHubServices $gitHubService): void
    {
        $this->addLog("🚀 Iniciando descarga de código desde GitHub...");
        
        $this->deploy->refresh();
        $this->deploy->status = 'processing';
        $this->deploy->save();

        try {
            $basePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $fullPath = $basePath . '/' . $this->deploymentPath;
            $targetBranch = $this->gitConfig->branch ?? 'main';

            $this->addLog("📂 Ruta destino: " . $fullPath);
            $this->addLog("🔗 Repositorio: " . $this->gitConfig->repository_url);
            $this->addLog("🌿 Rama: " . $targetBranch);
            $gitHubService->cloneOrUpdate(
                $this->gitConfig->repository_url,
                $fullPath,
                $targetBranch
            );

            $this->addLog("✅ Código descargado/actualizado correctamente.");

        } catch (Exception $e) {
            $msg = "❌ Error crítico en GitHub Job: " . $e->getMessage();
            Log::error($msg);
            $this->addLog($msg);
            
            $this->deploy->status = 'failed';
            $this->deploy->save();
            
            throw $e; 
        }
    }
}