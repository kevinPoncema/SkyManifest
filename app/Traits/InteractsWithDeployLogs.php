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
        $this->deploy->refresh();
        $currentLogs = $this->deploy->log_messages ?? [];
        $currentLogs[] = '[' . now()->toTimeString() . '] ' . $message;
        $this->deploy->log_messages = $currentLogs;
        $this->deploy->save();
    }
}