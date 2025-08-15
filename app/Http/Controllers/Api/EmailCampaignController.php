<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\EmailAutomationRule;
use App\Services\EmailMarketingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class EmailCampaignController extends Controller
{
    public function __construct(
        protected EmailMarketingService $emailMarketingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = EmailCampaign::with(['creator', 'recipients'])
            ->where('tenant_id', tenant()->id);

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('provider')) {
            $query->byProvider($request->provider);
        }

        $campaigns = $query->latest()->paginate(15);

        return response()->json([
            'campaigns' => $campaigns,
            'stats' => [
                'total' => EmailCampaign::where('tenant_id', tenant()->id)->count(),
                'sent' => EmailCampaign::where('tenant_id', tenant()->id)->where('status', 'sent')->count(),
                'scheduled' => EmailCampaign::where('tenant_id', tenant()->id)->where('status', 'scheduled')->count(),
                'draft' => EmailCampaign::where('tenant_id', tenant()->id)->where('status', 'draft')->count(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'template_data' => 'nullable|array',
            'type' => ['required', Rule::in(['newsletter', 'announcement', 'event', 'fundraising', 'engagement'])],
            'provider' => ['nullable', Rule::in(['mailchimp', 'constant_contact', 'mautic', 'internal'])],
            'audience_criteria' => 'nullable|array',
            'personalization_rules' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        try {
            $campaign = $this->emailMarketingService->createCampaign($validated);

            return response()->json([
                'message' => 'Campaign created successfully',
                'campaign' => $campaign->load(['creator', 'recipients'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        return response()->json([
            'campaign' => $campaign->load(['creator', 'recipients', 'variants']),
            'analytics' => [
                'engagement_rate' => $campaign->engagement_rate,
                'delivery_rate' => $campaign->delivery_rate,
                'recipients_by_status' => $campaign->recipients()
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
            ]
        ]);
    }

    public function update(Request $request, EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canBeEdited()) {
            return response()->json([
                'message' => 'Campaign cannot be edited in current status'
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'template_data' => 'nullable|array',
            'audience_criteria' => 'nullable|array',
            'personalization_rules' => 'nullable|array',
        ]);

        $campaign->update($validated);

        return response()->json([
            'message' => 'Campaign updated successfully',
            'campaign' => $campaign->fresh(['creator', 'recipients'])
        ]);
    }

    public function destroy(EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        if (!$campaign->canBeEdited()) {
            return response()->json([
                'message' => 'Campaign cannot be deleted in current status'
            ], 422);
        }

        $campaign->delete();

        return response()->json([
            'message' => 'Campaign deleted successfully'
        ]);
    }

    public function send(EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        if (!$campaign->canBeSent()) {
            return response()->json([
                'message' => 'Campaign cannot be sent in current status'
            ], 422);
        }

        try {
            $success = $this->emailMarketingService->sendCampaign($campaign);

            if ($success) {
                return response()->json([
                    'message' => 'Campaign sent successfully',
                    'campaign' => $campaign->fresh()
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to send campaign'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function schedule(Request $request, EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        try {
            $success = $this->emailMarketingService->scheduleCampaign(
                $campaign,
                new \DateTime($validated['scheduled_at'])
            );

            if ($success) {
                return response()->json([
                    'message' => 'Campaign scheduled successfully',
                    'campaign' => $campaign->fresh()
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to schedule campaign'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to schedule campaign',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createAbTest(Request $request, EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'subject' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'template_data' => 'nullable|array',
        ]);

        try {
            $variantCampaign = $this->emailMarketingService->createAbTest($campaign, $validated);

            return response()->json([
                'message' => 'A/B test variant created successfully',
                'parent_campaign' => $campaign->fresh(),
                'variant_campaign' => $variantCampaign
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create A/B test variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function preview(Request $request, EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $user = $validated['user_id'] 
            ? \App\Models\User::find($validated['user_id'])
            : auth()->user();

        $personalizedContent = $this->emailMarketingService->personalizeContent(
            $campaign->content,
            $user,
            $campaign->personalization_rules ?? []
        );

        return response()->json([
            'preview' => [
                'subject' => $campaign->subject,
                'content' => $personalizedContent,
                'recipient' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]
        ]);
    }

    public function recipients(EmailCampaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $recipients = $this->emailMarketingService->getRecipients($campaign);

        return response()->json([
            'recipients' => $recipients->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'graduation_year' => $user->educations->first()?->graduation_year,
                    'location' => $user->location,
                ];
            }),
            'count' => $recipients->count()
        ]);
    }

    public function templates(): JsonResponse
    {
        $templates = EmailTemplate::where('tenant_id', tenant()->id)
            ->active()
            ->latest()
            ->get();

        return response()->json([
            'templates' => $templates
        ]);
    }

    public function automationRules(): JsonResponse
    {
        $rules = EmailAutomationRule::with(['template'])
            ->where('tenant_id', tenant()->id)
            ->latest()
            ->get();

        return response()->json([
            'automation_rules' => $rules
        ]);
    }

    public function createAutomationRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_event' => 'required|string',
            'trigger_conditions' => 'nullable|array',
            'audience_criteria' => 'nullable|array',
            'template_id' => 'required|exists:email_templates,id',
            'delay_minutes' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $rule = $this->emailMarketingService->createAutomationRule($validated);

            return response()->json([
                'message' => 'Automation rule created successfully',
                'rule' => $rule->load('template')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create automation rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        $campaigns = EmailCampaign::where('tenant_id', tenant()->id)->get();

        $analytics = [
            'total_campaigns' => $campaigns->count(),
            'total_sent' => $campaigns->where('status', 'sent')->count(),
            'total_recipients' => $campaigns->sum('total_recipients'),
            'total_delivered' => $campaigns->sum('delivered_count'),
            'total_opened' => $campaigns->sum('opened_count'),
            'total_clicked' => $campaigns->sum('clicked_count'),
            'average_open_rate' => $campaigns->where('total_recipients', '>', 0)->avg('open_rate'),
            'average_click_rate' => $campaigns->where('opened_count', '>', 0)->avg('click_rate'),
            'campaigns_by_type' => $campaigns->groupBy('type')->map->count(),
            'campaigns_by_status' => $campaigns->groupBy('status')->map->count(),
            'recent_campaigns' => $campaigns->sortByDesc('created_at')->take(5)->values(),
        ];

        return response()->json(['analytics' => $analytics]);
    }
}
