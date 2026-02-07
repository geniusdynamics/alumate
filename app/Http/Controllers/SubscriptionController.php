<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateSubscriptionRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Display subscription management page.
     */
    public function index(): Response
    {
        $tenant = Auth::user()->currentTenant;
        
        $subscription = Subscription::with('plan')
            ->where('tenant_id', $tenant->id)
            ->active()
            ->first();

        $plans = SubscriptionPlan::active()->ordered()->get();

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        $usage = [];
        if ($subscription) {
            foreach ($subscription->usage as $item) {
                $usage[$item->feature_key] = [
                    'used' => $item->usage,
                    'limit' => $item->limit,
                    'percentage' => $item->usagePercentage(),
                ];
            }
        }

        return Inertia::render('Subscription/Index', [
            'subscription' => $subscription,
            'plans' => $plans,
            'invoices' => $invoices,
            'usage' => $usage,
        ]);
    }

    /**
     * Get available subscription plans.
     */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::active()->ordered()->get()->map(function ($plan) {
            return [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'description' => $plan->description,
                'price_monthly' => $plan->price_monthly,
                'price_yearly' => $plan->price_yearly,
                'currency' => $plan->currency,
                'is_popular' => $plan->is_popular,
                'trial_days' => $plan->trial_days,
                'yearly_savings' => $plan->getYearlySavingsPercentage(),
                'features' => $plan->planFeatures->map(function ($feature) {
                    return [
                        'key' => $feature->feature_key,
                        'name' => $feature->feature_name,
                        'value' => $feature->getDisplayValue(),
                    ];
                }),
            ];
        });

        return response()->json(['plans' => $plans]);
    }

    /**
     * Create a new subscription.
     */
    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $tenant = Auth::user()->currentTenant;
        
        // Check if tenant already has an active subscription
        $existingSubscription = Subscription::where('tenant_id', $tenant->id)->active()->first();
        if ($existingSubscription) {
            return response()->json([
                'message' => 'Tenant already has an active subscription',
            ], 422);
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $subscription = $this->subscriptionService->createSubscription(
                $tenant,
                $plan,
                $request->payment_method_id,
                $request->interval ?? 'monthly'
            );

            return response()->json([
                'message' => 'Subscription created successfully',
                'subscription' => $subscription->load('plan'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current subscription details.
     */
    public function show(): JsonResponse
    {
        $tenant = Auth::user()->currentTenant;
        
        $subscription = Subscription::with(['plan', 'usage'])
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$subscription) {
            return response()->json(['subscription' => null]);
        }

        $upcomingInvoice = $this->subscriptionService->getUpcomingInvoice($subscription);

        return response()->json([
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'status_label' => $subscription->getStatusLabel(),
                'status_color' => $subscription->getStatusColor(),
                'plan' => $subscription->plan,
                'current_period_starts_at' => $subscription->current_period_starts_at,
                'current_period_ends_at' => $subscription->current_period_ends_at,
                'trial_ends_at' => $subscription->trial_ends_at,
                'days_remaining' => $subscription->daysRemaining(),
                'trial_days_remaining' => $subscription->trialDaysRemaining(),
                'is_on_trial' => $subscription->isOnTrial(),
                'is_cancelled' => $subscription->isCancelled(),
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'payment_method' => $subscription->getPaymentMethodDisplay(),
                'usage' => $subscription->usage->map(function ($item) {
                    return [
                        'feature_key' => $item->feature_key,
                        'usage' => $item->usage,
                        'limit' => $item->limit,
                        'remaining' => $item->remaining(),
                        'percentage' => $item->usagePercentage(),
                    ];
                }),
            ],
            'upcoming_invoice' => $upcomingInvoice,
        ]);
    }

    /**
     * Upgrade or downgrade subscription.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'interval' => 'nullable|in:monthly,yearly',
        ]);

        $tenant = Auth::user()->currentTenant;
        
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 404);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $subscription = $this->subscriptionService->changePlan(
                $subscription,
                $newPlan,
                $request->interval ?? 'monthly'
            );

            return response()->json([
                'message' => 'Subscription plan changed successfully',
                'subscription' => $subscription->load('plan'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change subscription plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $request->validate([
            'immediate' => 'boolean',
        ]);

        $tenant = Auth::user()->currentTenant;
        
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 404);
        }

        try {
            $atPeriodEnd = !$request->boolean('immediate', false);
            $subscription = $this->subscriptionService->cancelSubscription($subscription, $atPeriodEnd);

            $message = $atPeriodEnd 
                ? 'Subscription will be cancelled at the end of the billing period'
                : 'Subscription has been cancelled immediately';

            return response()->json([
                'message' => $message,
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume cancelled subscription.
     */
    public function resume(): JsonResponse
    {
        $tenant = Auth::user()->currentTenant;
        
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        if (!$subscription || !$subscription->isCancelled()) {
            return response()->json([
                'message' => 'No cancelled subscription found',
            ], 404);
        }

        try {
            $subscription = $this->subscriptionService->resumeSubscription($subscription);

            return response()->json([
                'message' => 'Subscription has been resumed',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resume subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update payment method.
     */
    public function updatePaymentMethod(UpdatePaymentMethodRequest $request): JsonResponse
    {
        $tenant = Auth::user()->currentTenant;
        
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 404);
        }

        try {
            $subscription = $this->subscriptionService->updatePaymentMethod(
                $subscription,
                $request->payment_method_id
            );

            return response()->json([
                'message' => 'Payment method updated successfully',
                'payment_method' => $subscription->getPaymentMethodDisplay(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update payment method',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get proration estimate for plan change.
     */
    public function previewChange(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $tenant = Auth::user()->currentTenant;
        
        $subscription = Subscription::where('tenant_id', $tenant->id)->first();
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 404);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $proration = $this->subscriptionService->calculateProration($subscription, $newPlan);

            return response()->json([
                'proration' => $proration,
                'current_plan' => $subscription->plan->name,
                'new_plan' => $newPlan->name,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to calculate proration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get billing history.
     */
    public function invoices(): JsonResponse
    {
        $tenant = Auth::user()->currentTenant;
        
        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json($invoices);
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoice(string $invoiceId)
    {
        $tenant = Auth::user()->currentTenant;
        
        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->where('id', $invoiceId)
            ->firstOrFail();

        if (!$invoice->pdf_url) {
            return response()->json([
                'message' => 'Invoice PDF not available',
            ], 404);
        }

        return redirect()->away($invoice->pdf_url);
    }

    /**
     * Get Stripe publishable key.
     */
    public function getStripeKey(): JsonResponse
    {
        return response()->json([
            'key' => config('services.stripe.key'),
        ]);
    }
}
