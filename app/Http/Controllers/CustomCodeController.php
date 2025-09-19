<?php

namespace App\Http\Controllers;

use App\Models\CustomCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomCodeController extends Controller
{
    /**
     * Display a listing of custom codes
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|string',
            'page_id' => 'nullable|integer|exists:landing_pages,id',
            'type' => 'nullable|in:html,css,javascript',
            'is_active' => 'nullable|boolean',
            'is_draft' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'include_inactive' => 'nullable|boolean',
            'include_versions' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'sort_by' => 'nullable|in:created_at,updated_at,name,version',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = CustomCode::forTenant($request->tenant_id);

        // Apply filters
        if ($request->filled('page_id')) {
            $query->forPage($request->page_id);
        }

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('is_draft')) {
            $query->where('is_draft', $request->is_draft);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('tags')) {
            $query->withTags($request->tags);
        }

        // Include inactive codes if requested
        if (!$request->boolean('include_inactive')) {
            $query->active();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $total = $query->count();
        $customCodes = $query->skip($offset)->take($limit)->get();

        // Load relationships if needed
        $customCodes->load(['creator:id,name', 'updater:id,name']);

        return response()->json([
            'success' => true,
            'data' => $customCodes,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }

    /**
     * Store a newly created custom code
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|string',
            'page_id' => 'nullable|integer|exists:landing_pages,id',
            'type' => 'required|in:html,css,javascript',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'code' => 'required|string|max:1000000', // 1MB limit
            'is_active' => 'boolean',
            'is_draft' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate names within tenant/page scope
        $existingQuery = CustomCode::forTenant($request->tenant_id)
            ->where('name', $request->name)
            ->where('type', $request->type);

        if ($request->filled('page_id')) {
            $existingQuery->forPage($request->page_id);
        } else {
            $existingQuery->whereNull('page_id');
        }

        if ($existingQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A custom code with this name already exists for this scope.'
            ], 409);
        }

        $customCode = CustomCode::create([
            'tenant_id' => $request->tenant_id,
            'page_id' => $request->page_id,
            'type' => $request->type,
            'name' => $request->name,
            'description' => $request->description,
            'code' => $request->code,
            'version' => 1,
            'is_active' => $request->boolean('is_active', false),
            'is_draft' => $request->boolean('is_draft', true),
            'tags' => $request->tags ?? [],
            'metadata' => $request->metadata ?? [],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $customCode->load(['creator:id,name', 'updater:id,name']);

        return response()->json([
            'success' => true,
            'data' => $customCode,
            'message' => 'Custom code created successfully.'
        ], 201);
    }

    /**
     * Display the specified custom code
     */
    public function show(CustomCode $customCode): JsonResponse
    {
        $customCode->load(['creator:id,name', 'updater:id,name', 'page:id,title']);

        return response()->json([
            'success' => true,
            'data' => $customCode
        ]);
    }

    /**
     * Update the specified custom code
     */
    public function update(Request $request, CustomCode $customCode): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'code' => 'sometimes|required|string|max:1000000',
            'is_active' => 'boolean',
            'is_draft' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate names if name is being updated
        if ($request->filled('name') && $request->name !== $customCode->name) {
            $existingQuery = CustomCode::forTenant($customCode->tenant_id)
                ->where('name', $request->name)
                ->where('type', $customCode->type)
                ->where('id', '!=', $customCode->id);

            if ($customCode->page_id) {
                $existingQuery->forPage($customCode->page_id);
            } else {
                $existingQuery->whereNull('page_id');
            }

            if ($existingQuery->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A custom code with this name already exists for this scope.'
                ], 409);
            }
        }

        // Create version snapshot if code is being updated
        $shouldCreateVersion = $request->filled('code') && $request->code !== $customCode->code;
        
        if ($shouldCreateVersion) {
            $this->createVersionSnapshot($customCode);
        }

        // Update the custom code
        $updateData = $request->only(['name', 'description', 'code', 'is_active', 'is_draft', 'tags', 'metadata']);
        $updateData['updated_by'] = Auth::id();

        if ($shouldCreateVersion) {
            $updateData['version'] = $customCode->version + 1;
        }

        $customCode->update($updateData);

        $customCode->load(['creator:id,name', 'updater:id,name']);

        return response()->json([
            'success' => true,
            'data' => $customCode,
            'message' => 'Custom code updated successfully.'
        ]);
    }

    /**
     * Remove the specified custom code
     */
    public function destroy(CustomCode $customCode): JsonResponse
    {
        $customCode->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom code deleted successfully.'
        ]);
    }

    /**
     * Search custom codes
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|string',
            'query' => 'nullable|string|max:255',
            'type' => 'nullable|in:html,css,javascript',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_active' => 'nullable|boolean',
            'is_draft' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'sort_by' => 'nullable|in:created_at,updated_at,name',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = CustomCode::forTenant($request->tenant_id);

        // Apply search filters
        if ($request->filled('query')) {
            $query->search($request->query);
        }

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('tags')) {
            $query->withTags($request->tags);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('is_draft')) {
            $query->where('is_draft', $request->is_draft);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'updated_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        
        $total = $query->count();
        $customCodes = $query->skip($offset)->take($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $customCodes,
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total
            ]
        ]);
    }

    /**
     * Get statistics for custom codes
     */
    public function stats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $tenantId = $request->tenant_id;

        $stats = [
            'total_codes' => CustomCode::forTenant($tenantId)->count(),
            'active_codes' => CustomCode::forTenant($tenantId)->active()->count(),
            'draft_codes' => CustomCode::forTenant($tenantId)->drafts()->count(),
            'by_type' => [
                'html' => CustomCode::forTenant($tenantId)->ofType('html')->count(),
                'css' => CustomCode::forTenant($tenantId)->ofType('css')->count(),
                'javascript' => CustomCode::forTenant($tenantId)->ofType('javascript')->count(),
            ],
            'total_versions' => CustomCode::forTenant($tenantId)->sum('version'),
            'storage_size' => CustomCode::forTenant($tenantId)->get()->sum('code_size'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Validate custom code
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:1000000',
            'type' => 'required|in:html,css,javascript'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Here you would integrate with your validation service
        // For now, we'll return a basic validation result
        $validationResult = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'security_issues' => [],
            'performance_issues' => []
        ];

        // Basic validation checks
        $code = $request->code;
        $type = $request->type;

        if ($type === 'html') {
            // Check for script tags
            if (preg_match('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', $code)) {
                $validationResult['security_issues'][] = [
                    'severity' => 'high',
                    'message' => 'Script tags detected in HTML code',
                    'remediation' => 'Remove script tags or use JavaScript code type instead'
                ];
            }
        }

        if ($type === 'javascript') {
            // Check for eval usage
            if (strpos($code, 'eval(') !== false) {
                $validationResult['security_issues'][] = [
                    'severity' => 'high',
                    'message' => 'Use of eval() detected',
                    'remediation' => 'Avoid using eval() as it can execute malicious code'
                ];
            }
        }

        $validationResult['is_valid'] = empty($validationResult['errors']);

        return response()->json([
            'success' => true,
            'data' => $validationResult
        ]);
    }

    /**
     * Create a version snapshot of the custom code
     */
    private function createVersionSnapshot(CustomCode $customCode): void
    {
        // This would create a version record in a separate table
        // For now, we'll just increment the version number
        // In a full implementation, you'd store the previous version
    }
}
