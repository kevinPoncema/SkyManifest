<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GitHubServices
{
    /**
     * Clona un repositorio. Los archivos quedarán DIRECTAMENTE en $destinationPath.
     * No se crean subcarpetas con el nombre del repo.
     */
    public function cloneRepository(string $repoUrl, string $destinationPath): void
    {
        // 1. Git clone falla si la carpeta existe y tiene cosas.
        // Si existe y no es un repo (o es basura de un intento fallido), la limpiamos.
        if (File::exists($destinationPath) && !$this->isGitRepository($destinationPath)) {
            Log::warning("La carpeta existe pero no es un repo válido, limpiando para clonar...", ['path' => $destinationPath]);
            File::deleteDirectory($destinationPath);
        }

        // 2. Aseguramos que el PADRE exista, pero dejamos que Git cree la carpeta final
        // para evitar conflictos de "directory exists and is not empty".
        $this->ensureParentDirectoryExists($destinationPath);

        $command = [
            'git', 'clone',
            '--depth', '1',          // Solo el último commit (más rápido)
            '--single-branch',       // Solo la rama default
            $repoUrl,
            $destinationPath         // <--- ESTO asegura que no se creen subcarpetas
        ];

        // 3. Aumentamos el timeout a 300s (5 min) para repos grandes
        $process = Process::timeout(300)->run($command);

        if (!$process->successful()) {
            Log::error('Error crítico al clonar repositorio', [
                'repo' => $repoUrl,
                'destination' => $destinationPath,
                'error' => $process->errorOutput()
            ]);
            // Limpiamos si falló para no dejar carpetas corruptas
            File::deleteDirectory($destinationPath);
            throw new RuntimeException("Error al clonar repositorio: " . $process->errorOutput());
        }

        Log::info('Repositorio clonado exitosamente', [
            'repo' => $repoUrl,
            'destination' => $destinationPath
        ]);
    }

    public function updateRepository(string $repositoryPath): void
    {
        if (!$this->isGitRepository($repositoryPath)) {
            throw new RuntimeException("Ruta inválida para update: {$repositoryPath}");
        }

        // git reset --hard asegura que si hubo cambios locales (innecesarios), se descarten
        // y quede idéntico al remoto. Es más seguro para deploys automáticos.
        $command = 'git fetch origin && git reset --hard origin/HEAD';
        
        $process = Process::path($repositoryPath)->timeout(300)->run($command);

        if (!$process->successful()) {
            Log::error('Error al actualizar repositorio', [
                'path' => $repositoryPath,
                'error' => $process->errorOutput()
            ]);
            throw new RuntimeException("Error al actualizar: " . $process->errorOutput());
        }

        Log::info('Repositorio actualizado', ['path' => $repositoryPath]);
    }

    public function isGitRepository(string $path): bool
    {
        return is_dir($path . '/.git');
    }

    public function getRemoteUrl(string $repositoryPath): string
    {
        if (!$this->isGitRepository($repositoryPath)) {
            return '';
        }

        $process = Process::path($repositoryPath)->run(['git', 'config', '--get', 'remote.origin.url']);

        if (!$process->successful()) {
            return '';
        }

        return trim($process->output());
    }

    public function repositoryExistsAndMatches(string $repositoryPath, string $expectedUrl): bool
    {
        if (!$this->isGitRepository($repositoryPath)) {
            return false;
        }

        $currentUrl = $this->getRemoteUrl($repositoryPath);
        
        // Normalización simple para comparar (quitar .git y slashes finales)
        $normalize = fn($url) => rtrim(str_replace('.git', '', $url), '/');

        return $normalize($currentUrl) === $normalize($expectedUrl);
    }

    public function cloneOrUpdate(string $repoUrl, string $destinationPath): void
    {
        // Caso 1: Ya existe y es el mismo repo -> Actualizar
        if ($this->repositoryExistsAndMatches($destinationPath, $repoUrl)) {
            Log::info('Repositorio coincide, actualizando...', ['path' => $destinationPath]);
            $this->updateRepository($destinationPath);
            return;
        }

        // Caso 2: Existe algo, pero es otro repo o está corrupto -> Borrar y Clonar
        if (File::exists($destinationPath)) {
            Log::warning('Conflicto de repositorio o URL, recreando carpeta...', ['path' => $destinationPath]);
            // Usamos la función nativa de Laravel, mucho más segura que unlink/rmdir recursivo manual
            File::deleteDirectory($destinationPath);
        }

        // Caso 3: No existe -> Clonar
        $this->cloneRepository($repoUrl, $destinationPath);
    }

    /**
     * Asegura que la carpeta contenedora exista (ej: /var/www/sites)
     * Para que git clone pueda crear la carpeta final (ej: /var/www/sites/proyecto1)
     */
    private function ensureParentDirectoryExists(string $path): void
    {
        $parent = dirname($path);
        if (!File::exists($parent)) {
            File::makeDirectory($parent, 0755, true);
        }
    }
}