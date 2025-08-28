<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrmIntegrationService;
use App\Services\GdprComplianceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FormController extends Controller
{
    public function __construct(
        private CrmIntegrationService $crmService,
        private GdprComplianceService $gdprService
    ) {}
    /**
     * Handle form submission
     */
    public function submit(Request $request): JsonResponse
    {
        try {
            // Get form configuration
            $formConfig = $request->input('_form_config', []);
            $formData = $request->except(['_form_config', '_crm_config']);
            
            // Validate form data based on configuration
            $validator = $this->validateFormData($formData, $formConfig);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Process form submission
            $submissionId = $this->processFormSubmission($formData, $formConfig, $request);
            
            // Handle CRM integration if configured
            if ($request->has('_crm_config')) {
                $this->handleCrmIntegration($formData, $request->input('_crm_config'));
            }
            
            // Send notifications if configured
            if (isset($formConfig['notifications']) && $formConfig['notifications']['enabled']) {
                $this->sendFormNotifications($formData, $formConfig);
            }
            
            // Track form submission
            $this->trackFormSubmission($formData, $formConfig, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Form submitted successfully',
                'submission_id' => $submissionId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Form submission error: ' . $e->getMessage(), [
                'form_data' => $formData ?? null,
                'form_config' => $formConfig ?? null,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your form submission'
            ], 500);
        }
    }
    
    /**
     * Handle auto-save functionality
     */
    public function autoSave(Request $request): JsonResponse
    {
        try {
            $formData = $request->input('formData', []);
            $formConfig = $request->input('formConfig', []);
            $userId = auth()->id();
            
            // Create cache key for auto-save
            $cacheKey = "form_autosave_{$userId}_" . md5(json_encode($formConfig));
            
            // Store form data in cache for 24 hours
            Cache::put($cacheKey, [
                'form_data' => $formData,
                'form_config' => $formConfig,
                'timestamp' => now()->toISOString(),
                'user_id' => $userId
            ], now()->addHours(24));
            
            return response()->json([
                'success' => true,
                'message' => 'Form data auto-saved successfully',
                'cache_key' => $cacheKey
            ]);
            
        } catch (\Exception $e) {
            Log::error('Auto-save error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-save form data'
            ], 500);
        }
    }
    
    /**
     * Send form notifications
     */
    public function sendNotifications(Request $request): JsonResponse
    {
        try {
            $formData = $request->input('formData', []);
            $notifications = $request->input('notifications', []);
            
            if (!$notifications['enabled']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notifications disabled'
                ]);
            }
            
            // Send email notifications
            if (isset($notifications['recipients'])) {
                foreach ($notifications['recipients'] as $recipient) {
                    Mail::raw(
                        $this->formatNotificationMessage($formData, $notifications),
                        function ($message) use ($recipient, $notifications) {
                            $message->to($recipient)
                                   ->subject($notifications['subject'] ?? 'New Form Submission');
                        }
                    );
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Notifications sent successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Notification sending error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notifications'
            ], 500);
        }
    }
    
    /**
     * Validate form data based on configuration
     */
    private function validateFormData(array $formData, array $formConfig): \Illuminate\Validation\Validator
    {
        $rules = [];
        $messages = [];
        
        if (isset($formConfig['fields'])) {
            foreach ($formConfig['fields'] as $field) {
                $fieldRules = [];
                
                // Required validation
                if ($field['required'] ?? false) {
                    $fieldRules[] = 'required';
                }
                
                // Type-specific validation
                switch ($field['type']) {
                    case 'email':
                        $fieldRules[] = 'email';
                        break;
                    case 'phone':
                        $fieldRules[] = 'regex:/^[\+]?[1-9][\d]{0,15}$/';
                        break;
                    case 'url':
                        $fieldRules[] = 'url';
                        break;
                    case 'number':
                        $fieldRules[] = 'numeric';
                        if (isset($field['min'])) {
                            $fieldRules[] = 'min:' . $field['min'];
                        }
                        if (isset($field['max'])) {
                            $fieldRules[] = 'max:' . $field['max'];
                        }
                        break;
                }
                
                if (!empty($fieldRules)) {
                    $rules[$field['name']] = $fieldRules;
                }
                
                // Custom error messages
                if ($field['required'] ?? false) {
                    $messages[$field['name'] . '.required'] = $field['label'] . ' is required';
                }
                if ($field['type'] === 'email') {
                    $messages[$field['name'] . '.email'] = $field['label'] . ' must be a valid email address';
                }
            }
        }
        
        return Validator::make($formData, $rules, $messages);
    }
    
    /**
     * Process form submission
     */
    private function processFormSubmission(array $formData, array $formConfig, Request $request): string
    {
        // Generate submission ID
        $submissionId = 'form_' . uniqid() . '_' . time();
        
        // Store submission data (in a real app, you'd save to database)
        Cache::put("form_submission_{$submissionId}", [
            'id' => $submissionId,
            'form_data' => $formData,
            'form_config' => $formConfig,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submitted_at' => now()->toISOString()
        ], now()->addDays(30));
        
        return $submissionId;
    }
    
    /**
     * Handle individual signup form submission
     */
    public function submitIndividualSignup(\App\Http\Requests\Forms\IndividualSignupRequest $request): JsonResponse
    {
        try {
            $formData = $request->validated();
            
            // Process form submission
            $submissionId = $this->processFormSubmission($formData, ['template_id' => 'individual-signup'], $request);
            
            // Handle CRM integration
            $crmResult = $this->handleAdvancedCrmIntegration($formData, 'individual-signup');
            
            // Send notifications
            $this->sendTemplateNotifications($formData, 'individual-signup');
            
            // Track submission
            $this->trackFormSubmission($formData, ['template_id' => 'individual-signup'], $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Registration submitted successfully! Welcome to our alumni network.',
                'submission_id' => $submissionId,
                'crm_result' => $crmResult,
                'next_steps' => [
                    'Check your email for a confirmation message',
                    'Complete your profile setup',
                    'Explore networking opportunities'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Individual signup form submission error: " . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your registration. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle institution demo request form submission
     */
    public function submitInstitutionDemoRequest(\App\Http\Requests\Forms\InstitutionDemoRequest $request): JsonResponse
    {
        try {
            $formData = $request->validated();
            
            // Process form submission
            $submissionId = $this->processFormSubmission($formData, ['template_id' => 'institution-demo-request'], $request);
            
            // Handle CRM integration
            $crmResult = $this->handleAdvancedCrmIntegration($formData, 'institution-demo-request');
            
            // Send notifications
            $this->sendTemplateNotifications($formData, 'institution-demo-request');
            
            // Track submission
            $this->trackFormSubmission($formData, ['template_id' => 'institution-demo-request'], $request);
            
            // Calculate priority score for sales team
            $priorityScore = $this->calculateLeadScore($formData, 'institution-demo-request');
            
            return response()->json([
                'success' => true,
                'message' => 'Demo request submitted successfully! Our team will contact you soon.',
                'submission_id' => $submissionId,
                'crm_result' => $crmResult,
                'priority_score' => $priorityScore,
                'next_steps' => [
                    'Our sales team will review your request within 24 hours',
                    'You will receive a calendar link to schedule your demo',
                    'Prepare any specific questions about your use case'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Institution demo request form submission error: " . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your demo request. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle contact form submission
     */
    public function submitContactForm(\App\Http\Requests\Forms\ContactFormRequest $request): JsonResponse
    {
        try {
            $formData = $request->validated();
            
            // Process form submission
            $submissionId = $this->processFormSubmission($formData, ['template_id' => 'contact-general'], $request);
            
            // Handle CRM integration
            $crmResult = $this->handleAdvancedCrmIntegration($formData, 'contact-general');
            
            // Send notifications
            $this->sendTemplateNotifications($formData, 'contact-general');
            
            // Track submission
            $this->trackFormSubmission($formData, ['template_id' => 'contact-general'], $request);
            
            // Generate ticket number for tracking
            $ticketNumber = 'TICKET-' . strtoupper(substr($submissionId, -8));
            
            return response()->json([
                'success' => true,
                'message' => 'Your message has been sent successfully!',
                'submission_id' => $submissionId,
                'ticket_number' => $ticketNumber,
                'crm_result' => $crmResult,
                'estimated_response_time' => $this->getEstimatedResponseTime($formData),
                'next_steps' => [
                    'You will receive an email confirmation shortly',
                    'Our team will respond based on your priority level',
                    'Keep your ticket number for reference: ' . $ticketNumber
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("Contact form submission error: " . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending your message. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle dynamic form submission
     */
    public function submitDynamicForm(\App\Http\Requests\Forms\DynamicFormRequest $request): JsonResponse
    {
        try {
            $formData = $request->validated();
            $formConfig = $request->input('_form_config', []);
            
            // Process form submission
            $submissionId = $this->processFormSubmission($formData, $formConfig, $request);
            
            // Handle CRM integration if configured
            if (isset($formConfig['crm_integration']) && $formConfig['crm_integration']['enabled']) {
                $crmResult = $this->handleDynamicCrmIntegration($formData, $formConfig);
            }
            
            // Send notifications if configured
            if (isset($formConfig['notifications']) && $formConfig['notifications']['enabled']) {
                $this->sendDynamicFormNotifications($formData, $formConfig);
            }
            
            // Track submission
            $this->trackFormSubmission($formData, $formConfig, $request);
            
            return response()->json([
                'success' => true,
                'message' => $formConfig['success_message'] ?? 'Form submitted successfully!',
                'submission_id' => $submissionId,
                'crm_result' => $crmResult ?? null
            ]);
            
        } catch (\Exception $e) {
            Log::error("Dynamic form submission error: " . $e->getMessage(), [
                'form_config' => $formConfig ?? null,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your form submission. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle newsletter signup form submission
     */
    public function submitNewsletterSignup(Request $request): JsonResponse
    {
        return $this->handleTemplateSubmission($request, 'newsletter-signup', [
            'email' => 'required|email',
            'first_name' => 'nullable|string|max:50',
            'newsletter_interests' => 'nullable|array',
            'email_frequency' => 'nullable|in:weekly,monthly,quarterly'
        ]);
    }

    /**
     * Handle event registration form submission
     */
    public function submitEventRegistration(Request $request): JsonResponse
    {
        return $this->handleTemplateSubmission($request, 'event-registration', [
            'attendee_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|regex:/^[\+]?[1-9][\d]{0,15}$/',
            'graduation_year' => 'required|integer|min:1950|max:' . (date('Y') + 5),
            'guest_count' => 'nullable|integer|min:0|max:5',
            'dietary_restrictions' => 'nullable|string|max:500'
        ]);
    }

    /**
     * Handle template-specific form submission
     */
    private function handleTemplateSubmission(Request $request, string $templateId, array $validationRules): JsonResponse
    {
        try {
            // Validate form data
            $validator = Validator::make($request->all(), $validationRules);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $formData = $validator->validated();
            
            // Process form submission
            $submissionId = $this->processFormSubmission($formData, ['template_id' => $templateId], $request);
            
            // Handle CRM integration based on template
            $crmResult = $this->handleAdvancedCrmIntegration($formData, $templateId);
            
            // Send template-specific notifications
            $this->sendTemplateNotifications($formData, $templateId);
            
            // Track form submission
            $this->trackFormSubmission($formData, ['template_id' => $templateId], $request);
            
            return response()->json([
                'success' => true,
                'message' => $this->getSuccessMessage($templateId),
                'submission_id' => $submissionId,
                'crm_result' => $crmResult
            ]);
            
        } catch (\Exception $e) {
            Log::error("Form submission error for template {$templateId}: " . $e->getMessage(), [
                'template_id' => $templateId,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your form submission'
            ], 500);
        }
    }

    /**
     * Handle advanced CRM integration with template-specific logic
     */
    private function handleAdvancedCrmIntegration(array $formData, string $templateId): array
    {
        try {
            $crmConfig = $this->getCrmConfigForTemplate($templateId);
            
            if (!$crmConfig['enabled']) {
                return ['success' => true, 'message' => 'CRM integration disabled'];
            }

            // Map form data to CRM fields
            $mappedData = $this->mapFormDataToCrm($formData, $crmConfig['mapping']);
            
            // Calculate lead score
            $leadScore = $this->calculateLeadScore($formData, $templateId);
            
            // Add template-specific data
            $crmData = array_merge($mappedData, [
                'lead_score' => $leadScore,
                'source' => 'form_submission',
                'template_id' => $templateId,
                'tags' => $crmConfig['tags'],
                'submitted_at' => now()->toISOString()
            ]);

            // Send to CRM (mock implementation)
            $crmResponse = $this->sendToCrm($crmData, $crmConfig);
            
            Log::info('CRM integration successful', [
                'template_id' => $templateId,
                'provider' => $crmConfig['provider'],
                'lead_score' => $leadScore,
                'crm_response' => $crmResponse
            ]);
            
            return $crmResponse;
            
        } catch (\Exception $e) {
            Log::error('CRM integration failed: ' . $e->getMessage(), [
                'template_id' => $templateId,
                'form_data_keys' => array_keys($formData)
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get CRM configuration for specific template
     */
    private function getCrmConfigForTemplate(string $templateId): array
    {
        $configs = [
            'individual-signup' => [
                'enabled' => true,
                'provider' => 'hubspot',
                'endpoint' => '/api/crm/hubspot/contacts',
                'mapping' => [
                    'first_name' => 'firstname',
                    'last_name' => 'lastname',
                    'email' => 'email',
                    'phone' => 'phone',
                    'current_company' => 'company',
                    'current_job_title' => 'jobtitle',
                    'graduation_year' => 'hs_graduation_year',
                    'major' => 'hs_field_of_study',
                    'industry' => 'industry'
                ],
                'tags' => ['alumni', 'individual', 'signup', 'qualified']
            ],
            'institution-demo-request' => [
                'enabled' => true,
                'provider' => 'salesforce',
                'endpoint' => '/api/crm/salesforce/leads',
                'mapping' => [
                    'contact_name' => 'Name',
                    'contact_title' => 'Title',
                    'institution_name' => 'Company',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'institution_type' => 'Institution_Type__c',
                    'alumni_count' => 'Alumni_Count__c',
                    'budget_range' => 'Budget_Range__c',
                    'implementation_timeline' => 'Timeline__c',
                    'current_challenges' => 'Description'
                ],
                'tags' => ['institution', 'demo-request', 'qualified-lead', 'high-priority']
            ],
            'contact-general' => [
                'enabled' => true,
                'provider' => 'hubspot',
                'endpoint' => '/api/crm/hubspot/tickets',
                'mapping' => [
                    'name' => 'contact_name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'organization' => 'company',
                    'subject' => 'subject',
                    'message' => 'content',
                    'inquiry_category' => 'hs_ticket_category',
                    'priority_level' => 'hs_ticket_priority'
                ],
                'tags' => ['contact_form', 'support_inquiry']
            ],
            'newsletter-signup' => [
                'enabled' => true,
                'provider' => 'hubspot',
                'endpoint' => '/api/crm/hubspot/contacts',
                'mapping' => [
                    'email' => 'email',
                    'first_name' => 'firstname'
                ],
                'tags' => ['newsletter', 'subscriber']
            ],
            'event-registration' => [
                'enabled' => true,
                'provider' => 'hubspot',
                'endpoint' => '/api/crm/hubspot/contacts',
                'mapping' => [
                    'attendee_name' => 'name',
                    'email' => 'email',
                    'phone' => 'phone',
                    'graduation_year' => 'hs_graduation_year'
                ],
                'tags' => ['event_registration', 'attendee']
            ]
        ];

        return $configs[$templateId] ?? ['enabled' => false];
    }

    /**
     * Calculate lead score based on form data and template
     */
    private function calculateLeadScore(array $formData, string $templateId): int
    {
        $baseScore = 0;
        
        // Template-specific base scores
        $baseScores = [
            'individual-signup' => 60,
            'institution-demo-request' => 85,
            'contact-general' => 30,
            'newsletter-signup' => 25,
            'event-registration' => 40
        ];
        
        $score = $baseScores[$templateId] ?? 0;
        
        // Score based on completeness
        $totalFields = count($formData);
        $completedFields = count(array_filter($formData, function($value) {
            return !empty($value);
        }));
        
        $completenessScore = ($completedFields / $totalFields) * 20;
        $score += $completenessScore;
        
        // Template-specific scoring
        switch ($templateId) {
            case 'institution-demo-request':
                if (isset($formData['decision_role']) && $formData['decision_role'] === 'decision_maker') {
                    $score += 25;
                }
                if (isset($formData['budget_range']) && !str_contains($formData['budget_range'], '<')) {
                    $score += 20;
                }
                if (isset($formData['implementation_timeline']) && in_array($formData['implementation_timeline'], ['immediate', '1-3months'])) {
                    $score += 15;
                }
                break;
                
            case 'individual-signup':
                if (!empty($formData['current_company'])) {
                    $score += 15;
                }
                if (!empty($formData['current_job_title'])) {
                    $score += 10;
                }
                break;
                
            case 'contact-general':
                if (isset($formData['priority_level']) && $formData['priority_level'] === 'urgent') {
                    $score += 20;
                }
                if (isset($formData['inquiry_category']) && in_array($formData['inquiry_category'], ['sales', 'demo_request'])) {
                    $score += 15;
                }
                break;
        }
        
        return min(max($score, 0), 100); // Clamp between 0-100
    }

    /**
     * Map form data to CRM fields
     */
    private function mapFormDataToCrm(array $formData, array $mapping): array
    {
        $mappedData = [];
        
        foreach ($mapping as $formField => $crmField) {
            if (isset($formData[$formField])) {
                $mappedData[$crmField] = $formData[$formField];
            }
        }
        
        return $mappedData;
    }

    /**
     * Send data to CRM (mock implementation)
     */
    private function sendToCrm(array $data, array $config): array
    {
        // In a real implementation, this would make actual API calls to CRM systems
        Log::info('Sending data to CRM', [
            'provider' => $config['provider'],
            'endpoint' => $config['endpoint'],
            'data_keys' => array_keys($data)
        ]);
        
        return [
            'success' => true,
            'lead_id' => 'lead_' . uniqid(),
            'provider' => $config['provider']
        ];
    }

    /**
     * Send template-specific notifications
     */
    private function sendTemplateNotifications(array $formData, string $templateId): void
    {
        try {
            $notificationConfig = $this->getNotificationConfigForTemplate($templateId);
            
            if (!$notificationConfig['enabled']) {
                return;
            }
            
            $recipients = $this->determineNotificationRecipients($formData, $templateId);
            $subject = $this->processTemplateString($notificationConfig['subject'], $formData);
            $message = $this->formatTemplateNotificationMessage($formData, $templateId);
            
            foreach ($recipients as $recipient) {
                Mail::raw($message, function ($mail) use ($recipient, $subject) {
                    $mail->to($recipient)->subject($subject);
                });
            }
            
        } catch (\Exception $e) {
            Log::error('Template notification failed: ' . $e->getMessage(), [
                'template_id' => $templateId
            ]);
        }
    }

    /**
     * Get notification configuration for template
     */
    private function getNotificationConfigForTemplate(string $templateId): array
    {
        $configs = [
            'individual-signup' => [
                'enabled' => true,
                'subject' => 'New Alumni Registration: {{first_name}} {{last_name}}',
                'recipients' => ['alumni@company.com']
            ],
            'institution-demo-request' => [
                'enabled' => true,
                'subject' => 'High Priority Demo Request: {{institution_name}} ({{alumni_count}} alumni)',
                'recipients' => ['sales@company.com', 'demos@company.com']
            ],
            'contact-general' => [
                'enabled' => true,
                'subject' => '[{{priority_level|upper}}] {{inquiry_category|title}}: {{subject}}',
                'recipients' => [] // Dynamic routing
            ],
            'newsletter-signup' => [
                'enabled' => true,
                'subject' => 'New Newsletter Subscription',
                'recipients' => ['marketing@company.com']
            ],
            'event-registration' => [
                'enabled' => true,
                'subject' => 'New Event Registration: {{attendee_name}}',
                'recipients' => ['events@company.com']
            ]
        ];

        return $configs[$templateId] ?? ['enabled' => false];
    }

    /**
     * Determine notification recipients based on form data
     */
    private function determineNotificationRecipients(array $formData, string $templateId): array
    {
        $config = $this->getNotificationConfigForTemplate($templateId);
        $recipients = $config['recipients'] ?? [];
        
        // Dynamic routing for contact forms
        if ($templateId === 'contact-general' && isset($formData['inquiry_category'])) {
            $routingMap = [
                'technical_support' => ['support@company.com', 'tech@company.com'],
                'sales' => ['sales@company.com'],
                'demo_request' => ['sales@company.com', 'demos@company.com'],
                'partnership' => ['partnerships@company.com'],
                'media' => ['press@company.com'],
                'privacy' => ['privacy@company.com', 'legal@company.com'],
                'bug_report' => ['support@company.com', 'dev@company.com'],
                'feature_request' => ['product@company.com'],
                'general' => ['info@company.com']
            ];
            
            $categoryRecipients = $routingMap[$formData['inquiry_category']] ?? ['info@company.com'];
            $recipients = array_merge($recipients, $categoryRecipients);
            
            // Priority-based routing
            if (isset($formData['priority_level'])) {
                if ($formData['priority_level'] === 'urgent') {
                    $recipients[] = 'urgent@company.com';
                    $recipients[] = 'management@company.com';
                } elseif ($formData['priority_level'] === 'high') {
                    $recipients[] = 'priority@company.com';
                }
            }
        }
        
        return array_unique($recipients);
    }

    /**
     * Process template strings with form data
     */
    private function processTemplateString(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{(\w+)(?:\|(\w+))?\}\}/', function($matches) use ($data) {
            $key = $matches[1];
            $filter = $matches[2] ?? null;
            $value = $data[$key] ?? '';
            
            if ($filter) {
                switch ($filter) {
                    case 'upper':
                        $value = strtoupper($value);
                        break;
                    case 'lower':
                        $value = strtolower($value);
                        break;
                    case 'title':
                        $value = ucwords(str_replace('_', ' ', $value));
                        break;
                }
            }
            
            return $value;
        }, $template);
    }

    /**
     * Format template-specific notification message
     */
    private function formatTemplateNotificationMessage(array $formData, string $templateId): string
    {
        $message = "New form submission received:\n";
        $message .= "Template: " . ucwords(str_replace('-', ' ', $templateId)) . "\n\n";
        
        foreach ($formData as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $message .= ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
        }
        
        $message .= "\nSubmitted at: " . now()->format('Y-m-d H:i:s');
        $message .= "\nUser ID: " . (auth()->id() ?? 'Guest');
        
        return $message;
    }

    /**
     * Get success message for template
     */
    private function getSuccessMessage(string $templateId): string
    {
        $messages = [
            'individual-signup' => 'Welcome to our alumni network! Check your email for next steps.',
            'institution-demo-request' => 'Thank you for your interest! Our team will contact you within 24 hours to schedule your personalized demo.',
            'contact-general' => 'Thank you for contacting us! Your inquiry has been received and routed to the appropriate team.',
            'newsletter-signup' => 'Thank you for subscribing! Check your email to confirm your subscription.',
            'event-registration' => 'Registration successful! You will receive a confirmation email shortly.'
        ];
        
        return $messages[$templateId] ?? 'Form submitted successfully!';
    }
    
    /**
     * Send form notifications
     */
    private function sendFormNotifications(array $formData, array $formConfig): void
    {
        try {
            $notifications = $formConfig['notifications'] ?? [];
            
            if (!($notifications['enabled'] ?? false)) {
                return;
            }
            
            $message = $this->formatNotificationMessage($formData, $notifications);
            $subject = $notifications['subject'] ?? 'New Form Submission: ' . ($formConfig['title'] ?? 'Form');
            
            foreach ($notifications['recipients'] ?? [] as $recipient) {
                Mail::raw($message, function ($mail) use ($recipient, $subject) {
                    $mail->to($recipient)->subject($subject);
                });
            }
            
        } catch (\Exception $e) {
            Log::error('Form notification failed: ' . $e->getMessage());
            // Don't fail the form submission if notifications fail
        }
    }
    
    /**
     * Format notification message
     */
    private function formatNotificationMessage(array $formData, array $notifications): string
    {
        $message = "New form submission received:\n\n";
        
        foreach ($formData as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $message .= ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
        }
        
        $message .= "\nSubmitted at: " . now()->format('Y-m-d H:i:s');
        $message .= "\nUser ID: " . auth()->id();
        
        return $message;
    }
    
    /**
     * Track form submission for analytics
     */
    private function trackFormSubmission(array $formData, array $formConfig, Request $request): void
    {
        try {
            // Track form submission metrics
            $trackingData = [
                'form_title' => $formConfig['title'] ?? 'Unknown Form',
                'form_fields_count' => count($formConfig['fields'] ?? []),
                'form_layout' => $formConfig['layout'] ?? 'single-column',
                'user_id' => auth()->id(),
                'submission_time' => now()->toISOString(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ];
            
            // In a real implementation, you would send this to your analytics service
            Log::info('Form submission tracked', $trackingData);
            
        } catch (\Exception $e) {
            Log::error('Form tracking failed: ' . $e->getMessage());
            // Don't fail the form submission if tracking fails
        }
    }
}