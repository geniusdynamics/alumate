<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeadManagementService;
use App\Models\Lead;
use App\Models\LeadScoringRule;
use App\Models\CrmIntegration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadManagementController extends Controller
{
    public function __construct(
        private LeadManagementService $leadService
    ) {}

    /**
     * Display the lead management dashboard
     */
    public function index(): Response
    {
        $leads = Lead::with(['assignedUser', 'activities' => function ($query) {
            $query->latest()->limit(3);
        }])
        ->orderBy('priority', 'desc')
        ->orderBy('score', 'desc')
        ->paginate(20);

        $analytics = $this->leadService->getLeadAnalytics();
        $pipeline = $this->leadService->getLeadPipeline();
        $needsAttention = $this->leadService->getLeadsNeedingAttention();

        return Inertia::render('Admin/LeadManagement/Index', [
            'leads' => $leads,
            'analytics' => $analytics,
            'pipeline' => $pipeline,
            'needsAttention' => $needsAttention->take(10),
            'filters' => request()->only(['status', 'type', 'priority', 'assigned_to']),
        ]);
    }

    /**
     * Show lead details
     */
    public function show(Lead $lead): Response
    {
        $lead->load(['assignedUser', 'activities.creator']);

        return Inertia::render('Admin/LeadManagement/Show', [
            'lead' => $lead,
            'activities' => $lead->activities()->with('creator')->paginate(20),
        ]);
    }

    /**
     * Create a new lead
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:leads,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'lead_type' => 'required|in:individual,institutional,enterprise',
            'source' => 'required|in:homepage,demo_request,trial_signup,contact_form,referral,organic,paid_ads',
            'utm_data' => 'nullable|array',
            'form_data' => 'nullable|array',
            'behavioral_data' => 'nullable|array',
        ]);

        try {
            $lead = $this->leadService->createLead($validated);

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully',
                'lead' => $lead->load(['assignedUser']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update lead
     */
    public function update(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:leads,email,' . $lead->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'lead_type' => 'sometimes|in:individual,institutional,enterprise',
            'status' => 'sometimes|in:new,contacted,qualified,proposal,negotiation,closed_won,closed_lost',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $oldStatus = $lead->status;
            $lead->update($validated);

            // Log status change if it occurred
            if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
                $lead->addActivity(
                    'status_change',
                    'Status updated',
                    "Status changed from {$oldStatus} to {$validated['status']}"
                );
            }

            // Sync to CRM if configured
            $this->leadService->syncLeadToCRM($lead);

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully',
                'lead' => $lead->load(['assignedUser']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Qualify a lead
     */
    public function qualify(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'qualification_data' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $qualifiedLead = $this->leadService->qualifyLead($lead->id, $validated['qualification_data']);

            if (isset($validated['notes'])) {
                $qualifiedLead->addActivity(
                    'note',
                    'Lead qualified',
                    $validated['notes']
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Lead qualified successfully',
                'lead' => $qualifiedLead->load(['assignedUser']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to qualify lead: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create follow-up sequence
     */
    public function createFollowUp(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'sequence_type' => 'required|in:standard,enterprise',
        ]);

        try {
            $activities = $this->leadService->createFollowUpSequence($lead, $validated['sequence_type']);

            return response()->json([
                'success' => true,
                'message' => 'Follow-up sequence created successfully',
                'activities' => $activities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create follow-up sequence: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add activity to lead
     */
    public function addActivity(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:email,call,meeting,demo,proposal,follow_up,note',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'outcome' => 'nullable|in:positive,neutral,negative',
            'scheduled_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        try {
            $activity = $lead->activities()->create([
                'type' => $validated['type'],
                'subject' => $validated['subject'],
                'description' => $validated['description'] ?? null,
                'outcome' => $validated['outcome'] ?? null,
                'scheduled_at' => isset($validated['scheduled_at']) ? \Carbon\Carbon::parse($validated['scheduled_at']) : null,
                'metadata' => $validated['metadata'] ?? [],
                'created_by' => auth()->id(),
            ]);

            // Update last contacted if it's a contact activity
            if (in_array($validated['type'], ['email', 'call', 'meeting'])) {
                $lead->update(['last_contacted_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Activity added successfully',
                'activity' => $activity->load('creator'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add activity: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lead analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'lead_type', 'source']);
        
        try {
            $report = $this->leadService->generateLeadReport($filters);

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate analytics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk sync leads to CRM
     */
    public function bulkSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'exists:leads,id',
        ]);

        try {
            $results = $this->leadService->bulkSyncToCRM($validated['lead_ids'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Bulk sync completed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync leads: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update lead behavioral data
     */
    public function updateBehavior(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'behavioral_data' => 'required|array',
        ]);

        try {
            $this->leadService->updateBehavioralData($lead, $validated['behavioral_data']);

            return response()->json([
                'success' => true,
                'message' => 'Behavioral data updated successfully',
                'lead' => $lead->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update behavioral data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lead scoring rules
     */
    public function getScoringRules(): JsonResponse
    {
        $rules = LeadScoringRule::orderBy('priority', 'desc')->get();

        return response()->json([
            'success' => true,
            'rules' => $rules,
        ]);
    }

    /**
     * Create or update scoring rule
     */
    public function storeScoringRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:form_submission,page_visit,email_open,email_click,demo_request,trial_signup,company_size,job_title,industry',
            'conditions' => 'required|array',
            'points' => 'required|integer|min:-100|max:100',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0',
        ]);

        try {
            $rule = LeadScoringRule::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Scoring rule created successfully',
                'rule' => $rule,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create scoring rule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get CRM integrations
     */
    public function getCrmIntegrations(): JsonResponse
    {
        $integrations = CrmIntegration::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'integrations' => $integrations,
        ]);
    }

    /**
     * Create or update CRM integration
     */
    public function storeCrmIntegration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:salesforce,hubspot,pipedrive,zoho,twenty,frappe,custom',
            'config' => 'required|array',
            'is_active' => 'boolean',
            'sync_direction' => 'required|in:push,pull,bidirectional',
            'sync_interval' => 'integer|min:300', // Minimum 5 minutes
            'field_mappings' => 'nullable|array',
        ]);

        try {
            $integration = CrmIntegration::create($validated);

            // Test the connection
            $testResult = $integration->testConnection();
            
            return response()->json([
                'success' => true,
                'message' => 'CRM integration created successfully',
                'integration' => $integration,
                'connection_test' => $testResult,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create CRM integration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test CRM integration connection
     */
    public function testCrmConnection(CrmIntegration $integration): JsonResponse
    {
        try {
            $result = $integration->testConnection();

            return response()->json([
                'success' => true,
                'connection_test' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test connection: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export leads
     */
    public function export(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'priority', 'date_from', 'date_to']);
        
        try {
            $query = Lead::with(['assignedUser']);

            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['type'])) {
                $query->where('lead_type', $filters['type']);
            }
            
            if (isset($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }
            
            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }
            
            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $leads = $query->get();

            return response()->json([
                'success' => true,
                'leads' => $leads,
                'exported_at' => now(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export leads: ' . $e->getMessage(),
            ], 500);
        }
    }
}
