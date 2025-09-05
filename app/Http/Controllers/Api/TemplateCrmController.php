<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TemplateCrmService;
use App\Models\TemplateCrmIntegration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Template CRM Integration Controller
 *
 * API endpoints for managing CRM integrations and template synchronization
 */
class TemplateCrmController extends Controller
{
    private TemplateCrmService $crmService;

    public function __construct(TemplateCrmService $crmService)
    {
        $this->crmService = $crmService;
    }

    /**
     * Get all CRM integrations for the current tenant
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->user()->current_tenant_id ?? 1; // Default for single tenant
            $integrations = $this->crmService->getTenantCrmIntegrations($tenantId);

            return response()->json([
                'success' => true,
                'data' => $integrations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve CRM integrations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new CRM integration
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'provider' => 'required|string|in:' . implode(',', TemplateCrmIntegration::PROVIDERS),
            'config' => 'required|array',
            'is_active' => 'boolean',
            'sync_direction' => 'string|in:' . implode(',', TemplateCrmIntegration::SYNC_DIRECTIONS),
            'sync_interval' => 'integer|min:60|max:86400',
            'field_mappings' => 'array',
            'sync_filters' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = array_merge($request->all(), [
                'tenant_id' => $request->user()->current_tenant_id ?? 1,
            ]);

            $integration = $this->crmService->createCrmIntegration($data);

            return response()->json([
                'success' => true,
                'message' => 'CRM integration created successfully',
                'data' => $integration
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create CRM integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific CRM integration
     */
    public function show(int $integrationId): JsonResponse
    {
        try {
            $integration = TemplateCrmIntegration::findOrFail($integrationId);

            return response()->json([
                'success' => true,
                'data' => $integration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'CRM integration not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a CRM integration
     */
    public function update(Request $request, int $integrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'config' => 'array',
            'is_active' => 'boolean',
            'sync_direction' => 'string|in:' . implode(',', TemplateCrmIntegration::SYNC_DIRECTIONS),
            'sync_interval' => 'integer|min:60|max:86400',
            'field_mappings' => 'array',
            'sync_filters' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $integration = $this->crmService->updateCrmIntegration($integrationId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'CRM integration updated successfully',
                'data' => $integration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update CRM integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a CRM integration
     */
    public function destroy(int $integrationId): JsonResponse
    {
        try {
            $this->crmService->deleteCrmIntegration($integrationId);

            return response()->json([
                'success' => true,
                'message' => 'CRM integration deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete CRM integration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test CRM integration connection
     */
    public function testConnection(int $integrationId): JsonResponse
    {
        try {
            $result = $this->crmService->testCrmConnection($integrationId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test CRM connection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync templates to CRM
     */
    public function syncTemplates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_ids' => 'array',
            'template_ids.*' => 'integer|exists:templates,id',
            'filters' => 'array',
            'filters.category' => 'string|in:' . implode(',', \App\Models\Template::CATEGORIES),
            'filters.audience_type' => 'string|in:' . implode(',', \App\Models\Template::AUDIENCE_TYPES),
            'filters.campaign_type' => 'string|in:' . implode(',', \App\Models\Template::CAMPAIGN_TYPES),
            'filters.is_premium' => 'boolean',
            'filters.usage_threshold' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->has('filters')) {
                $result = $this->crmService->syncTemplatesByFilters($request->filters);
            } else {
                $templateIds = $request->template_ids ?? [];
                $result = $this->crmService->syncTemplatesToCrm($templateIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Template sync completed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync logs
     */
    public function getSyncLogs(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'string|in:success,failed,pending',
            'provider' => 'string|in:' . implode(',', TemplateCrmIntegration::PROVIDERS),
            'sync_type' => 'string|in:create,update,delete',
            'date_from' => 'date',
            'date_to' => 'date',
            'limit' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tenantId = $request->user()->current_tenant_id ?? 1;
            $filters = array_filter($request->only([
                'status', 'provider', 'sync_type', 'date_from', 'date_to'
            ]));

            $logs = $this->crmService->getSyncLogs($tenantId, $filters);

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sync logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync statistics
     */
    public function getSyncStatistics(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->user()->current_tenant_id ?? 1;
            $stats = $this->crmService->getSyncStatistics($tenantId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sync statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate field mappings
     */
    public function validateFieldMappings(Request $request, int $integrationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'field_mappings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->crmService->validateFieldMappings(
                $integrationId,
                $request->field_mappings
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate field mappings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available CRM fields
     */
    public function getAvailableFields(Request $request, int $integrationId): JsonResponse
    {
        try {
            $integration = TemplateCrmIntegration::findOrFail($integrationId);
            $fields = $this->crmService->getAvailableCrmFields(
                $integration->provider,
                $integration->config
            );

            return response()->json([
                'success' => true,
                'data' => $fields
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve CRM fields',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle CRM webhook
     */
    public function handleWebhook(Request $request, string $provider): JsonResponse
    {
        try {
            $result = $this->crmService->processCrmWebhook($provider, $request->all());

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
