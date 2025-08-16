<?php

namespace App\Services;

use App\Models\CrmIntegration;
use App\Models\Lead;
use App\Models\LeadScoringRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadManagementService
{
    /**
     * Create a new lead from form submission
     */
    public function createLead(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            // Create the lead
            $lead = Lead::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'lead_type' => $data['lead_type'] ?? 'individual',
                'source' => $data['source'] ?? 'homepage',
                'utm_data' => $data['utm_data'] ?? null,
                'form_data' => $data['form_data'] ?? null,
                'behavioral_data' => $data['behavioral_data'] ?? null,
            ]);

            // Apply lead scoring
            $this->applyLeadScoring($lead, 'form_submission', $data);

            // Auto-assign if rules exist
            $this->autoAssignLead($lead);

            // Log initial activity
            $lead->addActivity(
                'note',
                'Lead created',
                "Lead created from {$lead->source} with initial score of {$lead->score}"
            );

            // Sync to CRM if configured
            $this->syncLeadToCRM($lead);

            return $lead;
        });
    }

    /**
     * Apply lead scoring rules
     */
    public function applyLeadScoring(Lead $lead, string $triggerType, array $context = []): void
    {
        $rules = LeadScoringRule::active()
            ->byTrigger($triggerType)
            ->byPriority()
            ->get();

        $totalPoints = 0;
        $appliedRules = [];

        foreach ($rules as $rule) {
            if ($rule->matches($lead, $context)) {
                $totalPoints += $rule->points;
                $appliedRules[] = $rule->name;
            }
        }

        if ($totalPoints !== 0) {
            $lead->updateScore(
                $totalPoints,
                'Applied rules: '.implode(', ', $appliedRules)
            );

            // Update priority based on score
            $this->updateLeadPriority($lead);
        }
    }

    /**
     * Update lead priority based on score
     */
    private function updateLeadPriority(Lead $lead): void
    {
        $oldPriority = $lead->priority;

        if ($lead->score >= 80) {
            $newPriority = 'urgent';
        } elseif ($lead->score >= 60) {
            $newPriority = 'high';
        } elseif ($lead->score >= 40) {
            $newPriority = 'medium';
        } else {
            $newPriority = 'low';
        }

        if ($oldPriority !== $newPriority) {
            $lead->update(['priority' => $newPriority]);

            $lead->addActivity(
                'note',
                'Priority updated',
                "Priority changed from {$oldPriority} to {$newPriority} based on score ({$lead->score})"
            );
        }
    }

    /**
     * Auto-assign lead based on rules
     */
    private function autoAssignLead(Lead $lead): void
    {
        // Simple round-robin assignment for now
        // In production, this would be more sophisticated
        $availableUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['sales_rep', 'account_manager']);
        })->get();

        if ($availableUsers->isNotEmpty()) {
            $assignedUser = $availableUsers->random();
            $lead->update(['assigned_to' => $assignedUser->id]);

            $lead->addActivity(
                'note',
                'Lead assigned',
                "Lead automatically assigned to {$assignedUser->name}"
            );
        }
    }

    /**
     * Qualify a lead
     */
    public function qualifyLead(int $leadId, array $qualificationData): Lead
    {
        $lead = Lead::findOrFail($leadId);

        $lead->updateStatus('qualified', 'Lead qualified based on criteria');

        // Update lead with qualification data
        $lead->update([
            'form_data' => array_merge($lead->form_data ?? [], $qualificationData),
        ]);

        // Apply scoring for qualification
        $this->applyLeadScoring($lead, 'qualification', $qualificationData);

        // Sync to CRM
        $this->syncLeadToCRM($lead);

        return $lead;
    }

    /**
     * Create follow-up sequence for lead
     */
    public function createFollowUpSequence(Lead $lead, string $sequenceType = 'standard'): Collection
    {
        $activities = collect();

        $sequences = [
            'standard' => [
                ['type' => 'email', 'delay' => 0, 'subject' => 'Welcome and next steps'],
                ['type' => 'call', 'delay' => 1, 'subject' => 'Initial discovery call'],
                ['type' => 'email', 'delay' => 3, 'subject' => 'Follow-up resources'],
                ['type' => 'call', 'delay' => 7, 'subject' => 'Check-in call'],
                ['type' => 'email', 'delay' => 14, 'subject' => 'Case study and demo offer'],
            ],
            'enterprise' => [
                ['type' => 'email', 'delay' => 0, 'subject' => 'Enterprise solution overview'],
                ['type' => 'call', 'delay' => 1, 'subject' => 'Stakeholder discovery call'],
                ['type' => 'meeting', 'delay' => 3, 'subject' => 'Solution demonstration'],
                ['type' => 'proposal', 'delay' => 7, 'subject' => 'Custom proposal preparation'],
                ['type' => 'call', 'delay' => 10, 'subject' => 'Proposal review call'],
            ],
        ];

        $sequence = $sequences[$sequenceType] ?? $sequences['standard'];

        foreach ($sequence as $step) {
            $scheduledAt = now()->addDays($step['delay']);

            $activity = $lead->activities()->create([
                'type' => $step['type'],
                'subject' => $step['subject'],
                'description' => "Automated follow-up sequence: {$sequenceType}",
                'scheduled_at' => $scheduledAt,
                'metadata' => [
                    'sequence_type' => $sequenceType,
                    'sequence_step' => count($activities) + 1,
                    'automated' => true,
                ],
                'created_by' => $lead->assigned_to ?? auth()->id() ?? 1,
            ]);

            $activities->push($activity);
        }

        $lead->addActivity(
            'note',
            'Follow-up sequence created',
            "Created {$sequenceType} follow-up sequence with ".count($activities).' activities'
        );

        return $activities;
    }

    /**
     * Get lead analytics
     */
    public function getLeadAnalytics(array $filters = []): array
    {
        $query = Lead::query();

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['lead_type'])) {
            $query->where('lead_type', $filters['lead_type']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        $leads = $query->get();

        return [
            'total_leads' => $leads->count(),
            'by_status' => $leads->groupBy('status')->map->count(),
            'by_type' => $leads->groupBy('lead_type')->map->count(),
            'by_source' => $leads->groupBy('source')->map->count(),
            'by_priority' => $leads->groupBy('priority')->map->count(),
            'average_score' => $leads->avg('score'),
            'qualified_rate' => $leads->where('qualified_at')->count() / max($leads->count(), 1) * 100,
            'conversion_rate' => $leads->where('status', 'closed_won')->count() / max($leads->count(), 1) * 100,
            'hot_leads' => $leads->filter->isHot()->count(),
            'needs_follow_up' => Lead::needsFollowUp()->count(),
        ];
    }

    /**
     * Get lead pipeline data
     */
    public function getLeadPipeline(): array
    {
        $pipeline = Lead::selectRaw('status, COUNT(*) as count, AVG(score) as avg_score')
            ->groupBy('status')
            ->orderByRaw("FIELD(status, 'new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost')")
            ->get();

        return $pipeline->map(function ($stage) {
            return [
                'status' => $stage->status,
                'count' => $stage->count,
                'avg_score' => round($stage->avg_score, 1),
                'percentage' => 0, // Will be calculated on frontend
            ];
        })->toArray();
    }

    /**
     * Sync lead to CRM systems
     */
    public function syncLeadToCRM(Lead $lead): array
    {
        $results = [];

        $integrations = CrmIntegration::active()->get();

        foreach ($integrations as $integration) {
            try {
                $result = $integration->syncLead($lead);
                $results[$integration->name] = $result;

                if ($result['success']) {
                    Log::info("Lead {$lead->id} synced to {$integration->name}");
                } else {
                    Log::error("Failed to sync lead {$lead->id} to {$integration->name}: {$result['message']}");
                }
            } catch (\Exception $e) {
                $results[$integration->name] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];

                Log::error("Exception syncing lead {$lead->id} to {$integration->name}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    /**
     * Bulk sync leads to CRM
     */
    public function bulkSyncToCRM(?array $leadIds = null): array
    {
        $query = Lead::query();

        if ($leadIds) {
            $query->whereIn('id', $leadIds);
        } else {
            // Sync leads that haven't been synced or need re-sync
            $query->where(function ($q) {
                $q->whereNull('synced_at')
                    ->orWhere('updated_at', '>', DB::raw('synced_at'));
            });
        }

        $leads = $query->get();
        $results = [];

        foreach ($leads as $lead) {
            $results[$lead->id] = $this->syncLeadToCRM($lead);
        }

        return [
            'total_leads' => $leads->count(),
            'results' => $results,
        ];
    }

    /**
     * Get leads needing attention
     */
    public function getLeadsNeedingAttention(): Collection
    {
        return Lead::where(function ($query) {
            $query->whereNull('assigned_to') // Unassigned leads
                ->orWhere(function ($q) {
                    $q->where('priority', 'urgent')
                        ->whereNull('last_contacted_at');
                })
                ->orWhere(function ($q) {
                    $q->where('last_contacted_at', '<', now()->subDays(7))
                        ->whereNotIn('status', ['closed_won', 'closed_lost']);
                });
        })
            ->with(['assignedUser', 'activities' => function ($query) {
                $query->latest()->limit(3);
            }])
            ->orderBy('priority', 'desc')
            ->orderBy('score', 'desc')
            ->get();
    }

    /**
     * Update lead behavioral data
     */
    public function updateBehavioralData(Lead $lead, array $behaviorData): void
    {
        $currentData = $lead->behavioral_data ?? [];
        $updatedData = array_merge($currentData, $behaviorData);

        $lead->update(['behavioral_data' => $updatedData]);

        // Apply scoring based on behavior
        $this->applyLeadScoring($lead, 'page_visit', $behaviorData);
    }

    /**
     * Generate lead report
     */
    public function generateLeadReport(array $filters = []): array
    {
        $analytics = $this->getLeadAnalytics($filters);
        $pipeline = $this->getLeadPipeline();
        $needsAttention = $this->getLeadsNeedingAttention();

        return [
            'analytics' => $analytics,
            'pipeline' => $pipeline,
            'needs_attention' => $needsAttention->take(10),
            'top_sources' => $this->getTopSources($filters),
            'performance_metrics' => $this->getPerformanceMetrics($filters),
        ];
    }

    /**
     * Get top lead sources
     */
    private function getTopSources(array $filters = []): array
    {
        $query = Lead::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        return $query->selectRaw('source, COUNT(*) as count, AVG(score) as avg_score')
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(array $filters = []): array
    {
        $query = Lead::query();

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        $leads = $query->get();
        $qualified = $leads->where('qualified_at');
        $won = $leads->where('status', 'closed_won');

        return [
            'total_leads' => $leads->count(),
            'qualified_leads' => $qualified->count(),
            'won_leads' => $won->count(),
            'qualification_rate' => $leads->count() > 0 ? ($qualified->count() / $leads->count()) * 100 : 0,
            'win_rate' => $qualified->count() > 0 ? ($won->count() / $qualified->count()) * 100 : 0,
            'average_time_to_qualify' => $this->calculateAverageTimeToQualify($qualified),
            'average_score' => $leads->avg('score'),
        ];
    }

    /**
     * Calculate average time to qualify leads
     */
    private function calculateAverageTimeToQualify(Collection $qualifiedLeads): float
    {
        if ($qualifiedLeads->isEmpty()) {
            return 0;
        }

        $totalHours = $qualifiedLeads->sum(function ($lead) {
            return $lead->created_at->diffInHours($lead->qualified_at);
        });

        return $totalHours / $qualifiedLeads->count();
    }
}
