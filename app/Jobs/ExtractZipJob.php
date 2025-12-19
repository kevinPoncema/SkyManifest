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
            // 1. Verificar que el archivo ZIP exista (con reintentos)
            // En Docker, a veces hay delay en sincronización de archivos
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
            
            // 2. Localizar el archivo ZIP usando Storage
            $absoluteZipPath = Storage::path($this->zipFilePath);

            // 3. Definir ruta de destino (Volumen compartido)
            $basePath = rtrim(env('DEPLOYMENT_PATH', '/var/www/sites'), '/');
            $targetPath = $basePath . '/' . $this->deploymentPath;

            $this->addLog("📂 Descomprimiendo en: $targetPath");

            // 4. Limpiar carpeta destino si existe (Estrategia de Sobreescritura Limpia)
            if (File::exists($targetPath)) {
                File::cleanDirectory($targetPath);
            } else {
                File::makeDirectory($targetPath, 0755, true);
            }

            // 5. Descomprimir
            $zip = new ZipArchive;
            if ($zip->open($absoluteZipPath) === TRUE) {
                $zip->extractTo($targetPath);
                $zip->close();
                $this->addLog("✅ Archivo descomprimido correctamente.");
            } else {
                throw new RuntimeException("No se pudo abrir el archivo ZIP. Puede estar corrupto.");
            }

            // 6. Limpieza del archivo temporal (Ya no lo necesitamos)
            // Usar Storage para eliminar de forma segura
            if (Storage::exists($this->zipFilePath)) {
                Storage::delete($this->zipFilePath);
                $this->addLog("🧹 Archivo ZIP temporal eliminado.");
            }

        } catch (Exception $e) {
            $msg = "❌ Error extrayendo ZIP: " . $e->getMessage();
            $this->addLog($msg);
            $this->deploy->status = 'failed';
            $this->deploy->save();
            
            // Intentamos borrar el zip aunque falle, para no llenar el disco
            if (Storage::exists($this->zipFilePath)) {
                Storage::delete($this->zipFilePath);
            }
            
            throw $e;
        }
    }
}