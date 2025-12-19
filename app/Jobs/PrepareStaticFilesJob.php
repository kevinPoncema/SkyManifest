<?php

namespace App\Jobs;

use App\Models\Deploy;
use App\Models\GitConfig;
use App\Traits\InteractsWithDeployLogs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;
use RuntimeException;

class PrepareStaticFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithDeployLogs;

    protected const ALLOWED_EXTENSIONS = [
        'html', 'htm', 'css', 'js', 'mjs', 'map', 'json', 'xml', 'txt',
        'ico', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'bmp',
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        'mp4', 'webm', 'ogv', 'mp3', 'wav', 'ogg', 'pdf'
    ];

    public function __construct(
        public Deploy $deploy,
        protected string $deploymentPath,
        protected GitConfig $gitConfig
    ) {}

    public function handle(): void
    {
        $this->addLog("🧹 Iniciando preparación de archivos estáticos...");

        try {
            $baseStoragePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $projectRootPath = $baseStoragePath . '/' . $this->deploymentPath;

            if (!File::exists($projectRootPath)) {
                throw new RuntimeException("El directorio del proyecto no existe: $projectRootPath");
            }

            // Mover archivos si hay un base_directory
            $relativeBaseDir = trim($this->gitConfig->base_directory ?? '', '/');
            if (!empty($relativeBaseDir)) {
                $this->promoteBaseDirectory($projectRootPath, $relativeBaseDir);
            }

            // Limpiar basura
            $this->cleanNonStaticFiles($projectRootPath);

            $this->addLog("✨ Archivos estáticos preparados correctamente.");

        } catch (Exception $e) {
            $msg = "❌ Error preparando archivos: " . $e->getMessage();
            Log::error($msg);
            $this->addLog($msg);
            
            $this->deploy->status = 'failed';
            $this->deploy->save();
            
            throw $e;
        }
    }

    protected function promoteBaseDirectory(string $rootPath, string $subDir): void
    {
        $sourcePath = $rootPath . '/' . $subDir;
        $this->addLog("📂 Directorio base configurado: /$subDir");

        if (!File::isDirectory($sourcePath)) {
            throw new RuntimeException("El directorio '$subDir' no existe.");
        }

        $tempPath = $rootPath . '_temp_' . uniqid();
        File::moveDirectory($sourcePath, $tempPath);
        File::cleanDirectory($rootPath);
        File::copyDirectory($tempPath, $rootPath);
        File::deleteDirectory($tempPath);

        $this->addLog("📦 Contenido promovido a la raíz.");
    }

    protected function cleanNonStaticFiles(string $path): void
    {
        $this->addLog("🛡️ Eliminando archivos no estáticos...");
        $files = File::allFiles($path);
        
        foreach ($files as $file) {
            if (!in_array(strtolower($file->getExtension()), self::ALLOWED_EXTENSIONS)) {
                File::delete($file->getRealPath());
            }
        }

        $dirs = ['.git', '.github', '.vscode', 'node_modules', 'vendor'];
        foreach ($dirs as $dir) {
            if (File::isDirectory($path . '/' . $dir)) File::deleteDirectory($path . '/' . $dir);
        }
    }
}