<?php

namespace App\Traits;

use App\Models\Deploy;

/**
 * @property Deploy $deploy
 */
trait InteractsWithDeployLogs
{
    /**
     * Adds a message to the deploy log ensuring persistence of previous data.
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