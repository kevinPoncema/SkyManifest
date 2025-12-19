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
use Illuminate\Support\Facades\Storage; // Importante para manejar el ZIP temporal
use ZipArchive;
use Exception;
use RuntimeException;

class ExtractZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithDeployLogs;

    public function __construct(
        public Deploy $deploy,
        protected string $zipFilePath, // Ruta relativa en storage/app (ej: temp_zips/xyz.zip)
        protected string $deploymentPath // Ruta destino final (ej: www.mi-proyecto)
    ) {}

    public function handle(): void
    {
        $this->addLog("📦 Iniciando procesamiento del archivo ZIP...");
        
        $this->deploy->refresh();
        $this->deploy->status = 'processing';
        $this->deploy->save();

        try {
            // 1. Localizar el archivo ZIP en el disco
            // storage_path('app/...') nos da la ruta absoluta en el sistema
            $absoluteZipPath = storage_path('app/' . $this->zipFilePath);

            if (!file_exists($absoluteZipPath)) {
                throw new RuntimeException("El archivo ZIP temporal no se encuentra: $absoluteZipPath");
            }

            // 2. Definir ruta de destino (Volumen compartido)
            $basePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $targetPath = $basePath . '/' . $this->deploymentPath;

            $this->addLog("📂 Descomprimiendo en: $targetPath");

            // 3. Limpiar carpeta destino si existe (Estrategia de Sobreescritura Limpia)
            if (File::exists($targetPath)) {
                File::cleanDirectory($targetPath);
            } else {
                File::makeDirectory($targetPath, 0755, true);
            }

            // 4. Descomprimir
            $zip = new ZipArchive;
            if ($zip->open($absoluteZipPath) === TRUE) {
                $zip->extractTo($targetPath);
                $zip->close();
                $this->addLog("✅ Archivo descomprimido correctamente.");
            } else {
                throw new RuntimeException("No se pudo abrir el archivo ZIP. Puede estar corrupto.");
            }

            // 5. Limpieza del archivo temporal (Ya no lo necesitamos)
            // Usamos unlink para borrar el ZIP de storage/app/temp_zips
            if (unlink($absoluteZipPath)) {
                $this->addLog("🧹 Archivo ZIP temporal eliminado.");
            }

        } catch (Exception $e) {
            $msg = "❌ Error extrayendo ZIP: " . $e->getMessage();
            $this->addLog($msg);
            $this->deploy->status = 'failed';
            $this->deploy->save();
            
            // Intentamos borrar el zip aunque falle, para no llenar el disco
            if (isset($absoluteZipPath) && file_exists($absoluteZipPath)) {
                unlink($absoluteZipPath);
            }
            
            throw $e;
        }
    }
}