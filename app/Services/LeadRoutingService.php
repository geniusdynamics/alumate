<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\CrmIntegration;
use App\Models\Tenant;
use App\Jobs\RouteLeadToCrm;
use App\Jobs\RouteLeadToMultipleCrms;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for managing lead routing to multiple CRM systems
 *
 * Handles intelligent distribution of leads across different CRM providers,
 * form field configuration based on audience types, and multi-CRM routing strategies.
 */
class LeadRoutingService
{
    /**
     * Route a lead to multiple CRM systems based on routing rules and audience type
     */
    public function routeLead(Lead $lead, string $audienceType = 'general', array $customRules = []): array
    {
        try {
            // Determine audience-specific routing
            $routingConfig = $this->getAudienceRoutingConfig($audienceType);

            // Get available CRM integrations for current tenant
            $crmIntegrations = $this->getTenantCrmIntegrations();

            if (empty($crmIntegrations)) {
                Log::warning('No active CRM integrations found for tenant', [
                    'tenant_id' => tenant('id'),
                    'lead_id' => $lead->id
                ]);
                return ['success' => false, 'message' => 'No CRM integrations configured'];
            }

            // Apply custom routing logic
            $routingStrategy = $this->determineRoutingStrategy($lead, $routingConfig, $customRules);
            $result = $this->executeRoutingStrategy($lead, $routingStrategy, $crmIntegrations);

            // Log routing decision
            $this->logRoutingDecision($lead, $routingStrategy, $result);

            return [
                'success' => true,
                'lead_id' => $lead->id,
                'routing_strategy' => $routingStrategy,
                'routed_to' => count($result['routed_crms']),
                'result' => $result
            ];

        } catch (\Exception $e) {
            Log::error('Lead routing failed', [
                'lead_id' => $lead->id,
                'audience_type' => $audienceType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'lead_id' => $lead->id
            ];
        }
    }

    /**
     * Get form field configuration based on audience type
     */
    public function getFormFieldConfiguration(string $audienceType): array
    {
        $baseFields = [
            'first_name' => ['type' => 'text', 'required' => true, 'label' => 'First Name'],
            'last_name' => ['type' => 'text', 'required' => true, 'label' => 'Last Name'],
            'email' => ['type' => 'email', 'required' => true, 'label' => 'Email'],
            'phone' => ['type' => 'tel', 'required' => false, 'label' => 'Phone'],
        ];

        $audienceConfigs = [
            'individual' => [
                'job_title' => ['type' => 'text', 'required' => false, 'label' => 'Current Position'],
                'company' => ['type' => 'text', 'required' => false, 'label' => 'Current Company'],
                'industry' => ['type' => 'select', 'required' => false, 'label' => 'Industry'],
                'experience_years' => ['type' => 'number', 'required' => false, 'label' => 'Years of Experience'],
                'linkedin_profile' => ['type' => 'url', 'required' => false, 'label' => 'LinkedIn Profile'],
            ],
            'institution' => [
                'company' => ['type' => 'text', 'required' => true, 'label' => 'Institution Name'],
                'job_title' => ['type' => 'text', 'required' => true, 'label' => 'Role'],
                'alumni_count' => ['type' => 'number', 'required' => true, 'label' => 'Alumni Count'],
                'implementation_timeline' => ['type' => 'select', 'required' => true, 'label' => 'Implementation Timeline'],
                'primary_goal' => ['type' => 'select', 'required' => true, 'label' => 'Primary Goal'],
                'contact_title' => ['type' => 'text', 'required' => true, 'label' => 'Contact Title'],
            ],
            'employer' => [
                'company' => ['type' => 'text', 'required' => true, 'label' => 'Company Name'],
                'job_title' => ['type' => 'text', 'required' => true, 'label' => 'Position'],
                'industry' => ['type' => 'select', 'required' => true, 'label' => 'Industry'],
                'company_size' => ['type' => 'select', 'required' => false, 'label' => 'Company Size'],
                'hiring_goals' => ['type' => 'textarea', 'required' => false, 'label' => 'Hiring Goals'],
            ],
            'student' => [
                'graduation_year' => ['type' => 'number', 'required' => true, 'label' => 'Graduation Year'],
                'major' => ['type' => 'text', 'required' => false, 'label' => 'Major/Area of Study'],
                'career_interests' => ['type' => 'select', 'required' => true, 'label' => 'Career Interests'],
                'university_affiliation' => ['type' => 'text', 'required' => true, 'label' => 'University'],
            ],
            'general' => [
                'company' => ['type' => 'text', 'required' => false, 'label' => 'Company/Institution'],
                'job_title' => ['type' => 'text', 'required' => false, 'label' => 'Position'],
                'inquiry_type' => ['type' => 'select', 'required' => true, 'label' => 'How can we help?'],
            ]
        ];

        return array_merge($baseFields, $audienceConfigs[$audienceType] ?? []);
    }

    /**
     * Get audience-specific routing configuration
     */
    private function getAudienceRoutingConfig(string $audienceType): array
    {
        $configs = [
            'individual' => [
                'preferred_crm' => 'pipedrive',
                'secondary_crm' => 'hubspot',
                'routing_priority' => 'primary_first',
                'score_threshold' => 60,
            ],
            'institution' => [
                'preferred_crm' => 'salesforce',
                'secondary_crm' => 'hubspot',
                'routing_priority' => 'parallel',
                'score_threshold' => 80,
            ],
            'employer' => [
                'preferred_crm' => 'hubspot',
                'secondary_crm' => 'pipedrive',
                'routing_priority' => 'primary_only',
                'score_threshold' => 70,
            ],
            'student' => [
                'preferred_crm' => 'hubspot',
                'secondary_crm' => null,
                'routing_priority' => 'primary_only',
                'score_threshold' => 50,
            ],
            'general' => [
                'preferred_crm' => 'hubspot',
                'secondary_crm' => 'pipedrive',
                'routing_priority' => 'primary_first',
                'score_threshold' => 40,
            ]
        ];

        return $configs[$audienceType] ?? $configs['general'];
    }

    /**
     * Determine routing strategy based on lead, config, and rules
     */
    private function determineRoutingStrategy(Lead $lead, array $routingConfig, array $customRules): array
    {
        $strategy = [
            'type' => $routingConfig['routing_priority'],
            'primary_crm' => $routingConfig['preferred_crm'],
            'secondary_crm' => $routingConfig['secondary_crm'],
            'simultaneous_routing' => false,
            'load_balancing' => false,
            'qualify_only' => false,
        ];

        // Apply lead score-based routing
        if ($lead->score < $routingConfig['score_threshold']) {
            $strategy['qualify_only'] = true;
        }

        // Apply custom rules
        foreach ($customRules as $rule) {
            if ($this->evaluateRule($lead, $rule)) {
                $strategy = array_merge($strategy, $rule['strategy'] ?? []);
                break;
            }
        }

        // High score leads get parallel routing
        if ($lead->score >= 90) {
            $strategy['type'] = 'parallel';
        }

        return $strategy;
    }

    /**
     * Execute the determined routing strategy
     */
    private function executeRoutingStrategy(Lead $lead, array $strategy, array $crmIntegrations): array
    {
        $routedCrms = [];
        $results = [];

        if ($strategy['qualify_only']) {
            return [
                'routed_crms' => [],
                'results' => [],
                'notes' => 'Lead needs qualification before routing'
            ];
        }

        if ($strategy['type'] === 'parallel') {
            // Route to all available CRMs simultaneously
            foreach ($crmIntegrations as $crm) {
                $result = $this->queueLeadRouting($lead, $crm);
                $routedCrms[] = $crm->provider;
                $results[] = $result;
            }
        } elseif ($strategy['type'] === 'primary_first') {
            // Route to primary CRM first
            if ($strategy['primary_crm'] && isset($crmIntegrations[$strategy['primary_crm']])) {
                $result = $this->queueLeadRouting($lead, $crmIntegrations[$strategy['primary_crm']]);
                $routedCrms[] = $strategy['primary_crm'];
                $results[] = $result;
            }

            // Queue secondary routing with delay
            if ($strategy['secondary_crm'] && isset($crmIntegrations[$strategy['secondary_crm']])) {
                $this->queueDelayedRouting($lead, $crmIntegrations[$strategy['secondary_crm']], 3600); // 1 hour delay
            }
        } elseif ($strategy['type'] === 'load_balanced') {
            // Load balance across available CRMs
            $crm = $this->selectLoadBalancedCrm($crmIntegrations, $lead);
            $result = $this->queueLeadRouting($lead, $crm);
            $routedCrms[] = $crm->provider;
            $results[] = $result;
        } else {
            // Primary only (default behavior)
            if ($strategy['primary_crm'] && isset($crmIntegrations[$strategy['primary_crm']])) {
                $result = $this->queueLeadRouting($lead, $crmIntegrations[$strategy['primary_crm']]);
                $routedCrms[] = $strategy['primary_crm'];
                $results[] = $result;
            }
        }

        return [
            'routed_crms' => array_unique($routedCrms),
            'results' => $results,
            'strategy_applied' => $strategy['type']
        ];
    }

    /**
     * Queue lead routing to a specific CRM
     */
    private function queueLeadRouting(Lead $lead, CrmIntegration $crm): array
    {
        try {
            RouteLeadToCrm::dispatch($lead, $crm)
                ->onQueue('lead-routing');

            return [
                'success' => true,
                'crm_provider' => $crm->provider,
                'queued_at' => now()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to queue lead routing', [
                'lead_id' => $lead->id,
                'crm_provider' => $crm->provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'crm_provider' => $crm->provider,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Queue delayed routing for follow-up CRM routing
     */
    private function queueDelayedRouting(Lead $lead, CrmIntegration $crm, int $delaySeconds): void
    {
        try {
            RouteLeadToCrm::dispatch($lead, $crm)
                ->delay(now()->addSeconds($delaySeconds))
                ->onQueue('lead-routing-delayed');

        } catch (\Exception $e) {
            Log::error('Failed to queue delayed lead routing', [
                'lead_id' => $lead->id,
                'crm_provider' => $crm->provider,
                'delay' => $delaySeconds,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Select CRM using load balancing
     */
    private function selectLoadBalancedCrm(array $crmIntegrations, Lead $lead): CrmIntegration
    {
        $cacheKey = 'crm_load_balancer_index';
        $index = Cache::get($cacheKey, 0);
        $crmArray = array_values($crmIntegrations);

        $selectedCrm = $crmArray[$index % count($crmArray)];

        Cache::put($cacheKey, $index + 1, now()->addHour());

        return $selectedCrm;
    }

    /**
     * Get all active CRM integrations for current tenant
     */
    private function getTenantCrmIntegrations(): array
    {
        $integrations = CrmIntegration::active()
            ->get()
            ->keyBy('provider')
            ->toArray();

        return array_map(function ($integration) {
            return (object) $integration;
        }, $integrations);
    }

    /**
     * Evaluate a custom routing rule against a lead
     */
    private function evaluateRule(Lead $lead, array $rule): bool
    {
        $condition = $rule['condition'] ?? [];

        foreach ($condition as $field => $criteria) {
            $value = $this->getLeadFieldValue($lead, $field);

            if (!$this->matchesCriteria($value, $criteria)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get lead field value by path
     */
    private function getLeadFieldValue(Lead $lead, string $path)
    {
        $parts = explode('.', $path);
        $value = $lead;

        foreach ($parts as $part) {
            if (is_array($value)) {
                $value = $value[$part] ?? null;
            } elseif (is_object($value)) {
                $value = $value->{$part} ?? null;
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Check if a value matches given criteria
     */
    private function matchesCriteria($value, array $criteria): bool
    {
        $operator = $criteria['operator'] ?? 'equals';
        $expectedValue = $criteria['value'] ?? null;

        switch ($operator) {
            case 'equals':
                return $value == $expectedValue;
            case 'not_equals':
                return $value != $expectedValue;
            case 'greater_than':
                return $value > $expectedValue;
            case 'less_than':
                return $value < $expectedValue;
            case 'contains':
                return str_contains(strtolower($value), strtolower($expectedValue));
            case 'in':
                return in_array($value, $expectedValue);
            default:
                return false;
        }
    }

    /**
     * Log routing decision for analytics and debugging
     */
    private function logRoutingDecision(Lead $lead, array $strategy, array $result): void
    {
        Log::info('Lead routing decision made', [
            'lead_id' => $lead->id,
            'lead_score' => $lead->score,
            'strategy_type' => $strategy['type'],
            'primary_crm' => $strategy['primary_crm'],
            'secondary_crm' => $strategy['secondary_crm'],
            'routed_to' => $result['routed_crms'],
            'routed_count' => count($result['routed_crms']),
            'qualify_only' => $strategy['qualify_only'] ?? false,
            'tenant_id' => tenant('id')
        ]);

        $lead->update([
            'routing_log' => array_merge(
                $lead->routing_log ?? [],
                [[
                    'timestamp' => now()->toISOString(),
                    'strategy' => $strategy,
                    'result' => $result,
                    'tenant_id' => tenant('id')
                ]]
            )
        ]);
    }

    /**
     * Batch route multiple leads with optimized performance
     */
    public function batchRouteLeads(array $leads, string $audienceType = 'general', array $customRules = []): array
    {
        $results = [];
        $batchSize = 50; // Process in batches for memory efficiency

        foreach (array_chunk($leads, $batchSize) as $batch) {
            foreach ($batch as $leadId) {
                $lead = Lead::find($leadId);
                if ($lead) {
                    $results[$leadId] = $this->routeLead($lead, $audienceType, $customRules);
                } else {
                    $results[$leadId] = ['success' => false, 'error' => 'Lead not found'];
                }
            }
        }

        return [
            'total_leads' => count($leads),
            'processed' => count($results),
            'successful' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
            'results' => $results
        ];
    }

    /**
     * Get routing analytics for reporting
     */
    public function getRoutingAnalytics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $leads = Lead::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('routing_log')
            ->get();

        $analytics = [
            'total_leads' => $leads->count(),
            'routed_leads' => 0,
            'successful_routes' => 0,
            'strategy_usage' => [
                'parallel' => 0,
                'primary_first' => 0,
                'primary_only' => 0,
                'load_balanced' => 0,
                'qualify_only' => 0
            ],
            'crm_distribution' => [],
            'average_score' => $leads->avg('score'),
            'conversion_rate' => 0
        ];

        $qualifiedLeads = Lead::where('qualified_at', '>=', $startDate)->count();
        $analytics['conversion_rate'] = $leads->count() > 0 ?
            ($qualifiedLeads / $leads->count()) * 100 : 0;

        foreach ($leads as $lead) {
            $latestRouting = end($lead->routing_log ?? []);

            if (isset($latestRouting['strategy']['type'])) {
                $strategy = $latestRouting['strategy']['type'];
                $analytics['strategy_usage'][$strategy]++;

                if (!empty($latestRouting['result']['routed_crms'])) {
                    $analytics['routed_leads']++;
                    foreach ($latestRouting['result']['routed_crms'] as $crm) {
                        $analytics['crm_distribution'][$crm] =
                            ($analytics['crm_distribution'][$crm] ?? 0) + 1;
                    }
                }
            }
        }

        return $analytics;
    }
}