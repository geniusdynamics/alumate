<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\User;
use App\Traits\Exportable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class EmployerController extends Controller
{
    use Exportable;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Employer::class);

        $query = Employer::with(['user', 'verifier']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', '%'.$request->search.'%')
                    ->orWhere('company_registration_number', 'like', '%'.$request->search.'%')
                    ->orWhere('industry', 'like', '%'.$request->search.'%')
                    ->orWhere('contact_person_name', 'like', '%'.$request->search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('email', 'like', '%'.$request->search.'%');
                    });
            });
        }

        if ($request->filled('verification_status')) {
            if (is_array($request->verification_status)) {
                $query->whereIn('verification_status', $request->verification_status);
            } else {
                $query->where('verification_status', $request->verification_status);
            }
        }

        if ($request->filled('industry')) {
            if (is_array($request->industry)) {
                $query->whereIn('industry', $request->industry);
            } else {
                $query->where('industry', $request->industry);
            }
        }

        if ($request->filled('company_size')) {
            if (is_array($request->company_size)) {
                $query->whereIn('company_size', $request->company_size);
            } else {
                $query->where('company_size', $request->company_size);
            }
        }

        if ($request->filled('subscription_plan')) {
            if (is_array($request->subscription_plan)) {
                $query->whereIn('subscription_plan', $request->subscription_plan);
            } else {
                $query->where('subscription_plan', $request->subscription_plan);
            }
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'true');
        }

        if ($request->filled('can_post_jobs')) {
            $query->where('can_post_jobs', $request->can_post_jobs === 'true');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['company_name', 'verification_status', 'created_at', 'total_jobs_posted', 'employer_rating'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $employers = $query->paginate(15)->withQueryString();

        // Get filter options
        $verificationStatuses = ['pending', 'under_review', 'verified', 'rejected', 'suspended', 'requires_resubmission'];
        $industries = Employer::distinct()->pluck('industry')->filter()->sort()->values();
        $companySizes = ['startup', 'small', 'medium', 'large', 'enterprise'];
        $subscriptionPlans = ['free', 'basic', 'premium', 'enterprise'];

        return Inertia::render('Employers/Index', [
            'employers' => $employers,
            'verificationStatuses' => $verificationStatuses,
            'industries' => $industries,
            'companySizes' => $companySizes,
            'subscriptionPlans' => $subscriptionPlans,
            'filters' => $request->only([
                'search', 'verification_status', 'industry', 'company_size',
                'subscription_plan', 'is_active', 'can_post_jobs',
                'sort_by', 'sort_order',
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Auth/EmployerRegister');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'company_size' => 'nullable|in:startup,small,medium,large,enterprise',
            'company_description' => 'nullable|string|max:1000',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_title' => 'nullable|string|max:255',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_phone' => 'nullable|string|max:255',
            'established_year' => 'nullable|integer|min:1800|max:'.date('Y'),
            'employee_count' => 'nullable|integer|min:1',
            'terms_accepted' => 'required|accepted',
            'privacy_policy_accepted' => 'required|accepted',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('Employer');

        $employer = Employer::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'company_phone' => $request->company_phone,
            'company_registration_number' => $request->company_registration_number,
            'company_website' => $request->company_website,
            'industry' => $request->industry,
            'company_size' => $request->company_size,
            'company_description' => $request->company_description,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_title' => $request->contact_person_title,
            'contact_person_email' => $request->contact_person_email ?: $request->email,
            'contact_person_phone' => $request->contact_person_phone,
            'established_year' => $request->established_year,
            'employee_count' => $request->employee_count,
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'privacy_policy_accepted' => true,
            'privacy_policy_accepted_at' => now(),
            'verification_status' => 'pending',
        ]);

        // Check if profile is complete
        $employer->markProfileCompleted();

        return redirect()->route('login')
            ->with('success', 'Registration successful! Your account is pending verification.');
    }

    public function show(Employer $employer)
    {
        $this->authorize('view', $employer);

        $employer->load(['user', 'verifier', 'jobs.applications']);

        // Get employer statistics
        $statistics = [
            'profile_completion' => $employer->getProfileCompletionPercentage(),
            'job_stats' => $employer->updateJobStats(),
            'recent_applications' => $employer->getRecentApplications(10),
            'active_jobs' => $employer->getActiveJobs(10),
            'remaining_job_posts' => $employer->getRemainingJobPosts(),
        ];

        return Inertia::render('Employers/Show', [
            'employer' => $employer,
            'statistics' => $statistics,
        ]);
    }

    public function edit(Employer $employer)
    {
        $this->authorize('update', $employer);

        return Inertia::render('Employers/Edit', [
            'employer' => $employer,
        ]);
    }

    public function update(Request $request, Employer $employer)
    {
        $this->authorize('update', $employer);

        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:255',
            'company_tax_number' => 'nullable|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:255',
            'company_size' => 'nullable|in:startup,small,medium,large,enterprise',
            'company_description' => 'nullable|string|max:1000',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_title' => 'nullable|string|max:255',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_phone' => 'nullable|string|max:255',
            'established_year' => 'nullable|integer|min:1800|max:'.date('Y'),
            'employee_count' => 'nullable|integer|min:1',
            'business_locations' => 'nullable|array',
            'services_products' => 'nullable|array',
            'employer_benefits' => 'nullable|array',
            'notification_preferences' => 'nullable|array',
        ]);

        $employer->update($data);
        $employer->markProfileCompleted();

        return redirect()->route('employers.show', $employer)
            ->with('success', 'Employer profile updated successfully!');
    }

    public function destroy(Employer $employer)
    {
        $this->authorize('delete', $employer);

        // Check if employer has active jobs
        if ($employer->active_jobs_count > 0) {
            return back()->withErrors([
                'employer' => 'Cannot delete employer with active job postings. Please close all jobs first.',
            ]);
        }

        $employer->delete();

        return redirect()->route('employers.index')
            ->with('success', 'Employer deleted successfully!');
    }

    public function submitVerification(Request $request, Employer $employer)
    {
        $this->authorize('update', $employer);

        $request->validate([
            'verification_documents' => 'required|array|min:1',
            'verification_documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $documents = [];
        foreach ($request->file('verification_documents') as $file) {
            $path = $file->store('verification-documents', 'public');
            $documents[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        $employer->update([
            'verification_documents' => $documents,
            'verification_status' => 'under_review',
            'verification_submitted_at' => now(),
        ]);

        return back()->with('success', 'Verification documents submitted successfully!');
    }

    public function verify(Request $request, Employer $employer)
    {
        $this->authorize('verify', $employer);

        $request->validate([
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        $employer->verify(auth()->id(), $request->verification_notes);

        return back()->with('success', 'Employer verified successfully!');
    }

    public function reject(Request $request, Employer $employer)
    {
        $this->authorize('verify', $employer);

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $employer->reject($request->rejection_reason, auth()->id());

        return back()->with('success', 'Employer verification rejected.');
    }

    public function suspend(Request $request, Employer $employer)
    {
        $this->authorize('verify', $employer);

        $request->validate([
            'suspension_reason' => 'required|string|max:1000',
        ]);

        $employer->suspend($request->suspension_reason);

        return back()->with('success', 'Employer suspended successfully.');
    }

    public function reactivate(Employer $employer)
    {
        $this->authorize('verify', $employer);

        $employer->reactivate();

        return back()->with('success', 'Employer reactivated successfully.');
    }

    public function updateSubscription(Request $request, Employer $employer)
    {
        $this->authorize('update', $employer);

        $request->validate([
            'subscription_plan' => 'required|in:free,basic,premium,enterprise',
            'job_posting_limit' => 'required|integer|min:1|max:1000',
        ]);

        $employer->update([
            'subscription_plan' => $request->subscription_plan,
            'job_posting_limit' => $request->job_posting_limit,
            'subscription_expires_at' => now()->addYear(),
        ]);

        return back()->with('success', 'Subscription updated successfully!');
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Employer::class);

        $query = Employer::with(['user']);

        // Apply same filters as index method
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('company_name', 'like', '%'.$request->search.'%')
                    ->orWhere('industry', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('verification_status')) {
            if (is_array($request->verification_status)) {
                $query->whereIn('verification_status', $request->verification_status);
            } else {
                $query->where('verification_status', $request->verification_status);
            }
        }

        // Define export columns
        $columns = [
            'id', 'company_name', 'industry', 'company_size', 'verification_status',
            'total_jobs_posted', 'active_jobs_count', 'employer_rating',
            'subscription_plan', 'created_at', 'verification_completed_at',
        ];

        $format = $request->get('format', 'csv');
        $filename = 'employers_export_'.date('Y-m-d_H-i-s');

        if ($format === 'json') {
            return $this->exportToJson($query, $filename.'.json');
        }

        return $this->exportToCsv($query, $columns, $filename.'.csv');
    }
}
