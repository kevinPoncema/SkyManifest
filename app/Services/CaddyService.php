<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CaddyService
{
    protected string $baseUrl;

    public function __construct()
    {
        // get default url
        $this->baseUrl = rtrim(config('services.caddy.host', env('CADDY_HOST', 'http://caddy:2019')), '/');
    }

    /**
     * PRIMARY ORCHESTRATOR: Synchronizes domains for a folder.
     */
    public function syncDomains(array $domainList, string $path): void
    {
        Log::info("Iniciando sincronización de dominios para: $path", ['domains' => $domainList]);
        $currentDomains = $this->getDomainsPointingToPath($path);
        $domainsToDelete = array_diff($currentDomains, $domainList);
        foreach ($domainsToDelete as $domain) {
            Log::info("Eliminando dominio obsoleto: $domain");
            $this->removeDomain($domain);
        }

        foreach ($domainList as $domain) {
            $this->addDomain($domain, $path);
        }  
        
        Log::info("Sincronización finalizada.");
    }

    /**
     * Adds or Updates a domain (Intelligent UPSERT Logic).
     */
    public function addDomain(string $domain, string $path): bool
    {
        $routeId = $this->getRouteId($domain);
        
        $config = [
            "@id" => $routeId,
            "match" => [["host" => [$domain]]],
            "handle" => [[
                "handler" => "subroute",
                "routes" => [[
                    "handle" => [[
                        "handler" => "file_server",
                        "root" => $path
                    ]]
                ]]
            ]]
        ];

        if ($this->idExists($routeId)) {
            Log::info("Actualizando configuración Caddy para: $domain");
            $response = Http::put("{$this->baseUrl}/id/{$routeId}", $config);
        } else {
            Log::info("Creando nueva configuración Caddy para: $domain");
            $response = Http::post("{$this->baseUrl}/config/apps/http/servers/srv0/routes", $config);
        }

        if ($response->failed()) {
            Log::error("Fallo al configurar Caddy para $domain", ['body' => $response->body()]);
            throw new RuntimeException("Caddy Error ({$response->status()}): " . $response->body());
        }

        return true;
    }

    /**
     * Removes a domain from the configuration.
     */
    public function removeDomain(string $domain): bool
    {
        $routeId = $this->getRouteId($domain);
        
        $response = Http::delete("{$this->baseUrl}/id/{$routeId}");
        if ($response->failed() && $response->status() !== 404) {
            throw new RuntimeException("Error eliminando dominio de Caddy: " . $response->body());
        }

        return true;
    }

    /**
     * Helper: Finds which domains currently point to a physical folder.
     */
    private function getDomainsPointingToPath(string $path): array
    {
        $response = Http::get("{$this->baseUrl}/config/apps/http/servers/srv0/routes");

        if ($response->failed()) {
            return [];
        }

        $routes = $response->json();
        if (!is_array($routes)) return [];

        $foundDomains = [];

        foreach ($routes as $route) {
            $routeRoot = $route['handle'][0]['routes'][0]['handle'][0]['root'] ?? null;

            if ($routeRoot === $path) {
                $hosts = $route['match'][0]['host'] ?? [];
                foreach ($hosts as $host) {
                    $foundDomains[] = $host;
                }
            }
        }

        return $foundDomains;
    }

    /**
     * Checks if an ID already exists in the Caddy configuration.
     */
    private function idExists(string $routeId): bool
    {
        $response = Http::get("{$this->baseUrl}/id/{$routeId}");
        return $response->successful();
    }

    /**
     * Generates a unique and valid ID for Caddy based on the domain.
     */
    private function getRouteId(string $domain): string
    {
        return 'site_' . str_replace('.', '_', $domain);
    }
}