<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateWebhookRequest;
use App\Http\Requests\Api\UpdateWebhookRequest;
use App\Http\Resources\WebhookResource;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(
        private WebhookService $webhookService
    ) {}

    /**
     * Display a listing of webhooks for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('user_id', $request->user()->id)
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('event'), function ($query) use ($request) {
                $query->whereJsonContains('events', $request->event);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => WebhookResource::collection($webhooks),
            'meta' => [
                'pagination' => [
                    'current_page' => $webhooks->currentPage(),
                    'per_page' => $webhooks->perPage(),
                    'total' => $webhooks->total(),
                    'last_page' => $webhooks->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Store a newly created webhook.
     */
    public function store(CreateWebhookRequest $request): JsonResponse
    {
        $webhook = $this->webhookService->createWebhook(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => new WebhookResource($webhook),
            'message' => 'Webhook created successfully',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified webhook.
     */
    public function show(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('view', $webhook);

        return response()->json([
            'success' => true,
            'data' => new WebhookResource($webhook->load(['deliveries' => function ($query) {
                $query->latest()->limit(10);
            }])),
        ]);
    }

    /**
     * Update the specified webhook.
     */
    public function update(UpdateWebhookRequest $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $webhook = $this->webhookService->updateWebhook($webhook, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new WebhookResource($webhook),
            'message' => 'Webhook updated successfully',
        ]);
    }

    /**
     * Remove the specified webhook.
     */
    public function destroy(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('delete', $webhook);

        $this->webhookService->deleteWebhook($webhook);

        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Test a webhook by sending a test payload.
     */
    public function test(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $delivery = $this->webhookService->testWebhook($webhook);

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_id' => $delivery->id,
                'status' => $delivery->status,
                'response_code' => $delivery->response_code,
                'response_body' => $delivery->response_body,
            ],
            'message' => 'Test webhook sent successfully',
        ]);
    }

    /**
     * Get webhook delivery history.
     */
    public function deliveries(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('view', $webhook);

        $deliveries = $webhook->deliveries()
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('event'), function ($query) use ($request) {
                $query->where('event_type', $request->event);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $deliveries->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $deliveries->currentPage(),
                    'per_page' => $deliveries->perPage(),
                    'total' => $deliveries->total(),
                    'last_page' => $deliveries->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Retry a failed webhook delivery.
     */
    public function retryDelivery(Request $request, Webhook $webhook, $deliveryId): JsonResponse
    {
        $this->authorize('update', $webhook);

        $delivery = $webhook->deliveries()->findOrFail($deliveryId);

        $newDelivery = $this->webhookService->retryDelivery($delivery);

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_id' => $newDelivery->id,
                'status' => $newDelivery->status,
                'response_code' => $newDelivery->response_code,
            ],
            'message' => 'Webhook delivery retried successfully',
        ]);
    }

    /**
     * Get webhook statistics.
     */
    public function statistics(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('view', $webhook);

        $stats = $this->webhookService->getWebhookStatistics($webhook, [
            'period' => $request->get('period', '30d'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get available webhook events.
     */
    public function events(): JsonResponse
    {
        $events = $this->webhookService->getAvailableEvents();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Validate webhook URL.
     */
    public function validateUrl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $validation = $this->webhookService->validateWebhookUrl($request->url);

        return response()->json([
            'success' => true,
            'data' => $validation,
        ]);
    }

    /**
     * Pause webhook deliveries.
     */
    public function pause(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $this->webhookService->pauseWebhook($webhook);

        return response()->json([
            'success' => true,
            'message' => 'Webhook paused successfully',
        ]);
    }

    /**
     * Resume webhook deliveries.
     */
    public function resume(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $this->webhookService->resumeWebhook($webhook);

        return response()->json([
            'success' => true,
            'message' => 'Webhook resumed successfully',
        ]);
    }
}
