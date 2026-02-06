<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateMigrationRequest;
use App\Models\Migration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MigrationController extends Controller
{
    /**
     * Display a listing of migrations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $migrations = Migration::where('tenant_id', tenant()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'migrations' => $migrations->items(),
            'pagination' => [
                'current_page' => $migrations->currentPage(),
                'last_page' => $migrations->lastPage(),
                'per_page' => $migrations->perPage(),
                'total' => $migrations->total(),
            ],
            'meta' => [
                'total_count' => Migration::where('tenant_id', tenant()->id)->count(),
                'statuses' => ['pending', 'processing', 'completed', 'failed', 'rolled_back'],
                'types' => ['data', 'schema', 'content', 'configuration'],
            ]
        ]);
    }

    /**
     * Store a newly created migration
     *
     * @param CreateMigrationRequest $request
     * @return JsonResponse
     */
    public function store(CreateMigrationRequest $request): JsonResponse
    {
        $migration = Migration::create(array_merge($request->validated(), [
            'tenant_id' => tenant()->id,
            'user_id' => Auth::id(),
            'status' => 'pending',
        ]));

        // Dispatch migration job
        // MigrationJob::dispatch($migration);

        return response()->json([
            'migration' => $migration,
            'message' => 'Migration created successfully',
        ], 201);
    }

    /**
     * Display the specified migration
     *
     * @param Migration $migration
     * @return JsonResponse
     */
    public function show(Migration $migration): JsonResponse
    {
        $this->authorize('view', $migration);

        return response()->json([
            'migration' => $migration->load('user'),
        ]);
    }

    /**
     * Execute migration
     *
     * @param Migration $migration
     * @return JsonResponse
     */
    public function execute(Migration $migration): JsonResponse
    {
        $this->authorize('execute', $migration);

        if ($migration->status !== 'pending') {
            return response()->json([
                'message' => 'Migration is not in pending status',
            ], 422);
        }

        // Dispatch execute migration job
        // ExecuteMigrationJob::dispatch($migration);

        return response()->json([
            'message' => 'Migration execution initiated',
            'migration_id' => $migration->id,
        ]);
    }

    /**
     * Remove the specified migration
     *
     * @param Migration $migration
     * @return JsonResponse
     */
    public function destroy(Migration $migration): JsonResponse
    {
        $this->authorize('delete', $migration);

        if ($migration->status === 'processing') {
            return response()->json([
                'message' => 'Cannot delete migration while it is processing',
            ], 422);
        }

        $migration->delete();

        return response()->json([
            'message' => 'Migration deleted successfully',
        ]);
    }
}