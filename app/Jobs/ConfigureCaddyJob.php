<?php

namespace App\Jobs;

use App\Models\Deploy;
use App\Services\CaddyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Exception;

class ConfigureCaddyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param Deploy $deploy El modelo del despliegue
     * @param string $deploymentPath Ruta relativa (ej: www.mi-proyecto)
     * @param Collection $domains Colección de modelos Domain
     */
    public function __construct(
        protected Deploy $deploy,
        protected string $deploymentPath,
        protected Collection $domains
    ) {}

    public function handle(CaddyService $caddyService): void
    {
        $this->addLog("🌐 Iniciando configuración de servidor web (Caddy)...");

        try {
            $basePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $fullPath = $basePath . '/' . $this->deploymentPath;
            $domainList = $this->domains->pluck('url')->toArray();

            $this->addLog("📝 Dominios a configurar: " . implode(', ', $domainList));
            $this->addLog("📂 Apuntando a ruta: " . $fullPath);

            $caddyService->syncDomains($domainList, $fullPath);

            $this->addLog("✅ Configuración de Caddy completada correctamente.");
            $this->addLog("🚀 ¡Despliegue finalizado con éxito!");
            $this->deploy->status = 'success';
            $this->deploy->save();

        } catch (Exception $e) {
            $msg = "❌ Error configurando Caddy: " . $e->getMessage();
            Log::error($msg);
            $this->addLog($msg);

            // Si falla aquí, todo el deploy falla
            $this->deploy->status = 'failed';
            $this->deploy->save();

            throw $e;
        }
    }

    /**
     * Helper para logs con persistencia explícita
     */
    protected function addLog(string $message): void
    {
        $currentLogs = $this->deploy->log_messages ?? [];
        $currentLogs[] = '[' . now()->toTimeString() . '] ' . $message;
        
        $this->deploy->log_messages = $currentLogs;
        $this->deploy->save();
    }
}