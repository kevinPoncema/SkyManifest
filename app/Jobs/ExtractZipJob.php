<?php

namespace App\Jobs;

use App\Models\Deploy;
use App\Traits\InteractsWithDeployLogs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Exception;
use RuntimeException;
use Illuminate\Support\Sleep;

class ExtractZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithDeployLogs;

    public function __construct(
        public Deploy $deploy,
        protected string $zipFilePath, // temp zip
        protected string $deploymentPath 
    ) {}

    public function handle(): void
    {
        $this->addLog("📦 Iniciando procesamiento del archivo ZIP...");
        
        $this->deploy->refresh();
        $this->deploy->status = 'processing';
        $this->deploy->save();

        try {
            $maxRetries = 3;
            $retryCount = 0;
            
            while ($retryCount < $maxRetries) {
                if (Storage::exists($this->zipFilePath)) {
                    break;
                }
                $retryCount++;
                if ($retryCount < $maxRetries) {
                    $this->addLog("⏳ Archivo no encontrado, reintentando ({$retryCount}/{$maxRetries})...");
                    Sleep::milliseconds(500);
                } else {
                    throw new RuntimeException("El archivo ZIP temporal no se encuentra después de {$maxRetries} intentos: {$this->zipFilePath}");
                }
            }
            
            $absoluteZipPath = Storage::path($this->zipFilePath);

            $basePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $targetPath = $basePath . '/' . $this->deploymentPath;

            $this->addLog("📂 Descomprimiendo en: $targetPath");

            // 4. Limpiar carpeta destino si existe (Estrategia de Sobreescritura Limpia)
            if (File::exists($targetPath)) {
                File::cleanDirectory($targetPath);
            } else {
                File::makeDirectory($targetPath, 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($absoluteZipPath) === TRUE) {
                $zip->extractTo($targetPath);
                $zip->close();
                $this->addLog("✅ Archivo descomprimido correctamente.");
            } else {
                throw new RuntimeException("No se pudo abrir el archivo ZIP. Puede estar corrupto.");
            }

            // cleaning temp file
            if (Storage::exists($this->zipFilePath)) {
                Storage::delete($this->zipFilePath);
                $this->addLog("🧹 Archivo ZIP temporal eliminado.");
            }

        } catch (Exception $e) {
            $msg = "❌ Error extrayendo ZIP: " . $e->getMessage();
            $this->addLog($msg);
            $this->deploy->status = 'failed';
            $this->deploy->save();
            
            // try to delete the temp file if it still exists
            if (Storage::exists($this->zipFilePath)) {
                Storage::delete($this->zipFilePath);
            }
            
            throw $e;
        }
    }
}