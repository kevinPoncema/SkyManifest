<?php

namespace App\Traits;

use App\Models\Deploy;

/**
 * @property Deploy $deploy
 */
trait InteractsWithDeployLogs
{
    /**
     * Agrega un mensaje al log del deploy asegurando la persistencia de datos previos.
     */
    protected function addLog(string $message): void
    {
        // 1. CRÍTICO: Recargar el modelo desde la BD para obtener logs de jobs anteriores
        $this->deploy->refresh();

        // 2. Obtener logs actuales
        $currentLogs = $this->deploy->log_messages ?? [];
        
        // 3. Agregar nuevo mensaje con timestamp
        $currentLogs[] = '[' . now()->toTimeString() . '] ' . $message;
        
        // 4. Guardar
        $this->deploy->log_messages = $currentLogs;
        $this->deploy->save();
    }
}