<?php

namespace App\Exports;

use App\Models\Graduate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GraduatesExport implements FromCollection, ShouldAutoSize, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $filters;

    protected $selectedFields;

    protected $includeHeaders;

    public function __construct(array $filters = [], array $selectedFields = [], bool $includeHeaders = true)
    {
        $this->filters = $filters;
        $this->selectedFields = empty($selectedFields) ? $this->getDefaultFields() : $selectedFields;
        $this->includeHeaders = $includeHeaders;
    }

    public function collection()
    {
        $query = Graduate::with(['course', 'user']);

        // Apply filters
        if (! empty($this->filters['course_id'])) {
            $query->where('course_id', $this->filters['course_id']);
        }

        if (! empty($this->filters['graduation_year'])) {
            if (is_array($this->filters['graduation_year'])) {
                $query->whereBetween('graduation_year', $this->filters['graduation_year']);
            } else {
                $query->where('graduation_year', $this->filters['graduation_year']);
            }
        }

        if (! empty($this->filters['employment_status'])) {
            $query->where('employment_status', $this->filters['employment_status']);
        }

        if (! empty($this->filters['job_search_active'])) {
            $query->where('job_search_active', $this->filters['job_search_active'] === 'true');
        }

        if (! empty($this->filters['allow_employer_contact'])) {
            $query->where('allow_employer_contact', $this->filters['allow_employer_contact'] === 'true');
        }

        if (! empty($this->filters['skills'])) {
            $skills = is_array($this->filters['skills']) ? $this->filters['skills'] : [$this->filters['skills']];
            foreach ($skills as $skill) {
                $query->whereJsonContains('skills', $skill);
            }
        }

        if (! empty($this->filters['gpa_min'])) {
            $query->where('gpa', '>=', $this->filters['gpa_min']);
        }

        if (! empty($this->filters['gpa_max'])) {
            $query->where('gpa', '<=', $this->filters['gpa_max']);
        }

        if (! empty($this->filters['created_after'])) {
            $query->where('created_at', '>=', $this->filters['created_after']);
        }

        if (! empty($this->filters['created_before'])) {
            $query->where('created_at', '<=', $this->filters['created_before']);
        }

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->get();
    }

    public function headings(): array
    {
        if (! $this->includeHeaders) {
            return [];
        }

        $headings = [];
        foreach ($this->selectedFields as $field) {
            $headings[] = $this->getFieldDisplayName($field);
        }

        return $headings;
    }

    public function map($graduate): array
    {
        $row = [];

        foreach ($this->selectedFields as $field) {
            $row[] = $this->getFieldValue($graduate, $field);
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        if (! $this->includeHeaders) {
            return [];
        }

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => Color::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '366092'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->selectedFields as $index => $field) {
            $widths[chr(65 + $index)] = $this->getFieldWidth($field);
        }

        return $widths;
    }

    public function title(): string
    {
        return 'Graduates Export';
    }

    protected function getDefaultFields(): array
    {
        return [
            'name',
            'email',
            'phone',
            'student_id',
            'course_name',
            'graduation_year',
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
            'profile_completion_percentage',
            'created_at',
            'updated_at',
        ];
    }

    protected function getFieldDisplayName(string $field): string
    {
        $displayNames = [
            'name' => 'Full Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'address' => 'Address',
            'student_id' => 'Student ID',
            'course_name' => 'Course',
            'graduation_year' => 'Graduation Year',
            'gpa' => 'GPA',
            'academic_standing' => 'Academic Standing',
            'employment_status' => 'Employment Status',
            'current_job_title' => 'Current Job Title',
            'current_company' => 'Current Company',
            'current_salary' => 'Current Salary',
            'employment_start_date' => 'Employment Start Date',
            'skills' => 'Skills',
            'certifications' => 'Certifications',
            'allow_employer_contact' => 'Allow Employer Contact',
            'job_search_active' => 'Job Search Active',
            'profile_completion_percentage' => 'Profile Completion %',
            'last_profile_update' => 'Last Profile Update',
            'last_employment_update' => 'Last Employment Update',
            'created_at' => 'Created Date',
            'updated_at' => 'Last Updated',
        ];

        return $displayNames[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    protected function getFieldValue($graduate, string $field)
    {
        switch ($field) {
            case 'course_name':
                return $graduate->course?->name ?? 'N/A';

            case 'employment_status':
                return $graduate->employment_status['status'] ?? 'N/A';

            case 'current_job_title':
                return $graduate->employment_status['job_title'] ?? '';

            case 'current_company':
                return $graduate->employment_status['company'] ?? '';

            case 'current_salary':
                return $graduate->employment_status['salary'] ?? '';

            case 'employment_start_date':
                return $graduate->employment_status['start_date'] ?? '';

            case 'skills':
                return is_array($graduate->skills) ? implode(', ', $graduate->skills) : '';

            case 'certifications':
                if (is_array($graduate->certifications)) {
                    return collect($graduate->certifications)->map(function ($cert) {
                        if (is_array($cert)) {
                            $parts = [];
                            if (! empty($cert['name'])) {
                                $parts[] = $cert['name'];
                            }
                            if (! empty($cert['issuer'])) {
                                $parts[] = "({$cert['issuer']})";
                            }
                            if (! empty($cert['date_obtained'])) {
                                $parts[] = "[{$cert['date_obtained']}]";
                            }

                            return implode(' ', $parts);
                        }

                        return $cert;
                    })->implode('; ');
                }

                return '';

            case 'allow_employer_contact':
                return $graduate->allow_employer_contact ? 'Yes' : 'No';

            case 'job_search_active':
                return $graduate->job_search_active ? 'Yes' : 'No';

            case 'academic_standing':
                return ucwords(str_replace('_', ' ', $graduate->academic_standing ?? ''));

            case 'created_at':
            case 'updated_at':
            case 'last_profile_update':
            case 'last_employment_update':
                $date = $graduate->$field;

                return $date ? $date->format('Y-m-d H:i:s') : '';

            default:
                return $graduate->$field ?? '';
        }
    }

    protected function getFieldWidth(string $field): int
    {
        $widths = [
            'name' => 25,
            'email' => 30,
            'phone' => 15,
            'address' => 40,
            'student_id' => 15,
            'course_name' => 25,
            'graduation_year' => 12,
            'gpa' => 8,
            'academic_standing' => 15,
            'employment_status' => 15,
            'current_job_title' => 25,
            'current_company' => 25,
            'current_salary' => 12,
            'employment_start_date' => 15,
            'skills' => 40,
            'certifications' => 50,
            'allow_employer_contact' => 18,
            'job_search_active' => 15,
            'profile_completion_percentage' => 18,
            'created_at' => 18,
            'updated_at' => 18,
        ];

        return $widths[$field] ?? 15;
    }

    public function getAvailableFields(): array
    {
        return [
            'basic_info' => [
                'name' => 'Full Name',
                'email' => 'Email Address',
                'phone' => 'Phone Number',
                'address' => 'Address',
                'student_id' => 'Student ID',
            ],
            'academic' => [
                'course_name' => 'Course',
                'graduation_year' => 'Graduation Year',
                'gpa' => 'GPA',
                'academic_standing' => 'Academic Standing',
            ],
            'employment' => [
                'employment_status' => 'Employment Status',
                'current_job_title' => 'Current Job Title',
                'current_company' => 'Current Company',
                'current_salary' => 'Current Salary',
                'employment_start_date' => 'Employment Start Date',
            ],
            'skills_certs' => [
                'skills' => 'Skills',
                'certifications' => 'Certifications',
            ],
            'preferences' => [
                'allow_employer_contact' => 'Allow Employer Contact',
                'job_search_active' => 'Job Search Active',
            ],
            'system' => [
                'profile_completion_percentage' => 'Profile Completion %',
                'last_profile_update' => 'Last Profile Update',
                'last_employment_update' => 'Last Employment Update',
                'created_at' => 'Created Date',
                'updated_at' => 'Last Updated',
            ],
        ];
    }
}
