# Integration Guide

## Overview

The Component Library System supports integration with various CRM platforms, third-party services, and external systems through webhooks, APIs, and event-driven architecture. This guide covers all available integration options and implementation details.

## CRM Integrations

### Supported CRM Platforms

- **Salesforce**: Full API integration with lead management
- **HubSpot**: Contact creation and deal tracking
- **Pipedrive**: Lead capture and pipeline management
- **Zoho CRM**: Contact and opportunity management
- **Custom CRM**: Generic webhook integration for any CRM

### Salesforce Integration

#### Setup

1. **Create Connected App in Salesforce**
```xml
<!-- Connected App Configuration -->
<ConnectedApp>
    <fullName>ComponentLibraryIntegration</fullName>
    <label>Component Library Integration</label>
    <contactEmail>admin@yourcompany.com</contactEmail>
    <oauthConfig>
        <callbackUrl>https://yourapp.com/auth/salesforce/callback</callbackUrl>
        <scopes>Api</scopes>
        <scopes>Web</scopes>
        <scopes>RefreshToken</scopes>
    </oauthConfig>
</ConnectedApp>
```

2. **Configure Environment Variables**
```env
SALESFORCE_CLIENT_ID=your_client_id
SALESFORCE_CLIENT_SECRET=your_client_secret
SALESFORCE_REDIRECT_URI=https://yourapp.com/auth/salesforce/callback
SALESFORCE_SANDBOX=true  # Set to false for production
```

3. **Install Salesforce Service**
```bash
composer require salesforce/salesforce-php-sdk
```

#### Implementation

```php
<?php
// app/Services/CRM/SalesforceService.php

namespace App\Services\CRM;

use App\Contracts\CRMServiceInterface;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SalesforceService implements CRMServiceInterface
{
    private string $instanceUrl;
    private string $accessToken;
    
    public function __construct()
    {
        $this->authenticate();
    }
    
    public function createLead(array $leadData): array
    {
        $salesforceData = $this->mapToSalesforceFields($leadData);
        
        $response = Http::withToken($this->accessToken)
            ->post("{$this->instanceUrl}/services/data/v58.0/sobjects/Lead/", $salesforceData);
            
        if ($response->successful()) {
            return [
                'success' => true,
                'crm_id' => $response->json('id'),
                'message' => 'Lead created successfully in Salesforce'
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->json('message', 'Unknown error'),
            'details' => $response->json()
        ];
    }
    
    public function updateLead(string $crmId, array $leadData): array
    {
        $salesforceData = $this->mapToSalesforceFields($leadData);
        
        $response = Http::withToken($this->accessToken)
            ->patch("{$this->instanceUrl}/services/data/v58.0/sobjects/Lead/{$crmId}", $salesforceData);
            
        return [
            'success' => $response->successful(),
            'message' => $response->successful() ? 'Lead updated successfully' : $response->json('message')
        ];
    }
    
    public function getLead(string $crmId): ?array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->instanceUrl}/services/data/v58.0/sobjects/Lead/{$crmId}");
            
        return $response->successful() ? $response->json() : null;
    }
    
    private function authenticate(): void
    {
        $cachedToken = Cache::get('salesforce_access_token');
        
        if ($cachedToken) {
            $this->accessToken = $cachedToken['access_token'];
            $this->instanceUrl = $cachedToken['instance_url'];
            return;
        }
        
        $response = Http::asForm()->post('https://login.salesforce.com/services/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.salesforce.client_id'),
            'client_secret' => config('services.salesforce.client_secret'),
        ]);
        
        if ($response->successful()) {
            $tokenData = $response->json();
            $this->accessToken = $tokenData['access_token'];
            $this->instanceUrl = $tokenData['instance_url'];
            
            Cache::put('salesforce_access_token', $tokenData, now()->addHours(2));
        } else {
            throw new \Exception('Failed to authenticate with Salesforce: ' . $response->body());
        }
    }
    
    private function mapToSalesforceFields(array $leadData): array
    {
        return [
            'FirstName' => $leadData['first_name'] ?? '',
            'LastName' => $leadData['last_name'] ?? 'Unknown',
            'Email' => $leadData['email'] ?? '',
            'Phone' => $leadData['phone'] ?? '',
            'Company' => $leadData['company'] ?? 'Unknown',
            'Title' => $leadData['job_title'] ?? '',
            'LeadSource' => 'Component Library',
            'Status' => 'New',
            'Description' => $leadData['message'] ?? '',
            // Custom fields
            'Alumni_Network__c' => $leadData['alumni_network'] ?? false,
            'Graduation_Year__c' => $leadData['graduation_year'] ?? null,
            'Industry__c' => $leadData['industry'] ?? '',
            'Component_Source__c' => $leadData['component_name'] ?? '',
            'Page_URL__c' => $leadData['page_url'] ?? '',
            'UTM_Source__c' => $leadData['utm_source'] ?? '',
            'UTM_Medium__c' => $leadData['utm_medium'] ?? '',
            'UTM_Campaign__c' => $leadData['utm_campaign'] ?? '',
        ];
    }
}
```

#### Form Component Integration

```vue
<template>
  <form @submit.prevent="submitForm" class="crm-form">
    <div class="form-group">
      <label for="firstName">First Name *</label>
      <input 
        id="firstName"
        v-model="form.first_name" 
        type="text" 
        required
        :class="{ 'error': errors.first_name }"
      />
      <span v-if="errors.first_name" class="error-message">{{ errors.first_name }}</span>
    </div>
    
    <div class="form-group">
      <label for="lastName">Last Name *</label>
      <input 
        id="lastName"
        v-model="form.last_name" 
        type="text" 
        required
        :class="{ 'error': errors.last_name }"
      />
    </div>
    
    <div class="form-group">
      <label for="email">Email *</label>
      <input 
        id="email"
        v-model="form.email" 
        type="email" 
        required
        :class="{ 'error': errors.email }"
      />
    </div>
    
    <div class="form-group">
      <label for="company">Company</label>
      <input 
        id="company"
        v-model="form.company" 
        type="text"
      />
    </div>
    
    <div class="form-group">
      <label for="graduationYear">Graduation Year</label>
      <select id="graduationYear" v-model="form.graduation_year">
        <option value="">Select Year</option>
        <option v-for="year in graduationYears" :key="year" :value="year">
          {{ year }}
        </option>
      </select>
    </div>
    
    <button 
      type="submit" 
      :disabled="submitting"
      class="submit-button"
    >
      {{ submitting ? 'Submitting...' : 'Submit' }}
    </button>
    
    <div v-if="submitMessage" :class="submitMessageClass">
      {{ submitMessage }}
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

interface FormData {
  first_name: string
  last_name: string
  email: string
  company: string
  phone: string
  graduation_year: string
  message: string
  utm_source: string
  utm_medium: string
  utm_campaign: string
}

const props = defineProps<{
  config: {
    crm_integration: string
    success_message: string
    error_message: string
    redirect_url?: string
  }
}>()

const form = ref<FormData>({
  first_name: '',
  last_name: '',
  email: '',
  company: '',
  phone: '',
  graduation_year: '',
  message: '',
  utm_source: new URLSearchParams(window.location.search).get('utm_source') || '',
  utm_medium: new URLSearchParams(window.location.search).get('utm_medium') || '',
  utm_campaign: new URLSearchParams(window.location.search).get('utm_campaign') || ''
})

const errors = ref<Record<string, string>>({})
const submitting = ref(false)
const submitMessage = ref('')
const submitSuccess = ref(false)

const graduationYears = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let year = currentYear; year >= currentYear - 50; year--) {
    years.push(year)
  }
  return years
})

const submitMessageClass = computed(() => ({
  'success-message': submitSuccess.value,
  'error-message': !submitSuccess.value
}))

const submitForm = async () => {
  submitting.value = true
  errors.value = {}
  submitMessage.value = ''
  
  try {
    const response = await fetch('/api/v1/leads', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        ...form.value,
        crm_integration: props.config.crm_integration,
        component_name: 'Lead Capture Form',
        page_url: window.location.href
      })
    })
    
    const result = await response.json()
    
    if (response.ok && result.success) {
      submitSuccess.value = true
      submitMessage.value = props.config.success_message || 'Thank you! We\'ll be in touch soon.'
      
      // Reset form
      Object.keys(form.value).forEach(key => {
        if (!key.startsWith('utm_')) {
          form.value[key as keyof FormData] = ''
        }
      })
      
      // Redirect if configured
      if (props.config.redirect_url) {
        setTimeout(() => {
          window.location.href = props.config.redirect_url!
        }, 2000)
      }
      
      // Track conversion
      trackConversion('form_submit', {
        crm_integration: props.config.crm_integration,
        crm_id: result.crm_id
      })
      
    } else {
      submitSuccess.value = false
      submitMessage.value = result.message || props.config.error_message || 'An error occurred. Please try again.'
      
      if (result.errors) {
        errors.value = result.errors
      }
    }
  } catch (error) {
    submitSuccess.value = false
    submitMessage.value = props.config.error_message || 'Network error. Please check your connection and try again.'
  } finally {
    submitting.value = false
  }
}

const trackConversion = (event: string, data: any) => {
  // Analytics tracking implementation
  if (typeof gtag !== 'undefined') {
    gtag('event', 'conversion', {
      event_category: 'CRM Integration',
      event_label: props.config.crm_integration,
      value: 1,
      ...data
    })
  }
}
</script>
```

### HubSpot Integration

#### Setup and Configuration

```php
<?php
// app/Services/CRM/HubSpotService.php

namespace App\Services\CRM;

use App\Contracts\CRMServiceInterface;
use Illuminate\Support\Facades\Http;

class HubSpotService implements CRMServiceInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.hubapi.com';
    
    public function __construct()
    {
        $this->apiKey = config('services.hubspot.api_key');
    }
    
    public function createLead(array $leadData): array
    {
        $hubspotData = $this->mapToHubSpotProperties($leadData);
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/crm/v3/objects/contacts", [
            'properties' => $hubspotData
        ]);
        
        if ($response->successful()) {
            $contactId = $response->json('id');
            
            // Create associated deal if needed
            if (!empty($leadData['deal_value'])) {
                $this->createDeal($contactId, $leadData);
            }
            
            return [
                'success' => true,
                'crm_id' => $contactId,
                'message' => 'Contact created successfully in HubSpot'
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->json('message', 'Unknown error'),
            'details' => $response->json()
        ];
    }
    
    private function createDeal(string $contactId, array $leadData): void
    {
        $dealData = [
            'properties' => [
                'dealname' => $leadData['deal_name'] ?? 'Alumni Network Lead',
                'dealstage' => 'appointmentscheduled',
                'pipeline' => 'default',
                'amount' => $leadData['deal_value'] ?? 0,
                'closedate' => now()->addDays(30)->format('Y-m-d'),
                'hubspot_owner_id' => config('services.hubspot.default_owner_id')
            ],
            'associations' => [
                [
                    'to' => ['id' => $contactId],
                    'types' => [
                        [
                            'associationCategory' => 'HUBSPOT_DEFINED',
                            'associationTypeId' => 3  // Contact to Deal association
                        ]
                    ]
                ]
            ]
        ];
        
        Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json'
        ])->post("{$this->baseUrl}/crm/v3/objects/deals", $dealData);
    }
    
    private function mapToHubSpotProperties(array $leadData): array
    {
        return [
            'firstname' => $leadData['first_name'] ?? '',
            'lastname' => $leadData['last_name'] ?? '',
            'email' => $leadData['email'] ?? '',
            'phone' => $leadData['phone'] ?? '',
            'company' => $leadData['company'] ?? '',
            'jobtitle' => $leadData['job_title'] ?? '',
            'lifecyclestage' => 'lead',
            'lead_source' => 'Component Library',
            'hs_lead_status' => 'NEW',
            'message' => $leadData['message'] ?? '',
            // Custom properties
            'alumni_network' => $leadData['alumni_network'] ?? false,
            'graduation_year' => $leadData['graduation_year'] ?? '',
            'industry' => $leadData['industry'] ?? '',
            'component_source' => $leadData['component_name'] ?? '',
            'original_source_url' => $leadData['page_url'] ?? '',
            'utm_source' => $leadData['utm_source'] ?? '',
            'utm_medium' => $leadData['utm_medium'] ?? '',
            'utm_campaign' => $leadData['utm_campaign'] ?? '',
        ];
    }
}
```

## Webhook System

### Webhook Configuration

The system supports outgoing webhooks for real-time notifications:

```php
<?php
// app/Models/Webhook.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'retry_count',
        'last_success_at',
        'last_failure_at'
    ];
    
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime'
    ];
    
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### Webhook Service

```php
<?php
// app/Services/WebhookService.php

namespace App\Services;

use App\Models\Webhook;
use App\Jobs\SendWebhookJob;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function dispatch(string $event, array $data, ?int $tenantId = null): void
    {
        $webhooks = Webhook::query()
            ->where('is_active', true)
            ->when($tenantId, fn($query) => $query->where('tenant_id', $tenantId))
            ->whereJsonContains('events', $event)
            ->get();
            
        foreach ($webhooks as $webhook) {
            SendWebhookJob::dispatch($webhook, $event, $data);
        }
    }
    
    public function createWebhook(array $data): Webhook
    {
        return Webhook::create([
            'tenant_id' => $data['tenant_id'],
            'name' => $data['name'],
            'url' => $data['url'],
            'events' => $data['events'],
            'secret' => $data['secret'] ?? $this->generateSecret(),
            'is_active' => $data['is_active'] ?? true
        ]);
    }
    
    private function generateSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(32));
    }
}
```

### Webhook Job

```php
<?php
// app/Jobs/SendWebhookJob.php

namespace App\Jobs;

use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $backoff = 60; // seconds
    
    public function __construct(
        private Webhook $webhook,
        private string $event,
        private array $data
    ) {}
    
    public function handle(): void
    {
        $payload = [
            'event' => $this->event,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
            'webhook_id' => $this->webhook->id
        ];
        
        $signature = $this->generateSignature($payload);
        
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->event,
                    'User-Agent' => 'ComponentLibrary-Webhook/1.0'
                ])
                ->post($this->webhook->url, $payload);
                
            if ($response->successful()) {
                $this->webhook->update([
                    'last_success_at' => now(),
                    'retry_count' => 0
                ]);
                
                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $this->webhook->id,
                    'event' => $this->event,
                    'status_code' => $response->status()
                ]);
            } else {
                $this->handleFailure($response->status(), $response->body());
            }
        } catch (\Exception $e) {
            $this->handleFailure(0, $e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }
    
    private function generateSignature(array $payload): string
    {
        $jsonPayload = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $jsonPayload, $this->webhook->secret);
    }
    
    private function handleFailure(int $statusCode, string $error): void
    {
        $this->webhook->increment('retry_count');
        $this->webhook->update(['last_failure_at' => now()]);
        
        Log::error('Webhook delivery failed', [
            'webhook_id' => $this->webhook->id,
            'event' => $this->event,
            'status_code' => $statusCode,
            'error' => $error,
            'retry_count' => $this->webhook->retry_count
        ]);
        
        // Disable webhook after too many failures
        if ($this->webhook->retry_count >= 10) {
            $this->webhook->update(['is_active' => false]);
            Log::warning('Webhook disabled due to repeated failures', [
                'webhook_id' => $this->webhook->id
            ]);
        }
    }
}
```

## Third-Party Service Integrations

### Email Marketing Platforms

#### Mailchimp Integration

```php
<?php
// app/Services/EmailMarketing/MailchimpService.php

namespace App\Services\EmailMarketing;

use Illuminate\Support\Facades\Http;

class MailchimpService
{
    private string $apiKey;
    private string $serverPrefix;
    private string $baseUrl;
    
    public function __construct()
    {
        $this->apiKey = config('services.mailchimp.api_key');
        $this->serverPrefix = substr($this->apiKey, strpos($this->apiKey, '-') + 1);
        $this->baseUrl = "https://{$this->serverPrefix}.api.mailchimp.com/3.0";
    }
    
    public function addSubscriber(string $listId, array $subscriberData): array
    {
        $response = Http::withBasicAuth('user', $this->apiKey)
            ->post("{$this->baseUrl}/lists/{$listId}/members", [
                'email_address' => $subscriberData['email'],
                'status' => 'subscribed',
                'merge_fields' => [
                    'FNAME' => $subscriberData['first_name'] ?? '',
                    'LNAME' => $subscriberData['last_name'] ?? '',
                    'COMPANY' => $subscriberData['company'] ?? '',
                    'GRADYEAR' => $subscriberData['graduation_year'] ?? '',
                ],
                'tags' => $subscriberData['tags'] ?? [],
                'interests' => $subscriberData['interests'] ?? []
            ]);
            
        return [
            'success' => $response->successful(),
            'subscriber_id' => $response->json('id'),
            'message' => $response->successful() ? 'Subscriber added successfully' : $response->json('detail')
        ];
    }
}
```

### Analytics Platforms

#### Google Analytics 4 Integration

```javascript
// resources/js/analytics/ga4.js

class GA4Analytics {
    constructor(measurementId) {
        this.measurementId = measurementId;
        this.initialize();
    }
    
    initialize() {
        // Load GA4 script
        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${this.measurementId}`;
        document.head.appendChild(script);
        
        // Initialize gtag
        window.dataLayer = window.dataLayer || [];
        window.gtag = function() { dataLayer.push(arguments); };
        gtag('js', new Date());
        gtag('config', this.measurementId, {
            page_title: document.title,
            page_location: window.location.href
        });
    }
    
    trackComponentView(componentData) {
        gtag('event', 'component_view', {
            event_category: 'Component Library',
            event_label: componentData.name,
            component_category: componentData.category,
            component_type: componentData.type,
            page_location: window.location.href,
            custom_parameters: {
                tenant_id: componentData.tenant_id,
                component_id: componentData.id
            }
        });
    }
    
    trackComponentClick(componentData, element) {
        gtag('event', 'component_click', {
            event_category: 'Component Library',
            event_label: `${componentData.name} - ${element}`,
            component_category: componentData.category,
            element_type: element,
            value: 1
        });
    }
    
    trackFormSubmission(formData) {
        gtag('event', 'form_submit', {
            event_category: 'Lead Generation',
            event_label: formData.form_name,
            form_type: formData.type,
            crm_integration: formData.crm_integration,
            value: formData.estimated_value || 1
        });
    }
    
    trackConversion(conversionData) {
        gtag('event', 'conversion', {
            event_category: 'Component Library',
            event_label: conversionData.component_name,
            transaction_id: conversionData.transaction_id,
            value: conversionData.value,
            currency: 'USD',
            items: [{
                item_id: conversionData.component_id,
                item_name: conversionData.component_name,
                item_category: conversionData.component_category,
                quantity: 1,
                price: conversionData.value
            }]
        });
    }
}

// Initialize analytics
const analytics = new GA4Analytics(window.GA4_MEASUREMENT_ID);

// Export for use in components
window.ComponentAnalytics = analytics;
```

### Social Media Integrations

#### LinkedIn Lead Gen Forms

```php
<?php
// app/Services/SocialMedia/LinkedInService.php

namespace App\Services\SocialMedia;

use Illuminate\Support\Facades\Http;

class LinkedInService
{
    private string $accessToken;
    private string $baseUrl = 'https://api.linkedin.com/v2';
    
    public function __construct()
    {
        $this->accessToken = config('services.linkedin.access_token');
    }
    
    public function getLeadGenForms(string $sponsoredAccount): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/leadGenForms", [
                'q' => 'sponsoredAccount',
                'sponsoredAccount' => $sponsoredAccount
            ]);
            
        return $response->successful() ? $response->json('elements', []) : [];
    }
    
    public function getFormResponses(string $formId): array
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/leadGenFormResponses", [
                'q' => 'form',
                'form' => $formId
            ]);
            
        return $response->successful() ? $response->json('elements', []) : [];
    }
    
    public function processFormResponse(array $response): array
    {
        $leadData = [];
        
        foreach ($response['formResponse']['answers'] as $answer) {
            $questionId = $answer['question'];
            $value = $answer['answer'] ?? '';
            
            // Map LinkedIn form fields to our lead structure
            switch ($questionId) {
                case 'firstName':
                    $leadData['first_name'] = $value;
                    break;
                case 'lastName':
                    $leadData['last_name'] = $value;
                    break;
                case 'emailAddress':
                    $leadData['email'] = $value;
                    break;
                case 'phoneNumber':
                    $leadData['phone'] = $value;
                    break;
                case 'company':
                    $leadData['company'] = $value;
                    break;
                case 'jobTitle':
                    $leadData['job_title'] = $value;
                    break;
            }
        }
        
        return $leadData;
    }
}
```

## API Integration Examples

### REST API Client

```typescript
// resources/js/services/ApiClient.ts

interface ApiResponse<T = any> {
  data: T;
  meta?: {
    pagination?: {
      current_page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  };
  links?: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status: number;
}

class ApiClient {
  private baseUrl: string;
  private token: string | null = null;
  
  constructor(baseUrl: string) {
    this.baseUrl = baseUrl.replace(/\/$/, '');
  }
  
  setToken(token: string): void {
    this.token = token;
  }
  
  private async request<T>(
    method: string,
    endpoint: string,
    data?: any,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers
    };
    
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }
    
    const config: RequestInit = {
      method,
      headers,
      ...options
    };
    
    if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
      config.body = JSON.stringify(data);
    }
    
    try {
      const response = await fetch(url, config);
      const responseData = await response.json();
      
      if (!response.ok) {
        throw new ApiError(
          responseData.message || 'An error occurred',
          responseData.errors,
          response.status
        );
      }
      
      return responseData;
    } catch (error) {
      if (error instanceof ApiError) {
        throw error;
      }
      
      throw new ApiError(
        'Network error occurred',
        undefined,
        0
      );
    }
  }
  
  // Component methods
  async getComponents(params?: {
    category?: string;
    search?: string;
    per_page?: number;
    sort?: string;
    direction?: 'asc' | 'desc';
  }): Promise<ApiResponse<Component[]>> {
    const queryString = params ? '?' + new URLSearchParams(params).toString() : '';
    return this.request<Component[]>('GET', `/components${queryString}`);
  }
  
  async getComponent(id: number): Promise<ApiResponse<Component>> {
    return this.request<Component>('GET', `/components/${id}`);
  }
  
  async createComponent(data: Partial<Component>): Promise<ApiResponse<Component>> {
    return this.request<Component>('POST', '/components', data);
  }
  
  async updateComponent(id: number, data: Partial<Component>): Promise<ApiResponse<Component>> {
    return this.request<Component>('PUT', `/components/${id}`, data);
  }
  
  async deleteComponent(id: number): Promise<ApiResponse<{ message: string }>> {
    return this.request<{ message: string }>('DELETE', `/components/${id}`);
  }
  
  // Theme methods
  async getThemes(): Promise<ApiResponse<Theme[]>> {
    return this.request<Theme[]>('GET', '/component-themes');
  }
  
  async createTheme(data: Partial<Theme>): Promise<ApiResponse<Theme>> {
    return this.request<Theme>('POST', '/component-themes', data);
  }
  
  // Media methods
  async uploadMedia(file: File, metadata?: {
    type?: string;
    alt_text?: string;
    caption?: string;
  }): Promise<ApiResponse<MediaFile>> {
    const formData = new FormData();
    formData.append('file', file);
    
    if (metadata) {
      Object.entries(metadata).forEach(([key, value]) => {
        if (value) formData.append(key, value);
      });
    }
    
    return this.request<MediaFile>('POST', '/component-media', formData, {
      headers: {} // Let browser set Content-Type for FormData
    });
  }
  
  // Analytics methods
  async trackEvent(data: {
    component_instance_id: number;
    event_type: string;
    data?: any;
    user_id?: number;
    session_id?: string;
  }): Promise<ApiResponse<{ message: string }>> {
    return this.request<{ message: string }>('POST', '/component-analytics/track', data);
  }
  
  async getAnalytics(params?: {
    component_id?: number;
    event_type?: string;
    date_from?: string;
    date_to?: string;
    group_by?: 'day' | 'week' | 'month';
  }): Promise<ApiResponse<AnalyticsData>> {
    const queryString = params ? '?' + new URLSearchParams(params).toString() : '';
    return this.request<AnalyticsData>('GET', `/component-analytics${queryString}`);
  }
}

// Create and export API client instance
export const apiClient = new ApiClient('/api/v1');

// Set token from meta tag or localStorage
const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') ||
              localStorage.getItem('api_token');

if (token) {
  apiClient.setToken(token);
}

export default apiClient;
```

## Error Handling and Monitoring

### Integration Error Handling

```php
<?php
// app/Exceptions/IntegrationException.php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class IntegrationException extends Exception
{
    private string $integration;
    private array $context;
    
    public function __construct(
        string $integration,
        string $message,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        $this->integration = $integration;
        $this->context = $context;
        
        parent::__construct($message, $code, $previous);
    }
    
    public function report(): void
    {
        Log::error("Integration failure: {$this->integration}", [
            'message' => $this->getMessage(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ]);
        
        // Send to monitoring service
        if (config('services.sentry.dsn')) {
            app('sentry')->captureException($this);
        }
    }
    
    public function getIntegration(): string
    {
        return $this->integration;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
}
```

### Integration Health Monitoring

```php
<?php
// app/Console/Commands/CheckIntegrationHealth.php

namespace App\Console\Commands;

use App\Services\CRM\SalesforceService;
use App\Services\CRM\HubSpotService;
use App\Services\EmailMarketing\MailchimpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\IntegrationHealthAlert;

class CheckIntegrationHealth extends Command
{
    protected $signature = 'integrations:health-check';
    protected $description = 'Check the health of all integrations';
    
    public function handle(): void
    {
        $integrations = [
            'salesforce' => SalesforceService::class,
            'hubspot' => HubSpotService::class,
            'mailchimp' => MailchimpService::class,
        ];
        
        $results = [];
        
        foreach ($integrations as $name => $serviceClass) {
            $this->info("Checking {$name}...");
            
            try {
                $service = app($serviceClass);
                $health = $this->checkServiceHealth($service);
                
                $results[$name] = $health;
                
                if ($health['status'] === 'healthy') {
                    $this->info("✓ {$name} is healthy");
                } else {
                    $this->error("✗ {$name} is unhealthy: {$health['message']}");
                }
                
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'checked_at' => now()
                ];
                
                $this->error("✗ {$name} error: {$e->getMessage()}");
            }
        }
        
        // Store results in cache
        Cache::put('integration_health_check', $results, now()->addHours(1));
        
        // Send alerts for unhealthy integrations
        $unhealthy = collect($results)->filter(fn($result) => $result['status'] !== 'healthy');
        
        if ($unhealthy->isNotEmpty()) {
            Notification::route('mail', config('app.admin_email'))
                ->notify(new IntegrationHealthAlert($unhealthy->toArray()));
        }
        
        $this->info('Health check completed.');
    }
    
    private function checkServiceHealth($service): array
    {
        // Implement health check logic for each service type
        if (method_exists($service, 'healthCheck')) {
            return $service->healthCheck();
        }
        
        // Default health check - try to authenticate
        try {
            if (method_exists($service, 'authenticate')) {
                $service->authenticate();
            }
            
            return [
                'status' => 'healthy',
                'message' => 'Service is responding',
                'checked_at' => now()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'checked_at' => now()
            ];
        }
    }
}
```

This comprehensive integration guide covers all major CRM platforms, webhook systems, third-party services, and monitoring capabilities needed for the Component Library System.