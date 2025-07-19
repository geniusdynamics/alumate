<?php

namespace App\Imports;

use App\Models\Graduate;
use App\Models\Course;
use App\Models\ImportHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class GraduatesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected $importHistory;
    protected $validRows = [];
    protected $invalidRows = [];
    protected $duplicates = [];
    protected $conflicts = [];

    public function __construct($importHistoryId = null)
    {
        if ($importHistoryId) {
            $this->importHistory = ImportHistory::find($importHistoryId);
        }
    }

    public function collection(Collection $rows)
    {
        $processedRows = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $index => $row) {
            $processedRows++;
            
            // Validate row data
            $validatedData = $this->validateRow($row->toArray(), $index);
            
            if (!$validatedData) {
                $skippedCount++;
                continue;
            }

            // Check for duplicates
            $duplicate = $this->checkForDuplicates($validatedData);
            
            if ($duplicate) {
                $this->conflicts[] = [
                    'row' => $index + 2, // +2 because of header row and 0-based index
                    'data' => $validatedData,
                    'existing' => $duplicate,
                    'conflict_type' => 'duplicate_email'
                ];
                $skippedCount++;
                continue;
            }

            // Process skills and certifications
            $validatedData = $this->processComplexFields($validatedData);

            // Create or update graduate
            try {
                $graduate = Graduate::create($validatedData);
                $graduate->updateProfileCompletion();
                $createdCount++;
                
                $this->validRows[] = [
                    'row' => $index + 2,
                    'data' => $validatedData,
                    'graduate_id' => $graduate->id,
                    'action' => 'created'
                ];
            } catch (\Exception $e) {
                $this->invalidRows[] = [
                    'row' => $index + 2,
                    'data' => $validatedData,
                    'error' => $e->getMessage()
                ];
                $skippedCount++;
            }
        }

        // Update import history
        if ($this->importHistory) {
            $this->importHistory->update([
                'processed_rows' => $processedRows,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'valid_rows' => $this->validRows,
                'invalid_rows' => $this->invalidRows,
                'conflicts' => $this->conflicts,
                'status' => 'completed'
            ]);
        }
    }

    protected function validateRow(array $row, int $index): ?array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'graduation_year' => 'required|digits:4|integer|min:1900|max:' . (date('Y') + 1),
            'course_name' => 'required|string|exists:courses,name',
            'student_id' => 'nullable|string|max:255',
            'gpa' => 'nullable|numeric|min:0|max:4',
            'academic_standing' => 'nullable|in:excellent,very_good,good,satisfactory,pass',
            'employment_status' => 'required|in:unemployed,employed,self_employed,further_studies,other',
            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'current_salary' => 'nullable|numeric|min:0',
            'employment_start_date' => 'nullable|date',
            'skills' => 'nullable|string',
            'certifications' => 'nullable|string',
            'allow_employer_contact' => 'nullable|boolean',
            'job_search_active' => 'nullable|boolean',
        ];

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            $this->invalidRows[] = [
                'row' => $index + 2,
                'data' => $row,
                'errors' => $validator->errors()->toArray()
            ];
            return null;
        }

        // Convert course name to course_id
        $course = Course::where('name', $row['course_name'])->first();
        $validatedData = $validator->validated();
        $validatedData['course_id'] = $course->id;
        unset($validatedData['course_name']);

        return $validatedData;
    }

    protected function checkForDuplicates(array $data): ?Graduate
    {
        // Check for duplicate email
        $existingByEmail = Graduate::where('email', $data['email'])->first();
        if ($existingByEmail) {
            return $existingByEmail;
        }

        // Check for duplicate student_id if provided
        if (!empty($data['student_id'])) {
            $existingByStudentId = Graduate::where('student_id', $data['student_id'])->first();
            if ($existingByStudentId) {
                return $existingByStudentId;
            }
        }

        return null;
    }

    protected function processComplexFields(array $data): array
    {
        // Process skills
        if (!empty($data['skills'])) {
            $skills = array_map('trim', explode(',', $data['skills']));
            $data['skills'] = array_filter($skills);
        } else {
            $data['skills'] = [];
        }

        // Process certifications
        if (!empty($data['certifications'])) {
            $certifications = array_map('trim', explode(';', $data['certifications']));
            $processedCertifications = [];
            
            foreach ($certifications as $cert) {
                if (strpos($cert, '|') !== false) {
                    $parts = explode('|', $cert);
                    $processedCertifications[] = [
                        'name' => trim($parts[0]),
                        'issuer' => isset($parts[1]) ? trim($parts[1]) : '',
                        'date_obtained' => isset($parts[2]) ? trim($parts[2]) : ''
                    ];
                } else {
                    $processedCertifications[] = [
                        'name' => trim($cert),
                        'issuer' => '',
                        'date_obtained' => ''
                    ];
                }
            }
            $data['certifications'] = $processedCertifications;
        } else {
            $data['certifications'] = [];
        }

        // Set default privacy settings
        $data['privacy_settings'] = [
            'profile_visible' => true,
            'contact_visible' => true,
            'employment_visible' => true,
        ];

        // Convert boolean strings
        $data['allow_employer_contact'] = filter_var($data['allow_employer_contact'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['job_search_active'] = filter_var($data['job_search_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        return $data;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'graduation_year' => 'required|digits:4|integer|min:1900|max:' . (date('Y') + 1),
            'course_name' => 'required|string',
            'employment_status' => 'required|in:unemployed,employed,self_employed,further_studies,other',
        ];
    }

    public function getValidRows(): array
    {
        return $this->validRows;
    }

    public function getInvalidRows(): array
    {
        return $this->invalidRows;
    }

    public function getConflicts(): array
    {
        return $this->conflicts;
    }
}
