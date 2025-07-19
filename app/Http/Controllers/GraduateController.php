<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Graduate;
use App\Traits\Exportable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GraduateController extends Controller
{
    use Exportable;
    public function index(Request $request)
    {
        $query = Graduate::with('course');

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('student_id', 'like', '%' . $request->search . '%')
                  ->orWhere('current_job_title', 'like', '%' . $request->search . '%')
                  ->orWhere('current_company', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('employment_status')) {
            if (is_array($request->employment_status)) {
                $query->whereIn('employment_status', $request->employment_status);
            } else {
                $query->where('employment_status', $request->employment_status);
            }
        }

        if ($request->filled('graduation_year')) {
            if (is_array($request->graduation_year)) {
                $query->whereIn('graduation_year', $request->graduation_year);
            } else {
                $query->where('graduation_year', $request->graduation_year);
            }
        }

        if ($request->filled('graduation_year_range')) {
            $range = explode('-', $request->graduation_year_range);
            if (count($range) === 2) {
                $query->whereBetween('graduation_year', [$range[0], $range[1]]);
            }
        }

        if ($request->filled('course_id')) {
            if (is_array($request->course_id)) {
                $query->whereIn('course_id', $request->course_id);
            } else {
                $query->where('course_id', $request->course_id);
            }
        }

        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
            foreach ($skills as $skill) {
                $query->whereJsonContains('skills', trim($skill));
            }
        }

        if ($request->filled('gpa_min')) {
            $query->where('gpa', '>=', $request->gpa_min);
        }

        if ($request->filled('gpa_max')) {
            $query->where('gpa', '<=', $request->gpa_max);
        }

        if ($request->filled('academic_standing')) {
            if (is_array($request->academic_standing)) {
                $query->whereIn('academic_standing', $request->academic_standing);
            } else {
                $query->where('academic_standing', $request->academic_standing);
            }
        }

        if ($request->filled('job_search_active')) {
            $query->where('job_search_active', $request->job_search_active === 'true');
        }

        if ($request->filled('profile_completion_min')) {
            $query->where('profile_completion_percentage', '>=', $request->profile_completion_min);
        }

        if ($request->filled('has_certifications')) {
            if ($request->has_certifications === 'true') {
                $query->whereNotNull('certifications')->where('certifications', '!=', '[]');
            } else {
                $query->where(function($q) {
                    $q->whereNull('certifications')->orWhere('certifications', '[]');
                });
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSorts = ['name', 'graduation_year', 'employment_status', 'profile_completion_percentage', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $graduates = $query->paginate(15)->withQueryString();

        // Get filter options
        $graduationYears = Graduate::distinct()->pluck('graduation_year')->sort()->values();
        $employmentStatuses = ['unemployed', 'employed', 'self_employed', 'further_studies', 'other'];
        $academicStandings = ['excellent', 'very_good', 'good', 'satisfactory', 'pass'];

        return Inertia::render('Graduates/Index', [
            'graduates' => $graduates,
            'courses' => Course::all(),
            'graduationYears' => $graduationYears,
            'employmentStatuses' => $employmentStatuses,
            'academicStandings' => $academicStandings,
            'filters' => $request->only([
                'search', 'employment_status', 'graduation_year', 'graduation_year_range', 
                'course_id', 'skills', 'gpa_min', 'gpa_max', 'academic_standing', 
                'job_search_active', 'profile_completion_min', 'has_certifications',
                'sort_by', 'sort_order'
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Graduates/Create', [
            'courses' => Course::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:graduates',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'graduation_year' => 'required|digits:4|integer|min:1900|max:'.(date('Y')+1),
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'nullable|string|max:255|unique:graduates',
            'gpa' => 'nullable|numeric|min:0|max:4',
            'academic_standing' => 'nullable|in:excellent,very_good,good,satisfactory,pass',
            'employment_status' => 'required|in:unemployed,employed,self_employed,further_studies,other',
            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'current_salary' => 'nullable|numeric|min:0',
            'employment_start_date' => 'nullable|date',
            'skills' => 'nullable|array',
            'certifications' => 'nullable|array',
            'privacy_settings' => 'nullable|array',
            'allow_employer_contact' => 'boolean',
            'job_search_active' => 'boolean',
        ]);

        $graduate = Graduate::create($data);
        $graduate->updateProfileCompletion();

        return redirect()->route('graduates.index');
    }

    public function edit(Graduate $graduate)
    {
        return Inertia::render('Graduates/Edit', [
            'graduate' => $graduate,
            'courses' => Course::all(),
        ]);
    }

    public function update(Request $request, Graduate $graduate)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:graduates,email,'.$graduate->id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'graduation_year' => 'required|digits:4|integer|min:1900|max:'.(date('Y')+1),
            'course_id' => 'required|exists:courses,id',
            'student_id' => 'nullable|string|max:255|unique:graduates,student_id,'.$graduate->id,
            'gpa' => 'nullable|numeric|min:0|max:4',
            'academic_standing' => 'nullable|in:excellent,very_good,good,satisfactory,pass',
            'employment_status' => 'required|in:unemployed,employed,self_employed,further_studies,other',
            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'current_salary' => 'nullable|numeric|min:0',
            'employment_start_date' => 'nullable|date',
            'skills' => 'nullable|array',
            'certifications' => 'nullable|array',
            'privacy_settings' => 'nullable|array',
            'allow_employer_contact' => 'boolean',
            'job_search_active' => 'boolean',
        ]);

        // Track changes for audit log
        $changes = [];
        foreach ($data as $field => $value) {
            if ($graduate->$field != $value) {
                $changes[$field] = [
                    'old' => $graduate->$field,
                    'new' => $value
                ];
            }
        }

        $graduate->update($data);
        $graduate->updateProfileCompletion();

        // Log significant changes
        if (!empty($changes)) {
            $changedFields = array_keys($changes);
            $description = 'Profile updated: ' . implode(', ', $changedFields);
            $graduate->logAuditTrail('profile_updated', $description, null, null, null, $changes);
        }

        return redirect()->route('graduates.index');
    }

    public function show(Graduate $graduate)
    {
        return Inertia::render('Graduates/Show', [
            'graduate' => $graduate->load(['course', 'applications.job.employer']),
            'profileCompletion' => $graduate->profile_completion_percentage,
            'employmentHistory' => $this->getEmploymentHistory($graduate),
            'auditTrail' => $graduate->auditLogs()->with('user:id,name')->take(20)->get()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'created_at' => $log->created_at,
                    'user_name' => $log->user?->name ?? 'System',
                ];
            }),
        ]);
    }

    public function destroy(Graduate $graduate)
    {
        $graduate->delete();

        return redirect()->route('graduates.index');
    }

    public function updateEmployment(Request $request, Graduate $graduate)
    {
        $data = $request->validate([
            'employment_status' => 'required|in:unemployed,employed,self_employed,further_studies,other',
            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'current_salary' => 'nullable|numeric|min:0',
            'employment_start_date' => 'nullable|date',
        ]);

        $graduate->updateEmploymentStatus($data['employment_status'], $data);

        return back()->with('success', 'Employment status updated successfully.');
    }

    public function updatePrivacySettings(Request $request, Graduate $graduate)
    {
        $data = $request->validate([
            'privacy_settings' => 'required|array',
            'allow_employer_contact' => 'boolean',
            'job_search_active' => 'boolean',
        ]);

        // Track changes for audit log
        $changes = [];
        foreach ($data as $field => $value) {
            if ($graduate->$field != $value) {
                $changes[$field] = [
                    'old' => $graduate->$field,
                    'new' => $value
                ];
            }
        }

        $graduate->update($data);

        // Log privacy changes
        if (!empty($changes)) {
            $graduate->logPrivacyUpdate($changes);
        }

        return back()->with('success', 'Privacy settings updated successfully.');
    }

    private function getEmploymentHistory(Graduate $graduate)
    {
        // This would typically come from a separate employment history table
        // For now, we'll return current employment if exists
        if ($graduate->is_employed) {
            return [[
                'job_title' => $graduate->current_job_title,
                'company' => $graduate->current_company,
                'start_date' => $graduate->employment_start_date,
                'salary' => $graduate->current_salary,
                'status' => 'current'
            ]];
        }
        return [];
    }

    public function export(Request $request)
    {
        $query = Graduate::with(['course']);

        // Apply same filters as index method
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('student_id', 'like', '%' . $request->search . '%')
                  ->orWhere('current_job_title', 'like', '%' . $request->search . '%')
                  ->orWhere('current_company', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('employment_status')) {
            if (is_array($request->employment_status)) {
                $query->whereIn('employment_status', $request->employment_status);
            } else {
                $query->where('employment_status', $request->employment_status);
            }
        }

        if ($request->filled('graduation_year')) {
            if (is_array($request->graduation_year)) {
                $query->whereIn('graduation_year', $request->graduation_year);
            } else {
                $query->where('graduation_year', $request->graduation_year);
            }
        }

        if ($request->filled('course_id')) {
            if (is_array($request->course_id)) {
                $query->whereIn('course_id', $request->course_id);
            } else {
                $query->where('course_id', $request->course_id);
            }
        }

        if ($request->filled('skills')) {
            $skills = is_array($request->skills) ? $request->skills : explode(',', $request->skills);
            foreach ($skills as $skill) {
                $query->whereJsonContains('skills', trim($skill));
            }
        }

        // Apply additional filters...
        if ($request->filled('gpa_min')) {
            $query->where('gpa', '>=', $request->gpa_min);
        }

        if ($request->filled('gpa_max')) {
            $query->where('gpa', '<=', $request->gpa_max);
        }

        if ($request->filled('academic_standing')) {
            if (is_array($request->academic_standing)) {
                $query->whereIn('academic_standing', $request->academic_standing);
            } else {
                $query->where('academic_standing', $request->academic_standing);
            }
        }

        if ($request->filled('job_search_active')) {
            $query->where('job_search_active', $request->job_search_active === 'true');
        }

        if ($request->filled('profile_completion_min')) {
            $query->where('profile_completion_percentage', '>=', $request->profile_completion_min);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSorts = ['name', 'graduation_year', 'employment_status', 'profile_completion_percentage', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Define export columns
        $columns = [
            'id',
            'name',
            'email',
            'phone',
            'address',
            'graduation_year',
            'course.name as course_name',
            'student_id',
            'gpa',
            'academic_standing',
            'employment_status',
            'current_job_title',
            'current_company',
            'current_salary',
            'employment_start_date',
            'profile_completion_percentage',
            'allow_employer_contact',
            'job_search_active',
            'created_at',
            'updated_at'
        ];

        // Custom field selection
        if ($request->filled('export_fields')) {
            $requestedFields = $request->export_fields;
            $columns = array_intersect($columns, $requestedFields);
        }

        $format = $request->get('format', 'csv');
        $filename = 'graduates_export_' . date('Y-m-d_H-i-s');

        if ($format === 'json') {
            return $this->exportToJson($query, $filename . '.json');
        }

        return $this->exportToCsv($query, $columns, $filename . '.csv');
    }
}
