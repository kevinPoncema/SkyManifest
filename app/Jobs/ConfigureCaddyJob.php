<?php

namespace App\Jobs;

use App\Models\Deploy;
use App\Services\CaddyService;
use App\Traits\InteractsWithDeployLogs;
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
    use InteractsWithDeployLogs;

    public function __construct(
        public Deploy $deploy,
        protected string $deploymentPath,
        protected Collection $domains
    ) {}

    public function handle(CaddyService $caddyService): void
    {
        $this->addLog("🌐 Configurando servidor web (Caddy)...");

        try {
            $basePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $fullPath = $basePath . '/' . $this->deploymentPath;
            $domainList = $this->domains->pluck('url')->toArray();

            $this->addLog("📝 Dominios: " . implode(', ', $domainList));
            
            $caddyService->syncDomains($domainList, $fullPath);

            $this->addLog("✅ Caddy configurado.");
            $this->addLog("🚀 ¡Despliegue finalizado con éxito!");
            
            $this->deploy->refresh();
            $this->deploy->status = 'success';
            $this->deploy->save();

        } catch (Exception $e) {
            $msg = "❌ Error en Caddy: " . $e->getMessage();
            Log::error($msg);
            $this->addLog($msg);

            $this->deploy->status = 'failed';
            $this->deploy->save();

            throw $e;
        }
    }
}