<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Lead Scoring Service
 *
 * Calculates lead scores based on email engagement metrics and manages
 * lead scoring rules, thresholds, and score decay for inactive leads.
 */
class LeadScoringService
{
    /**
     * Scoring rules configuration
     */
    private const SCORING_RULES = [
        'email_opens' => [
            'points' => 5,
            'max_daily' => 10,
            'decay_days' => 30,
        ],
        'email_clicks' => [
            'points' => 10,
            'max_daily' => 20,
            'decay_days' => 30,
        ],
        'page_views' => [
            'points' => 2,
            'max_daily' => 15,
            'decay_days' => 7,
        ],
        'form_submissions' => [
            'points' => 25,
            'max_daily' => 50,
            'decay_days' => 90,
        ],
        'downloads' => [
            'points' => 15,
            'max_daily' => 30,
            'decay_days' => 60,
        ],
        'social_interactions' => [
            'points' => 3,
            'max_daily' => 10,
            'decay_days' => 14,
        ],
        'job_applications' => [
            'points' => 50,
            'max_daily' => 100,
            'decay_days' => 180,
        ],
    ];

    /**
     * Score thresholds for lead qualification
     */
    private const SCORE_THRESHOLDS = [
        'cold' => 0,
        'warm' => 25,
        'hot' => 50,
        'qualified' => 75,
    ];

    /**
     * Calculate lead score based on engagement events
     */
    public function calculateLeadScore(Lead $lead, ?array $engagementEvents = null): int
    {
        $baseScore = $this->getBaseScore($lead);
        $engagementScore = $this->calculateEngagementScore($lead, $engagementEvents);
        $decayScore = $this->applyScoreDecay($lead);

        $totalScore = $baseScore + $engagementScore - $decayScore;

        return max(0, min(100, $totalScore));
    }

    /**
     * Update lead score in real-time based on engagement events
     */
    public function updateLeadScore(Lead $lead, string $eventType, array $eventData = []): array
    {
        try {
            $oldScore = $lead->score ?? 0;

            // Calculate score change for this event
            $scoreChange = $this->calculateEventScore($eventType, $eventData);

            if ($scoreChange > 0) {
                // Check daily limits
                $dailyScore = $this->getDailyEngagementScore($lead, $eventType);
                $maxDaily = self::SCORING_RULES[$eventType]['max_daily'] ?? 0;

                if ($dailyScore + $scoreChange <= $maxDaily) {
                    $newScore = $this->calculateLeadScore($lead);
                    $lead->update(['score' => $newScore]);

                    // Log the score change
                    $this->logScoreChange($lead, $oldScore, $newScore, $eventType, $eventData);

                    // Update lead priority if needed
                    $this->updateLeadPriority($lead, $newScore);

                    return [
                        'success' => true,
                        'old_score' => $oldScore,
                        'new_score' => $newScore,
                        'change' => $newScore - $oldScore,
                        'reason' => $eventType,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Daily scoring limit reached or invalid event',
                'old_score' => $oldScore,
                'new_score' => $oldScore,
            ];

        } catch (\Exception $e) {
            Log::error('Lead score update failed', [
                'lead_id' => $lead->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Apply score decay for inactive leads
     */
    public function applyScoreDecay(Lead $lead): int
    {
        $decayAmount = 0;
        $lastActivity = $this->getLastActivityDate($lead);

        if (!$lastActivity) {
            return 0;
        }

        $daysSinceActivity = $lastActivity->diffInDays(now());

        // Apply decay based on inactivity period
        if ($daysSinceActivity > 90) {
            $decayAmount = (int)($lead->score * 0.5); // 50% decay
        } elseif ($daysSinceActivity > 60) {
            $decayAmount = (int)($lead->score * 0.3); // 30% decay
        } elseif ($daysSinceActivity > 30) {
            $decayAmount = (int)($lead->score * 0.1); // 10% decay
        }

        return $decayAmount;
    }

    /**
     * Get lead scoring analytics and reporting
     */
    public function getScoringAnalytics(?int $tenantId = null): array
    {
        $query = Lead::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $leads = $query->get();

        $analytics = [
            'total_leads' => $leads->count(),
            'average_score' => $leads->avg('score') ?? 0,
            'score_distribution' => $this->getScoreDistribution($leads),
            'qualification_rates' => $this->getQualificationRates($leads),
            'top_scoring_leads' => $leads->sortByDesc('score')->take(10)->values(),
            'recent_score_changes' => $this->getRecentScoreChanges($tenantId),
        ];

        return $analytics;
    }

    /**
     * Batch update lead scores for multiple leads
     */
    public function batchUpdateScores(array $leadIds): array
    {
        $results = [];
        $updated = 0;
        $errors = 0;

        foreach ($leadIds as $leadId) {
            try {
                $lead = Lead::find($leadId);
                if ($lead) {
                    $oldScore = $lead->score ?? 0;
                    $newScore = $this->calculateLeadScore($lead);

                    if ($newScore !== $oldScore) {
                        $lead->update(['score' => $newScore]);
                        $updated++;
                    }

                    $results[] = [
                        'lead_id' => $leadId,
                        'old_score' => $oldScore,
                        'new_score' => $newScore,
                        'updated' => $newScore !== $oldScore,
                    ];
                }
            } catch (\Exception $e) {
                $errors++;
                $results[] = [
                    'lead_id' => $leadId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'total_processed' => count($leadIds),
            'updated' => $updated,
            'errors' => $errors,
            'results' => $results,
        ];
    }

    /**
     * Get scoring rules and thresholds
     */
    public function getScoringConfiguration(): array
    {
        return [
            'rules' => self::SCORING_RULES,
            'thresholds' => self::SCORE_THRESHOLDS,
            'max_score' => 100,
            'decay_schedule' => [
                '30_days' => 0.1,
                '60_days' => 0.3,
                '90_days' => 0.5,
            ],
        ];
    }

    /**
     * Get base score for lead
     */
    private function getBaseScore(Lead $lead): int
    {
        $baseScore = 0;

        // Base score based on lead source
        $sourceScores = [
            'referral' => 20,
            'paid_ads' => 15,
            'organic_search' => 10,
            'social_media' => 8,
            'direct' => 5,
        ];

        $baseScore += $sourceScores[$lead->source] ?? 0;

        // Additional points for complete profile
        if ($lead->first_name && $lead->last_name) {
            $baseScore += 5;
        }

        if ($lead->email) {
            $baseScore += 5;
        }

        if ($lead->phone) {
            $baseScore += 3;
        }

        if ($lead->company) {
            $baseScore += 5;
        }

        return $baseScore;
    }

    /**
     * Calculate engagement score from activities
     */
    private function calculateEngagementScore(Lead $lead, ?array $engagementEvents = null): int
    {
        $score = 0;

        if ($engagementEvents) {
            // Calculate from provided events
            foreach ($engagementEvents as $event) {
                $score += $this->calculateEventScore($event['type'], $event);
            }
        } else {
            // Calculate from lead activities
            $activities = $lead->activities()
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            foreach ($activities as $activity) {
                $score += $this->calculateEventScore($activity->type, $activity->toArray());
            }
        }

        return $score;
    }

    /**
     * Calculate score for a specific event
     */
    private function calculateEventScore(string $eventType, array $eventData = []): int
    {
        $rule = self::SCORING_RULES[$eventType] ?? null;

        if (!$rule) {
            return 0;
        }

        $points = $rule['points'];

        // Apply multipliers based on event data
        switch ($eventType) {
            case 'email_clicks':
                // More points for important links
                if (isset($eventData['link_type']) && $eventData['link_type'] === 'cta') {
                    $points *= 2;
                }
                break;

            case 'page_views':
                // More points for important pages
                if (isset($eventData['page_type']) && in_array($eventData['page_type'], ['pricing', 'demo'])) {
                    $points *= 1.5;
                }
                break;

            case 'form_submissions':
                // More points for qualified forms
                if (isset($eventData['form_type']) && $eventData['form_type'] === 'demo_request') {
                    $points *= 2;
                }
                break;
        }

        return (int)$points;
    }

    /**
     * Get daily engagement score for rate limiting
     */
    private function getDailyEngagementScore(Lead $lead, string $eventType): int
    {
        $cacheKey = "lead_{$lead->id}_{$eventType}_daily_score";
        return Cache::get($cacheKey, 0);
    }

    /**
     * Get last activity date for decay calculation
     */
    private function getLastActivityDate(Lead $lead): ?Carbon
    {
        $lastActivity = $lead->activities()
            ->whereIn('type', array_keys(self::SCORING_RULES))
            ->latest('created_at')
            ->first();

        return $lastActivity ? $lastActivity->created_at : null;
    }

    /**
     * Log score changes for auditing
     */
    private function logScoreChange(Lead $lead, int $oldScore, int $newScore, string $eventType, array $eventData): void
    {
        $lead->activities()->create([
            'type' => 'score_change',
            'subject' => 'Lead score updated',
            'description' => "Score changed from {$oldScore} to {$newScore}",
            'metadata' => [
                'old_score' => $oldScore,
                'new_score' => $newScore,
                'change' => $newScore - $oldScore,
                'event_type' => $eventType,
                'event_data' => $eventData,
            ],
            'created_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Update lead priority based on score
     */
    private function updateLeadPriority(Lead $lead, int $score): void
    {
        $priority = 'low';

        if ($score >= self::SCORE_THRESHOLDS['qualified']) {
            $priority = 'urgent';
        } elseif ($score >= self::SCORE_THRESHOLDS['hot']) {
            $priority = 'high';
        } elseif ($score >= self::SCORE_THRESHOLDS['warm']) {
            $priority = 'medium';
        }

        if ($lead->priority !== $priority) {
            $lead->update(['priority' => $priority]);
        }
    }

    /**
     * Get score distribution for analytics
     */
    private function getScoreDistribution($leads): array
    {
        $distribution = [
            'cold' => 0,
            'warm' => 0,
            'hot' => 0,
            'qualified' => 0,
        ];

        foreach ($leads as $lead) {
            $score = $lead->score ?? 0;

            if ($score >= self::SCORE_THRESHOLDS['qualified']) {
                $distribution['qualified']++;
            } elseif ($score >= self::SCORE_THRESHOLDS['hot']) {
                $distribution['hot']++;
            } elseif ($score >= self::SCORE_THRESHOLDS['warm']) {
                $distribution['warm']++;
            } else {
                $distribution['cold']++;
            }
        }

        return $distribution;
    }

    /**
     * Get qualification rates for analytics
     */
    private function getQualificationRates($leads): array
    {
        $total = $leads->count();
        if ($total === 0) {
            return ['qualified' => 0, 'hot' => 0, 'conversion_rate' => 0];
        }

        $qualified = $leads->where('score', '>=', self::SCORE_THRESHOLDS['qualified'])->count();
        $hot = $leads->where('score', '>=', self::SCORE_THRESHOLDS['hot'])->count();

        return [
            'qualified' => round(($qualified / $total) * 100, 2),
            'hot' => round(($hot / $total) * 100, 2),
            'conversion_rate' => round(($qualified / $total) * 100, 2),
        ];
    }

    /**
     * Get recent score changes for analytics
     */
    private function getRecentScoreChanges(?int $tenantId = null): array
    {
        $query = LeadActivity::where('type', 'score_change')
            ->with('lead')
            ->latest('created_at')
            ->take(50);

        if ($tenantId) {
            $query->whereHas('lead', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        return $query->get()->map(function ($activity) {
            return [
                'lead_id' => $activity->lead_id,
                'lead_name' => $activity->lead->full_name ?? 'Unknown',
                'old_score' => $activity->metadata['old_score'] ?? 0,
                'new_score' => $activity->metadata['new_score'] ?? 0,
                'change' => $activity->metadata['change'] ?? 0,
                'reason' => $activity->metadata['event_type'] ?? 'unknown',
                'changed_at' => $activity->created_at,
            ];
        })->toArray();
    }
}