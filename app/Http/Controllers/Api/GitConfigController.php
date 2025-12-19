<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGitConfigRequest;
use App\Http\Requests\UpdateGitConfigRequest;
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
     * Store git configuration for a project.
     */
    public function store(StoreGitConfigRequest $request): JsonResponse
    {
        $projectId = $request->input('project_id');
        
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        // Check if git config already exists
        $existingConfig = $this->gitConfigService->getByProject($projectId);
        if ($existingConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una configuración Git para este proyecto. Use el método de actualización.'
            ], 409);
        }

        $gitConfig = $this->gitConfigService->createOrUpdate(
            $projectId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $gitConfig,
            'message' => 'Configuración Git creada exitosamente.'
        ], 201);
    }

    /**
     * Update git configuration for a project.
     */
    public function update(UpdateGitConfigRequest $request): JsonResponse
    {
        $projectId = $request->input('project_id');
        
        // Verify project belongs to authenticated user
        $project = $this->projectService->getById($projectId);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        // Check if git config exists
        $existingConfig = $this->gitConfigService->getByProject($projectId);
        if (!$existingConfig) {
            return response()->json([
                'success' => false,
                'message' => 'No existe configuración Git para este proyecto.'
            ], 404);
        }

        $gitConfig = $this->gitConfigService->createOrUpdate(
            $projectId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $gitConfig,
            'message' => 'Configuración Git actualizada exitosamente.'
        ], 200);
    }
}
