<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AlumniVerification;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VerificationService
{
    /**
     * Submit a new verification request.
     */
    public function submitVerification(
        User $user,
        Tenant $tenant,
        array $data,
        ?array $documents = []
    ): AlumniVerification {
        return DB::transaction(function () use ($user, $tenant, $data, $documents) {
            // Check if user already has a pending verification
            $existingVerification = AlumniVerification::where('user_id', $user->id)
                ->where('tenant_id', $tenant->id)
                ->where('status', AlumniVerification::STATUS_PENDING)
                ->first();

            if ($existingVerification) {
                throw new \Exception('You already have a pending verification request.');
            }

            // Process uploaded documents
            $documentPaths = [];
            if (!empty($documents)) {
                foreach ($documents as $document) {
                    if ($document instanceof UploadedFile) {
                        $path = $this->storeDocument($document, $user->id);
                        $documentPaths[] = $path;
                    }
                }
            }

            // Create verification request
            $verification = AlumniVerification::create([
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'institution_id' => $data['institution_id'] ?? null,
                'status' => AlumniVerification::STATUS_PENDING,
                'verification_method' => AlumniVerification::METHOD_MANUAL,
                'graduation_year' => $data['graduation_year'] ?? null,
                'student_id' => $data['student_id'] ?? null,
                'degree' => $data['degree'] ?? null,
                'major' => $data['major'] ?? null,
                'email_domain' => $this->extractEmailDomain($user->email),
                'supporting_documents' => $documentPaths,
                'notes' => $data['notes'] ?? null,
                'submitted_at' => now(),
                'expires_at' => now()->addDays(30), // Expires after 30 days
            ]);

            // Update user status
            $user->update([
                'verification_status' => 'pending',
            ]);

            // Try automatic verification by email domain
            if (!empty($data['institution_id'])) {
                $this->attemptAutoVerification($verification);
            }

            return $verification;
        });
    }

    /**
     * Attempt automatic verification by email domain.
     */
    public function attemptAutoVerification(AlumniVerification $verification): bool
    {
        if (!$verification->institution_id) {
            return false;
        }

        $institution = Institution::find($verification->institution_id);
        if (!$institution) {
            return false;
        }

        // Get approved email domains for the institution
        $approvedDomains = $institution->getSetting('approved_email_domains', []);
        $userDomain = $verification->email_domain;

        if (in_array($userDomain, $approvedDomains)) {
            // Auto-approve if email domain matches
            $verification->update([
                'status' => AlumniVerification::STATUS_APPROVED,
                'verification_method' => AlumniVerification::METHOD_EMAIL_DOMAIN,
                'reviewed_at' => now(),
                'notes' => 'Automatically verified via email domain matching.',
            ]);

            $verification->user->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]);

            Log::info('User auto-verified via email domain', [
                'user_id' => $verification->user_id,
                'domain' => $userDomain,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Approve a verification request.
     */
    public function approveVerification(
        AlumniVerification $verification,
        User $reviewer,
        ?string $notes = null
    ): AlumniVerification {
        $verification->approve($reviewer->id, $notes);

        Log::info('Verification approved', [
            'verification_id' => $verification->id,
            'user_id' => $verification->user_id,
            'reviewer_id' => $reviewer->id,
        ]);

        return $verification->fresh();
    }

    /**
     * Reject a verification request.
     */
    public function rejectVerification(
        AlumniVerification $verification,
        User $reviewer,
        string $reason,
        ?string $notes = null
    ): AlumniVerification {
        $verification->reject($reviewer->id, $reason, $notes);

        Log::info('Verification rejected', [
            'verification_id' => $verification->id,
            'user_id' => $verification->user_id,
            'reviewer_id' => $reviewer->id,
            'reason' => $reason,
        ]);

        return $verification->fresh();
    }

    /**
     * Process bulk verification import.
     */
    public function bulkVerify(array $records, User $admin, Tenant $tenant): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($records as $index => $record) {
            try {
                DB::transaction(function () use ($record, $admin, $tenant) {
                    // Find or create user
                    $user = User::where('email', $record['email'])->first();

                    if (!$user) {
                        // Create user if not exists
                        $user = User::create([
                            'name' => $record['name'],
                            'email' => $record['email'],
                            'password' => bcrypt(Str::random(16)),
                            'verification_status' => 'verified',
                            'verified_at' => now(),
                        ]);
                    }

                    // Create approved verification
                    AlumniVerification::create([
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'institution_id' => $record['institution_id'] ?? null,
                        'status' => AlumniVerification::STATUS_APPROVED,
                        'verification_method' => AlumniVerification::METHOD_BULK_IMPORT,
                        'graduation_year' => $record['graduation_year'] ?? null,
                        'student_id' => $record['student_id'] ?? null,
                        'degree' => $record['degree'] ?? null,
                        'major' => $record['major'] ?? null,
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now(),
                        'submitted_at' => now(),
                        'notes' => 'Bulk import verification',
                    ]);

                    // Update user status
                    $user->update([
                        'verification_status' => 'verified',
                        'verified_at' => now(),
                    ]);
                });

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $index + 1,
                    'email' => $record['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk verification completed', [
            'tenant_id' => $tenant->id,
            'admin_id' => $admin->id,
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Get verification statistics.
     */
    public function getStatistics(?Tenant $tenant = null): array
    {
        $query = AlumniVerification::query();

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        return [
            'pending' => (clone $query)->where('status', AlumniVerification::STATUS_PENDING)->count(),
            'approved' => (clone $query)->where('status', AlumniVerification::STATUS_APPROVED)->count(),
            'rejected' => (clone $query)->where('status', AlumniVerification::STATUS_REJECTED)->count(),
            'total' => (clone $query)->count(),
            'avg_review_time' => $this->calculateAverageReviewTime($query),
        ];
    }

    /**
     * Check if user is verified.
     */
    public function isVerified(User $user, ?Tenant $tenant = null): bool
    {
        $query = AlumniVerification::where('user_id', $user->id)
            ->where('status', AlumniVerification::STATUS_APPROVED);

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        return $query->exists();
    }

    /**
     * Get user's verification status.
     */
    public function getVerificationStatus(User $user, ?Tenant $tenant = null): ?AlumniVerification
    {
        $query = AlumniVerification::where('user_id', $user->id);

        if ($tenant) {
            $query->where('tenant_id', $tenant->id);
        }

        return $query->latest()->first();
    }

    /**
     * Store a verification document.
     */
    private function storeDocument(UploadedFile $file, int $userId): string
    {
        $path = 'verifications/' . $userId . '/' . uniqid() . '_' . $file->getClientOriginalName();
        Storage::disk('private')->putFileAs('', $file, $path);

        return $path;
    }

    /**
     * Extract domain from email.
     */
    private function extractEmailDomain(string $email): ?string
    {
        $parts = explode('@', $email);
        return count($parts) === 2 ? $parts[1] : null;
    }

    /**
     * Calculate average review time in hours.
     */
    private function calculateAverageReviewTime($query): ?float
    {
        $verifications = (clone $query)
            ->whereNotNull('reviewed_at')
            ->whereNotNull('submitted_at')
            ->get();

        if ($verifications->isEmpty()) {
            return null;
        }

        $totalHours = $verifications->sum(function ($v) {
            return $v->submitted_at->diffInHours($v->reviewed_at);
        });

        return round($totalHours / $verifications->count(), 2);
    }

    /**
     * Validate CSV data for bulk import.
     */
    public function validateBulkImportData(array $records): array
    {
        $errors = [];
        $required = ['email', 'name', 'graduation_year'];

        foreach ($records as $index => $record) {
            $row = $index + 1;

            foreach ($required as $field) {
                if (empty($record[$field])) {
                    $errors[] = "Row {$row}: Missing required field '{$field}'";
                }
            }

            if (!empty($record['email']) && !filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$row}: Invalid email address";
            }

            if (!empty($record['graduation_year']) && !is_numeric($record['graduation_year'])) {
                $errors[] = "Row {$row}: Graduation year must be numeric";
            }
        }

        return $errors;
    }
}
