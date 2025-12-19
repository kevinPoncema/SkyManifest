<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GitHubServices
{
    /**
     * Clona una rama específica.
     */
    public function cloneRepository(string $repoUrl, string $destinationPath, string $branch): void
    {
        // 1. Limpieza preventiva
        if (File::exists($destinationPath) && !$this->isGitRepository($destinationPath)) {
            Log::warning("Carpeta corrupta detectada, limpiando...", ['path' => $destinationPath]);
            File::deleteDirectory($destinationPath);
        }

        $this->ensureParentDirectoryExists($destinationPath);

        $command = [
            'git', 'clone',
            '--depth', '1',
            '--branch', $branch,
            '--single-branch',
            $repoUrl,
            $destinationPath
        ];

        $process = Process::timeout(300)->run($command);

        if (!$process->successful()) {
            Log::error('Error crítico al clonar repositorio', [
                'repo' => $repoUrl,
                'branch' => $branch,
                'error' => $process->errorOutput()
            ]);
            File::deleteDirectory($destinationPath);
            throw new RuntimeException("Error al clonar repositorio: " . $process->errorOutput());
        }

        Log::info('Repositorio clonado exitosamente', [
            'repo' => $repoUrl,
            'branch' => $branch
        ]);
    }

    /**
     * Actualiza la rama específica.
     */
    public function updateRepository(string $repositoryPath, string $branch): void
    {
        if (!$this->isGitRepository($repositoryPath)) {
            throw new RuntimeException("Ruta inválida para update: {$repositoryPath}");
        }

        $command = "git fetch origin $branch && git reset --hard origin/$branch";
        
        $process = Process::path($repositoryPath)->timeout(300)->run($command);

        if (!$process->successful()) {
            Log::error('Error al actualizar repositorio', [
                'path' => $repositoryPath,
                'branch' => $branch,
                'error' => $process->errorOutput()
            ]);
            throw new RuntimeException("Error al actualizar: " . $process->errorOutput());
        }

        Log::info('Repositorio actualizado', ['path' => $repositoryPath, 'branch' => $branch]);
    }

    /**
     * Orquestador inteligente: Decide si clonar, actualizar o re-clonar (si cambió la rama/url).
     */
    public function cloneOrUpdate(string $repoUrl, string $destinationPath, string $branch): void
    {
        if ($this->isGitRepository($destinationPath)) {
            
            $urlMatches = $this->repositoryMatchesUrl($destinationPath, $repoUrl);
            $branchMatches = $this->repositoryMatchesBranch($destinationPath, $branch);

            if ($urlMatches && $branchMatches) {
                Log::info('Repositorio y rama coinciden, actualizando...', ['path' => $destinationPath]);
                $this->updateRepository($destinationPath, $branch);
                return;
            }

            Log::warning('Cambio de configuración detectado (URL o Rama diferente). Re-clonando...', [
                'expected_url' => $repoUrl,
                'expected_branch' => $branch
            ]);
            File::deleteDirectory($destinationPath);
        }
        
        elseif (File::exists($destinationPath)) {
            File::deleteDirectory($destinationPath);
        }

        $this->cloneRepository($repoUrl, $destinationPath, $branch);
    }


    public function isGitRepository(string $path): bool
    {
        return is_dir($path . '/.git');
    }

    private function repositoryMatchesUrl(string $path, string $expectedUrl): bool
    {
        $process = Process::path($path)->run(['git', 'config', '--get', 'remote.origin.url']);
        if (!$process->successful()) return false;
        
        $currentUrl = trim($process->output());
        $normalize = fn($u) => rtrim(str_replace('.git', '', $u), '/');
        
        return $normalize($currentUrl) === $normalize($expectedUrl);
    }

    private function repositoryMatchesBranch(string $path, string $expectedBranch): bool
    {
        $process = Process::path($path)->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        if (!$process->successful()) return false;

        $currentBranch = trim($process->output());
        return $currentBranch === $expectedBranch;
    }

    private function ensureParentDirectoryExists(string $path): void
    {
        $parent = dirname($path);
        if (!File::exists($parent)) {
            File::makeDirectory($parent, 0755, true);
        }
    }
}