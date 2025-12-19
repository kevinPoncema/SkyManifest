<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    /**
     * Display a listing of the user's projects.
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->getAllByUser($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $projects,
            'message' => 'Proyectos obtenidos exitosamente.'
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $project = $this->projectService->create($data);

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Proyecto creado exitosamente.'
        ], 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Proyecto obtenido exitosamente.'
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);
        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        $updatedProject = $this->projectService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $updatedProject,
            'message' => 'Proyecto actualizado exitosamente.'
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getById($id);

        if (!$project || $project->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado.'
            ], 404);
        }

        $this->projectService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Proyecto eliminado exitosamente.'
        ]);
    }
}
