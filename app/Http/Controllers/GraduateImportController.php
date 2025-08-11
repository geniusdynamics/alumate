<?php

namespace App\Http\Controllers;

use App\Imports\GraduatesImport;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\ImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class GraduateImportController extends Controller
{
    public function index()
    {
        $importHistories = ImportHistory::where('type', 'graduates')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Graduates/ImportHistory', [
            'importHistories' => $importHistories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Graduates/Import', [
            'courses' => Course::all(['id', 'name']),
            'templateUrl' => route('graduates.import.template'),
            'maxFileSize' => '10MB',
            'supportedFormats' => ['xlsx', 'xls'],
            'requiredFields' => ['name', 'email', 'graduation_year', 'course_name', 'employment_status'],
            'optionalFields' => [
                'phone', 'address', 'student_id', 'gpa', 'academic_standing',
                'current_job_title', 'current_company', 'current_salary',
                'employment_start_date', 'skills', 'certifications',
                'allow_employer_contact', 'job_search_active',
            ],
        ]);
    }

    public function template()
    {
        $headers = [
            'name',
            'email',
            'phone',
            'address',
            'graduation_year',
            'course_name',
            'student_id',
            'gpa',
            'academic_standing',
            'employment_status',
            'current_job_title',
            'current_company',
            'current_salary',
            'employment_start_date',
            'skills',
            'certifications',
            'allow_employer_contact',
            'job_search_active',
        ];

        $sampleData = [
            [
                'John Doe',
                'john.doe@example.com',
                '+1234567890',
                '123 Main St, City, State',
                '2023',
                'Computer Science',
                'CS2023001',
                '3.75',
                'excellent',
                'employed',
                'Software Developer',
                'Tech Corp',
                '75000',
                '2023-06-01',
                'PHP, JavaScript, Vue.js, Laravel',
                'AWS Certified Developer|Amazon|2023-01-15;Google Analytics Certified|Google|2022-12-10',
                'true',
                'false',
            ],
            [
                'Jane Smith',
                'jane.smith@example.com',
                '+1234567891',
                '456 Oak Ave, City, State',
                '2023',
                'Business Administration',
                'BA2023002',
                '3.50',
                'very_good',
                'unemployed',
                '',
                '',
                '',
                '',
                'Project Management, Excel, PowerPoint',
                'PMP Certification|PMI|2023-03-20',
                'true',
                'true',
            ],
        ];

        return Excel::download(new class($headers, $sampleData)
        {
            private $headers;

            private $sampleData;

            public function __construct($headers, $sampleData)
            {
                $this->headers = $headers;
                $this->sampleData = $sampleData;
            }

            public function collection()
            {
                return collect([$this->headers, ...$this->sampleData]);
            }
        }, 'graduates_import_template.xlsx');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            // Store the file temporarily
            $filePath = $request->file('file')->store('temp-imports');

            // Get headers to validate structure
            $headings = (new HeadingRowImport)->toArray($request->file('file'))[0][0] ?? [];

            // Validate required headers
            $requiredHeaders = ['name', 'email', 'graduation_year', 'course_name', 'employment_status'];
            $missingHeaders = array_diff($requiredHeaders, $headings);

            if (! empty($missingHeaders)) {
                return back()->withErrors([
                    'file' => 'Missing required columns: '.implode(', ', $missingHeaders),
                ]);
            }

            // Create import history record
            $importHistory = ImportHistory::create([
                'user_id' => auth()->id(),
                'type' => 'graduates',
                'filename' => $request->file('file')->getClientOriginalName(),
                'file_path' => $filePath,
                'status' => 'pending',
                'started_at' => now(),
            ]);

            // Preview first 10 rows
            $import = new GraduatesImport($importHistory->id);
            $rows = Excel::toArray($import, $request->file('file'))[0];

            // Remove header row and limit to first 10 rows for preview
            $previewRows = array_slice($rows, 1, 10);
            $totalRows = count($rows) - 1; // Exclude header

            // Update import history with total rows
            $importHistory->update(['total_rows' => $totalRows]);

            return Inertia::render('Graduates/ImportPreview', [
                'importHistory' => $importHistory,
                'previewRows' => $previewRows,
                'headers' => $headings,
                'totalRows' => $totalRows,
                'courses' => Course::all(['id', 'name']),
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'file' => 'Error processing file: '.$e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'import_history_id' => 'required|exists:import_histories,id',
            'resolve_conflicts' => 'nullable|array',
            'skip_duplicates' => 'nullable|boolean',
            'update_existing' => 'nullable|boolean',
        ]);

        $importHistory = ImportHistory::findOrFail($request->import_history_id);

        // Ensure the import belongs to the current user
        if ($importHistory->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $importHistory->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Process the import with conflict resolution options
            $import = new GraduatesImport($importHistory->id, [
                'skip_duplicates' => $request->boolean('skip_duplicates', true),
                'update_existing' => $request->boolean('update_existing', false),
                'resolve_conflicts' => $request->input('resolve_conflicts', []),
            ]);

            Excel::import($import, Storage::path($importHistory->file_path));

            // Get final statistics from the import
            $stats = $import->getImportStatistics();

            $importHistory->update([
                'status' => 'completed',
                'completed_at' => now(),
                'processed_rows' => $stats['processed_rows'],
                'created_count' => $stats['created_count'],
                'updated_count' => $stats['updated_count'],
                'skipped_count' => $stats['skipped_count'],
                'valid_rows' => $stats['valid_rows'],
                'invalid_rows' => $stats['invalid_rows'],
                'conflicts' => $stats['conflicts'],
            ]);

            // Clean up temporary file
            Storage::delete($importHistory->file_path);
            $importHistory->update(['file_path' => null]);

            $message = "Import completed! Created: {$stats['created_count']}, Updated: {$stats['updated_count']}, Skipped: {$stats['skipped_count']}";

            if (! empty($stats['conflicts'])) {
                $message .= ' | Conflicts: '.count($stats['conflicts']);
            }

            return redirect()->route('graduates.import.history')
                ->with('success', $message);

        } catch (\Exception $e) {
            $importHistory->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return back()->withErrors([
                'import' => 'Import failed: '.$e->getMessage(),
            ]);
        }
    }

    public function show(ImportHistory $importHistory)
    {
        // Ensure the import belongs to the current user
        if ($importHistory->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Graduates/ImportDetails', [
            'importHistory' => $importHistory,
        ]);
    }

    public function rollback(ImportHistory $importHistory)
    {
        // Ensure the import belongs to the current user
        if ($importHistory->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $importHistory->canRollback()) {
            return back()->withErrors([
                'rollback' => 'This import cannot be rolled back. Rollback is only available within 24 hours of completion.',
            ]);
        }

        try {
            // Get all graduates created in this import
            $graduateIds = collect($importHistory->valid_rows)->pluck('graduate_id')->filter();

            // Delete the graduates
            Graduate::whereIn('id', $graduateIds)->delete();

            // Update import history
            $importHistory->update([
                'status' => 'rolled_back',
                'error_message' => 'Import rolled back by user on '.now()->format('Y-m-d H:i:s'),
            ]);

            return redirect()->route('graduates.import.history')
                ->with('success', "Import rolled back successfully. Removed {$graduateIds->count()} graduates.");

        } catch (\Exception $e) {
            return back()->withErrors([
                'rollback' => 'Rollback failed: '.$e->getMessage(),
            ]);
        }
    }
}
