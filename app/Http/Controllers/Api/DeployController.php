<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeployService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeployController extends Controller
{
    public function __construct(
        private DeployService $deployService,
        private ProjectService $projectService
    ) {}

    /**
     * Display deployment history for a project.
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

        $deploys = $this->deployService->getHistoryByProject($projectId);

        return response()->json([
            'success' => true,
            'data' => $deploys,
            'message' => 'Historial de despliegues obtenido exitosamente.'
        ]);
    }

    /**
     * Display the specified deployment details.
     */
    public function show(Request $request, int $projectId, int $deployId): JsonResponse
    {
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        $deploy = $this->deployService->getDeployDetails($deployId);

        if (!$deploy || $deploy->project_id !== $projectId) {
            return response()->json([
                'success' => false,
                'message' => 'Despliegue no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $deploy,
            'message' => 'Detalle del despliegue obtenido exitosamente.'
        ]);
    }
}
