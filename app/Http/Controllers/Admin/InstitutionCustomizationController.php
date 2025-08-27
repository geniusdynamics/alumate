<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InstitutionCustomizationController extends Controller
{
    public function index()
    {
        $institution = auth()->user()->institution;
        
        if (!$institution) {
            abort(403, 'No institution associated with this account');
        }

        return Inertia::render('Admin/InstitutionCustomization/Index', [
            'institution' => $institution,
            'availableFeatures' => $this->getAvailableFeatures(),
            'integrationOptions' => $this->getIntegrationOptions(),
        ]);
    }

    public function updateBranding(Request $request, Institution $institution)
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'custom_css' => 'nullable|string|max:10000',
            'font_family' => 'nullable|string|in:inter,roboto,open-sans,lato,montserrat,poppins',
            'theme_style' => 'nullable|string|in:modern,classic,minimal,corporate',
        ]);

        $updateData = [];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($institution->logo_path) {
                Storage::disk('public')->delete($institution->logo_path);
            }
            $logoPath = $request->file('logo')->store('institutions/logos', 'public');
            $updateData['logo_path'] = $logoPath;
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            if ($institution->banner_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $institution->banner_url));
            }
            $bannerPath = $request->file('banner')->store('institutions/banners', 'public');
            $updateData['banner_url'] = '/storage/' . $bannerPath;
        }

        // Update colors
        if (isset($validated['primary_color'])) {
            $updateData['primary_color'] = $validated['primary_color'];
        }
        if (isset($validated['secondary_color'])) {
            $updateData['secondary_color'] = $validated['secondary_color'];
        }

        // Update settings with branding preferences
        $settings = $institution->settings ?? [];
        $settings['branding'] = array_merge($settings['branding'] ?? [], [
            'custom_css' => $validated['custom_css'] ?? null,
            'font_family' => $validated['font_family'] ?? 'inter',
            'theme_style' => $validated['theme_style'] ?? 'modern',
        ]);
        $updateData['settings'] = $settings;

        $institution->update($updateData);

        return back()->with('success', 'Branding updated successfully');
    }

    public function updateFeatures(Request $request, Institution $institution)
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'features' => 'required|array',
            'features.*' => 'boolean',
        ]);

        $institution->update([
            'feature_flags' => $validated['features']
        ]);

        return back()->with('success', 'Feature settings updated successfully');
    }

    public function updateCustomFields(Request $request, Institution $institution)
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'custom_fields' => 'required|array',
            'custom_fields.*.name' => 'required|string|max:255',
            'custom_fields.*.type' => 'required|string|in:text,textarea,select,checkbox,date,number',
            'custom_fields.*.required' => 'boolean',
            'custom_fields.*.options' => 'nullable|array',
            'custom_fields.*.section' => 'required|string|in:profile,registration,career',
        ]);

        $settings = $institution->settings ?? [];
        $settings['custom_fields'] = $validated['custom_fields'];
        
        $institution->update(['settings' => $settings]);

        return back()->with('success', 'Custom fields updated successfully');
    }

    public function updateWorkflows(Request $request, Institution $institution)
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'workflows' => 'required|array',
            'workflows.*.name' => 'required|string|max:255',
            'workflows.*.trigger' => 'required|string',
            'workflows.*.actions' => 'required|array',
            'workflows.*.conditions' => 'nullable|array',
            'workflows.*.enabled' => 'boolean',
        ]);

        $settings = $institution->settings ?? [];
        $settings['workflows'] = $validated['workflows'];
        
        $institution->update(['settings' => $settings]);

        return back()->with('success', 'Workflows updated successfully');
    }

    public function updateReportingConfig(Request $request, Institution $institution)
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'reporting_config' => 'required|array',
            'reporting_config.default_metrics' => 'array',
            'reporting_config.custom_dashboards' => 'array',
            'reporting_config.scheduled_reports' => 'array',
            'reporting_config.data_retention_days' => 'integer|min:30|max:2555',
        ]);

        $settings = $institution->settings ?? [];
        $settings['reporting'] = $validated['reporting_config'];
        
        $institution->update(['settings' => $settings]);

        return back()->with('success', 'Reporting configuration updated successfully');
    }

    public function updateIntegrations(Request $request, Institution $institution)
    {
        $this->authorize('update', $institution);

        $validated = $request->validate([
            'integrations' => 'required|array',
            'integrations.*.name' => 'required|string',
            'integrations.*.enabled' => 'boolean',
            'integrations.*.config' => 'array',
        ]);

        $institution->update([
            'integration_settings' => $validated['integrations']
        ]);

        return back()->with('success', 'Integration settings updated successfully');
    }

    public function generateWhiteLabelConfig(Institution $institution)
    {
        $this->authorize('view', $institution);

        $whiteLabelService = app(\App\Services\WhiteLabelConfigService::class);
        $config = $whiteLabelService->generateConfig($institution);

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    private function getAvailableFeatures()
    {
        return [
            'social_timeline' => [
                'name' => 'Social Timeline',
                'description' => 'Enable social posts and timeline features',
                'category' => 'social'
            ],
            'job_matching' => [
                'name' => 'Job Matching',
                'description' => 'AI-powered job matching and recommendations',
                'category' => 'career'
            ],
            'mentorship' => [
                'name' => 'Mentorship Program',
                'description' => 'Alumni mentorship matching and management',
                'category' => 'career'
            ],
            'events' => [
                'name' => 'Events Management',
                'description' => 'Event creation, RSVP, and management',
                'category' => 'engagement'
            ],
            'fundraising' => [
                'name' => 'Fundraising Tools',
                'description' => 'Donation campaigns and giving tracking',
                'category' => 'fundraising'
            ],
            'analytics' => [
                'name' => 'Advanced Analytics',
                'description' => 'Detailed reporting and insights',
                'category' => 'analytics'
            ],
            'messaging' => [
                'name' => 'Direct Messaging',
                'description' => 'Private messaging between alumni',
                'category' => 'communication'
            ],
            'video_calling' => [
                'name' => 'Video Calling',
                'description' => 'Integrated video conferencing',
                'category' => 'communication'
            ],
            'success_stories' => [
                'name' => 'Success Stories',
                'description' => 'Alumni achievement showcases',
                'category' => 'engagement'
            ],
            'custom_branding' => [
                'name' => 'Custom Branding',
                'description' => 'Institution-specific branding and themes',
                'category' => 'customization'
            ]
        ];
    }

    private function getIntegrationOptions()
    {
        return [
            'email_marketing' => [
                'name' => 'Email Marketing',
                'providers' => ['mailchimp', 'constant_contact', 'sendgrid'],
                'description' => 'Integrate with email marketing platforms'
            ],
            'crm' => [
                'name' => 'CRM Integration',
                'providers' => ['salesforce', 'hubspot', 'pipedrive'],
                'description' => 'Connect with customer relationship management systems'
            ],
            'calendar' => [
                'name' => 'Calendar Integration',
                'providers' => ['google_calendar', 'outlook', 'apple_calendar'],
                'description' => 'Sync events with calendar systems'
            ],
            'sso' => [
                'name' => 'Single Sign-On',
                'providers' => ['saml', 'oauth2', 'ldap'],
                'description' => 'Enable single sign-on authentication'
            ],
            'payment' => [
                'name' => 'Payment Processing',
                'providers' => ['stripe', 'paypal', 'square'],
                'description' => 'Process donations and event payments'
            ]
        ];
    }