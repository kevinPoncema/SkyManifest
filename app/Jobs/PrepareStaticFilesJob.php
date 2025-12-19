<?php

namespace App\Jobs;

use App\Models\Deploy;
use App\Models\GitConfig;
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

    /**
     * Lista blanca de extensiones permitidas para servir estáticamente.
     * Todo lo que no esté aquí, será eliminado.
     */
    protected const ALLOWED_EXTENSIONS = [
        // Web
        'html', 'htm', 'css', 'js', 'mjs', 'map', 'json', 'xml', 'txt',
        // Imágenes
        'ico', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'bmp',
        // Fuentes
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        // Media
        'mp4', 'webm', 'ogv', 'mp3', 'wav', 'ogg',
        // Docs
        'pdf'
    ];

    public function __construct(
        protected Deploy $deploy,
        protected string $deploymentPath, // Ruta relativa (ej: www.proyecto-slug)
        protected GitConfig $gitConfig
    ) {}

    public function handle(): void
    {
        $this->addLog("🧹 Iniciando preparación y limpieza de archivos estáticos...");
        $this->deploy->status = 'processing';
        $this->deploy->save();

        try {
            // 1. Construir ruta absoluta raíz del proyecto
            $baseStoragePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $projectRootPath = $baseStoragePath . '/' . $this->deploymentPath;

            if (!File::exists($projectRootPath)) {
                throw new RuntimeException("El directorio del proyecto no existe: $projectRootPath");
            }

            // 2. Resolver el directorio base configurado (ej: /dist, /build o /)
            $relativeBaseDir = trim($this->gitConfig->base_directory ?? '', '/');
            
            // Si hay un directorio base definido y no es la raíz...
            if (!empty($relativeBaseDir)) {
                $this->promoteBaseDirectory($projectRootPath, $relativeBaseDir);
            }

            // 3. Escanear y eliminar archivos no estáticos
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

    /**
     * Mueve el contenido del subdirectorio (ej: /dist) a la raíz y borra el resto.
     */
    protected function promoteBaseDirectory(string $rootPath, string $subDir): void
    {
        $sourcePath = $rootPath . '/' . $subDir;

        $this->addLog("📂 Directorio base configurado: /$subDir");

        if (!File::isDirectory($sourcePath)) {
            throw new RuntimeException("El directorio base configurado '/$subDir' no existe en el repositorio. ¿Olvidaste compilar o la ruta es incorrecta?");
        }

        // Paso A: Mover el contenido útil a una carpeta temporal fuera del árbol sucio
        $tempPath = $rootPath . '_temp_build_' . uniqid();
        File::moveDirectory($sourcePath, $tempPath);

        // Paso B: Limpiar la raíz completamente (borra src, node_modules, .git, etc.)
        // Esto cumple con "eliminar todo lo que no esté dentro del directorio base"
        File::cleanDirectory($rootPath);

        // Paso C: Devolver los archivos desde el temporal a la raíz
        File::copyDirectory($tempPath, $rootPath);
        
        // Paso D: Eliminar el temporal
        File::deleteDirectory($tempPath);

        $this->addLog("📦 Contenido de /$subDir movido a la raíz del despliegue.");
    }

    /**
     * Elimina recursivamente cualquier archivo cuya extensión no esté en la lista blanca.
     */
    protected function cleanNonStaticFiles(string $path): void
    {
        $this->addLog("🛡️ Eliminando archivos no estáticos y código fuente...");

        // Obtenemos todos los archivos de forma recursiva
        $files = File::allFiles($path);
        $deletedCount = 0;

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());

            // Si la extensión NO está en la lista permitida, se borra.
            if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
                File::delete($file->getRealPath());
                $deletedCount++;
            }
        }

        // También limpiamos carpetas vacías o específicas que hayan quedado
        $directoriesToDelete = ['.git', '.github', '.vscode', 'node_modules', 'vendor'];
        foreach ($directoriesToDelete as $dir) {
            if (File::isDirectory($path . '/' . $dir)) {
                File::deleteDirectory($path . '/' . $dir);
            }
        }

        $this->addLog("🗑️ Se eliminaron $deletedCount archivos no aptos para navegador (php, ts, map, env, etc).");
    }

    protected function addLog(string $message): void
    {
        $currentLogs = $this->deploy->log_messages ?? [];
        $currentLogs[] = '[' . now()->toTimeString() . '] ' . $message;
        $this->deploy->log_messages = $currentLogs;
        $this->deploy->save();
    }
}