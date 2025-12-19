<?php

namespace App\Jobs;

use App\Models\Deploy;
use App\Models\GitConfig;
use App\Services\GitHubServices;
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

    /**
     * Create a new job instance.
     * * @param Deploy $deploy El modelo del despliegue actual
     * @param GitConfig $gitConfig La configuración del repo
     * @param string $deploymentPath El nombre de la carpeta relativa (ej: "www.mi-proyecto")
     */
    public function __construct(
        protected Deploy $deploy,
        protected GitConfig $gitConfig,
        protected string $deploymentPath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GitHubServices $gitHubService): void
    {
        $this->addLog("🚀 Iniciando descarga de código desde GitHub...");
        $this->deploy->update(['status' => 'processing']);

        try {
            $basePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            // Ruta Final: /var/www/sites/www.mi-proyecto
            $fullPath = $basePath . '/' . $this->deploymentPath;
            $this->addLog("📂 Ruta destino: " . $fullPath);
            $this->addLog("🔗 Repositorio: " . $this->gitConfig->repository_url);

            $gitHubService->cloneOrUpdate(
                $this->gitConfig->repository_url,
                $fullPath
            );

            $this->addLog("✅ Código descargado/actualizado correctamente.");

        } catch (Exception $e) {
            $errorMessage = "❌ Error crítico en GitHub Job: " . $e->getMessage();
            Log::error($errorMessage);
            $this->addLog($errorMessage);
            $this->deploy->update(['status' => 'failed']);
            throw $e; 
        }
    }

    /**
     * Helper para agregar mensajes al log JSON del modelo Deploy sin borrar los anteriores.
     */
    protected function addLog(string $message): void
    {
        $currentLogs = $this->deploy->log_messages ?? [];
        
        // Añadimos timestamp para debugear mejor
        $currentLogs[] = '[' . now()->toTimeString() . '] ' . $message;
        
        $this->deploy->update(['log_messages' => $currentLogs]);
    }
}