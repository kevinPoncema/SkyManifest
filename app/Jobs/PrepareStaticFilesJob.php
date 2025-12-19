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
        // 🟢 CAMBIO CLAVE: Permitimos null para despliegues vía ZIP
        protected ?GitConfig $gitConfig = null
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

            // 🟢 LÓGICA SEGURA: Validación de nulidad
            // Si hay GitConfig, usamos su configuración. Si es NULL (ZIP), usamos cadena vacía (raíz).
            $relativeBaseDir = '';
            
            if ($this->gitConfig) {
                $relativeBaseDir = trim($this->gitConfig->base_directory ?? '', '/');
            }

            // Solo ejecutamos la promoción si hay un directorio base definido diferente a la raíz
            if (!empty($relativeBaseDir)) {
                $this->promoteBaseDirectory($projectRootPath, $relativeBaseDir);
            }

            // Limpiar basura y archivos no permitidos
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
     * Mueve el contenido de una subcarpeta (ej: /dist) a la raíz.
     */
    protected function promoteBaseDirectory(string $rootPath, string $subDir): void
    {
        $sourcePath = $rootPath . '/' . $subDir;
        $this->addLog("📂 Directorio base configurado: /$subDir");

        if (!File::isDirectory($sourcePath)) {
            // Mensaje más amigable para el usuario
            throw new RuntimeException("El directorio '$subDir' no existe. Verifica tu configuración o que el build se haya generado correctamente.");
        }

        // Usamos un nombre temporal único para evitar colisiones
        $tempPath = $rootPath . '_temp_move_' . uniqid();
        
        // 1. Mover contenido útil a temporal
        File::moveDirectory($sourcePath, $tempPath);
        
        // 2. Limpiar todo lo demás en la raíz (código fuente, node_modules, etc)
        File::cleanDirectory($rootPath);
        
        // 3. Devolver contenido útil a la raíz
        File::copyDirectory($tempPath, $rootPath);
        
        // 4. Borrar temporal
        File::deleteDirectory($tempPath);

        $this->addLog("📦 Contenido promovido a la raíz.");
    }

    /**
     * Elimina todo lo que no sea necesario para producción.
     */
    protected function cleanNonStaticFiles(string $path): void
    {
        $this->addLog("🛡️ Eliminando archivos no estáticos...");
        
        $files = File::allFiles($path);
        $deletedCount = 0;
        
        foreach ($files as $file) {
            // Verificar extensión contra lista blanca
            if (!in_array(strtolower($file->getExtension()), self::ALLOWED_EXTENSIONS)) {
                File::delete($file->getRealPath());
                $deletedCount++;
            }
        }

        // Carpetas a eliminar incondicionalmente
        // Agregamos __MACOSX que es común en ZIPs subidos desde Mac
        $dirsToDelete = ['.git', '.github', '.vscode', 'node_modules', 'vendor', '__MACOSX'];
        
        foreach ($dirsToDelete as $dir) {
            $fullDirPath = $path . '/' . $dir;
            if (File::isDirectory($fullDirPath)) {
                File::deleteDirectory($fullDirPath);
            }
        }
    }
}