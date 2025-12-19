<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeployService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    public function show(int $deployId): JsonResponse
    {
        $deploy = $this->deployService->getDeployDetails($deployId);

        if (!$deploy) {
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

    /**
     * Deploy project from GitHub repository
     */
    public function deployFromGithub(Request $request, int $projectId): JsonResponse
    {
        try {
            $project = $this->projectService->getById($projectId);
            if (!$project || $project->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proyecto no encontrado.'
                ], 404);
            }

            $deploy = $this->deployService->deployWithGithub($projectId, $project->name);

            return response()->json([
                'success' => true,
                'data' => $deploy,
                'message' => 'Despliegue iniciado exitosamente. Se están ejecutando despligue en cola de espera.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al iniciar despliegue desde GitHub', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deploy project from ZIP file
     */
    public function deployFromZip(Request $request, int $projectId): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:51200', // Max 50MB
        ]);

        try {
            $project = $this->projectService->getById($projectId);
            if (!$project || $project->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Proyecto no encontrado.'], 404);
            }

            // temporal save the uploaded zip file
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('temp_zips');
            } else {
                throw new \Exception("No se recibió ningún archivo.");
            }

            $deploy = $this->deployService->deployWithZip($projectId, $project->name, $path);

            return response()->json([
                'success' => true,
                'data' => $deploy,
                'message' => 'Despliegue desde ZIP iniciado. El archivo se procesará en segundo plano.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error deploy ZIP', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
