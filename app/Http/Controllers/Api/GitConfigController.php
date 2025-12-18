<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGitConfigRequest;
use App\Services\GitConfigService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitConfigController extends Controller
{
    public function __construct(
        private GitConfigService $gitConfigService,
        private ProjectService $projectService
    ) {}

    /**
     * Display the git configuration for a project.
     */
    public function show(Request $request, int $projectId): JsonResponse
    {
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        $gitConfig = $this->gitConfigService->getByProject($projectId);

        if (!$gitConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Configuración Git no encontrada.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $gitConfig,
            'message' => 'Configuración Git obtenida exitosamente.'
        ]);
    }

    /**
     * Store or update git configuration for a project (Upsert).
     */
    public function store(StoreGitConfigRequest $request, int $projectId): JsonResponse
    {
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        $gitConfig = $this->gitConfigService->createOrUpdate(
            $projectId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $gitConfig,
            'message' => 'Configuración Git guardada exitosamente.'
        ], 200);
    }
}
