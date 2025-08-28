<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrmIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CrmWebhookController extends Controller
{
    public function __construct(
        private CrmIntegrationService $crmService
    ) {}

    /**
     * Handle HubSpot webhook
     */
    public function hubspot(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('HubSpot webhook received', [
                'headers' => $request->headers->all(),
                'payload_keys' => array_keys($payload)
            ]);

            // Validate HubSpot webhook signature
            if (!$this->validateHubSpotSignature($request)) {
                Log::warning('Invalid HubSpot webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Process webhook
            $result = $this->crmService->processWebhook('hubspot', $payload);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('HubSpot webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Handle Salesforce webhook
     */
    public function salesforce(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('Salesforce webhook received', [
                'headers' => $request->headers->all(),
                'payload_keys' => array_keys($payload)
            ]);

            // Validate Salesforce webhook
            if (!$this->validateSalesforceWebhook($request)) {
                Log::warning('Invalid Salesforce webhook');
                return response()->json(['error' => 'Invalid webhook'], 401);
            }

            // Process webhook
            $result = $this->crmService->processWebhook('salesforce', $payload);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Salesforce webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Handle Pipedrive webhook
     */
    public function pipedrive(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('Pipedrive webhook received', [
                'headers' => $request->headers->all(),
                'payload_keys' => array_keys($payload)
            ]);

            // Validate Pipedrive webhook
            if (!$this->validatePipedriveWebhook($request)) {
                Log::warning('Invalid Pipedrive webhook');
                return response()->json(['error' => 'Invalid webhook'], 401);
            }

            // Process webhook
            $result = $this->crmService->processWebhook('pipedrive', $payload);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Pipedrive webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Generic webhook handler for other CRM providers
     */
    public function generic(Request $request, string $provider): JsonResponse
    {
        try {
            $payload = $request->all();
            
            Log::info('Generic CRM webhook received', [
                'provider' => $provider,
                'headers' => $request->headers->all(),
                'payload_keys' => array_keys($payload)
            ]);

            // Basic validation - in production, implement provider-specific validation
            if (empty($payload)) {
                return response()->json(['error' => 'Empty payload'], 400);
            }

            // Process webhook
            $result = $this->crmService->processWebhook($provider, $payload);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Generic CRM webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Validate HubSpot webhook signature
     */
    private function validateHubSpotSignature(Request $request): bool
    {
        $signature = $request->header('X-HubSpot-Signature-v3');
        $timestamp = $request->header('X-HubSpot-Request-Timestamp');
        
        if (!$signature || !$timestamp) {
            return false;
        }

        // Get webhook secret from config
        $secret = config('services.hubspot.webhook_secret');
        if (!$secret) {
            Log::warning('HubSpot webhook secret not configured');
            return true; // Allow in development
        }

        // Validate timestamp (within 5 minutes)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = hash('sha256', 'v3' . $timestamp . $payload . $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate Salesforce webhook
     */
    private function validateSalesforceWebhook(Request $request): bool
    {
        // Salesforce uses IP allowlisting and HTTPS
        // In production, implement proper Salesforce webhook validation
        $userAgent = $request->userAgent();
        
        // Basic validation - check if request comes from Salesforce
        if (str_contains($userAgent, 'Salesforce')) {
            return true;
        }

        // Check for Salesforce-specific headers
        if ($request->hasHeader('X-Salesforce-SIP')) {
            return true;
        }

        return config('app.env') === 'local'; // Allow in development
    }

    /**
     * Validate Pipedrive webhook
     */
    private function validatePipedriveWebhook(Request $request): bool
    {
        // Pipedrive doesn't use signature validation by default
        // Implement IP allowlisting or custom validation as needed
        
        $userAgent = $request->userAgent();
        
        // Basic validation
        if (str_contains($userAgent, 'Pipedrive')) {
            return true;
        }

        // Check for required fields in payload
        $payload = $request->all();
        if (isset($payload['event']) && isset($payload['current'])) {
            return true;
        }

        return config('app.env') === 'local'; // Allow in development
    }
}