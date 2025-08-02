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
    protected $options;
    protected $validRows = [];
    protected $invalidRows = [];
    protected $duplicates = [];
    protected $conflicts = [];
    protected $statistics = [
        'processed_rows' => 0,
        'created_count' => 0,
        'updated_count' => 0,
        'skipped_count' => 0,
    ];

    public function __construct($importHistoryId = null, array $options = [])
    {
        if ($importHistoryId) {
            $this->importHistory = ImportHistory::find($importHistoryId);
        }
        
        $this->options = array_merge([
            'skip_duplicates' => true,
            'update_existing' => false,
            'resolve_conflicts' => [],
        ], $options);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $this->statistics['processed_rows']++;
            
            // Validate row data
            $validatedData = $this->validateRow($row->toArray(), $index);
            
            if (!$validatedData) {
                $this->statistics['skipped_count']++;
                continue;
            }

            // Check for duplicates
            $duplicate = $this->checkForDuplicates($validatedData);
            
            if ($duplicate) {
                $conflictResolution = $this->resolveConflict($validatedData, $duplicate, $index);
                
                if ($conflictResolution['action'] === 'skip') {
                    $this->statistics['skipped_count']++;
                    continue;
                } elseif ($conflictResolution['action'] === 'update') {
                    $this->updateExistingGraduate($duplicate, $validatedData, $index);
                    $this->statistics['updated_count']++;
                    continue;
                }
            }

            // Process skills and certifications
            $validatedData = $this->processComplexFields($validatedData);

            // Create new graduate
            try {
                $graduate = Graduate::create($validatedData);
                $graduate->updateProfileCompletion();
                $this->statistics['created_count']++;
                
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
                $this->statistics['skipped_count']++;
            }
        }
    }

    protected function resolveConflict(array $newData, Graduate $existing, int $index): array
    {
        $rowNumber = $index + 2;
        
        // Check if there's a specific resolution for this row
        if (isset($this->options['resolve_conflicts'][$rowNumber])) {
            $resolution = $this->options['resolve_conflicts'][$rowNumber];
            return ['action' => $resolution];
        }

        // Apply global options
        if ($this->options['update_existing']) {
            return ['action' => 'update'];
        }

        if ($this->options['skip_duplicates']) {
            $this->conflicts[] = [
                'row' => $rowNumber,
                'data' => $newData,
                'existing' => $existing->toArray(),
                'conflict_type' => $this->determineConflictType($newData, $existing),
                'similarity_score' => $this->calculateSimilarity($newData, $existing->toArray()),
            ];
            return ['action' => 'skip'];
        }

        return ['action' => 'create'];
    }

    protected function updateExistingGraduate(Graduate $existing, array $newData, int $index): void
    {
        // Only update fields that have new data
        $updateData = [];
        
        foreach ($newData as $key => $value) {
            if (!empty($value) && $existing->$key !== $value) {
                $updateData[$key] = $value;
            }
        }

        if (!empty($updateData)) {
            $existing->update($updateData);
            $existing->updateProfileCompletion();
        }

        $this->validRows[] = [
            'row' => $index + 2,
            'data' => $newData,
            'graduate_id' => $existing->id,
            'action' => 'updated'
        ];
    }

    protected function determineConflictType(array $newData, Graduate $existing): string
    {
        if ($newData['email'] === $existing->email) {
            return 'duplicate_email';
        }
        
        if (!empty($newData['student_id']) && $newData['student_id'] === $existing->student_id) {
            return 'duplicate_student_id';
        }
        
        return 'similar_record';
    }

    public function getImportStatistics(): array
    {
        return [
            'processed_rows' => $this->statistics['processed_rows'],
            'created_count' => $this->statistics['created_count'],
            'updated_count' => $this->statistics['updated_count'],
            'skipped_count' => $this->statistics['skipped_count'],
            'valid_rows' => $this->validRows,
            'invalid_rows' => $this->invalidRows,
            'conflicts' => $this->conflicts,
        ];
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

        // Add tenant_id from authenticated user's institution
        $validatedData['tenant_id'] = auth()->user()->institution_id;

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

        // Check for potential duplicates by name and graduation year
        $potentialDuplicates = Graduate::where('name', 'LIKE', '%' . $data['name'] . '%')
            ->where('graduation_year', $data['graduation_year'])
            ->get();

        if ($potentialDuplicates->count() > 0) {
            // Calculate similarity scores
            foreach ($potentialDuplicates as $potential) {
                $similarity = $this->calculateSimilarity($data, $potential->toArray());
                if ($similarity > 0.8) { // 80% similarity threshold
                    return $potential;
                }
            }
        }

        return null;
    }

    protected function calculateSimilarity(array $newData, array $existingData): float
    {
        $score = 0;
        $totalFields = 0;

        // Compare name (weighted heavily)
        if (isset($newData['name']) && isset($existingData['name'])) {
            $nameScore = $this->stringSimilarity($newData['name'], $existingData['name']);
            $score += $nameScore * 3; // Weight name heavily
            $totalFields += 3;
        }

        // Compare graduation year
        if (isset($newData['graduation_year']) && isset($existingData['graduation_year'])) {
            $score += ($newData['graduation_year'] == $existingData['graduation_year']) ? 2 : 0;
            $totalFields += 2;
        }

        // Compare course
        if (isset($newData['course_id']) && isset($existingData['course_id'])) {
            $score += ($newData['course_id'] == $existingData['course_id']) ? 1 : 0;
            $totalFields += 1;
        }

        // Compare phone if available
        if (!empty($newData['phone']) && !empty($existingData['phone'])) {
            $phoneScore = $this->stringSimilarity($newData['phone'], $existingData['phone']);
            $score += $phoneScore;
            $totalFields += 1;
        }

        return $totalFields > 0 ? $score / $totalFields : 0;
    }

    protected function stringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        if ($str1 === $str2) return 1.0;
        
        // Use Levenshtein distance for similarity
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) return 1.0;
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
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
