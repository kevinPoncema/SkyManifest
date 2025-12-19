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
        // Obtiene la URL del .env (ej: CADDY_HOST=http://caddy:2019)
        $this->baseUrl = rtrim(config('services.caddy.host', env('CADDY_HOST', 'http://caddy:2019')), '/');
    }

    /**
     * ORQUESTADOR PRINCIPAL: Sincroniza los dominios de una carpeta.
     * 1. Busca qué dominios apuntan YA a esa carpeta en Caddy.
     * 2. Borra los que sobran (estaban antes pero ya no están en la lista nueva).
     * 3. Crea o actualiza los que deben estar.
     * * @param array $domainList Lista de strings (ej: ['miweb.com', 'www.miweb.com'])
     * @param string $path Ruta física de la carpeta (ej: /var/www/sites/1)
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
     * Agrega o Actualiza un dominio.
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

        $response = Http::put("{$this->baseUrl}/id/{$routeId}", $config);

        if ($response->failed()) {
            Log::error("Fallo al agregar dominio $domain", ['body' => $response->body()]);
            throw new RuntimeException("Caddy Error: " . $response->body());
        }

        return true;
    }

    /**
     * Elimina un dominio.
     */
    public function removeDomain(string $domain): bool
    {
        $routeId = $this->getRouteId($domain);
        $response = Http::delete("{$this->baseUrl}/id/{$routeId}");

        if ($response->failed() && $response->status() !== 404) {
            throw new RuntimeException("Error eliminando dominio: " . $response->body());
        }

        return true;
    }

    /**
     * Helper: Busca en la config de Caddy todas las rutas que apunten a $path.
     * Retorna un array de dominios (ej: ['old-site.com']).
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
            // Estructura: handle[0] -> routes[0] -> handle[0] -> root
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

    private function getRouteId(string $domain): string
    {
        return 'site_' . str_replace('.', '_', $domain);
    }
}