<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Services\DomainService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(
        private DomainService $domainService,
        private ProjectService $projectService
    ) {}

    /**
     * Display a listing of domains for a project.
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        $domains = $this->domainService->getByProject($projectId);

        return response()->json([
            'success' => true,
            'data' => $domains,
            'message' => 'Dominios obtenidos exitosamente.'
        ]);
    }

    /**
     * Store a newly created domain.
     */
    public function store(StoreDomainRequest $request, int $projectId): JsonResponse
    {
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        $domain = $this->domainService->create($projectId, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $domain,
            'message' => 'Dominio creado exitosamente.'
        ], 201);
    }

    /**
     * Remove the specified domain.
     */
    public function destroy(Request $request, int $projectId, int $domainId): JsonResponse
    {
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        try {
            $this->domainService->delete($domainId);

            return response()->json([
                'success' => true,
                'message' => 'Dominio eliminado exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el dominio.'
            ], 500);
        }
    }
}
