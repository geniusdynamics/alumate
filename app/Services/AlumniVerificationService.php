<?php

namespace App\Services;

use App\Models\AlumniVerification;
use App\Models\Institution;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * ABOUTME: Service for managing alumni verification workflows including manual review,
 * ABOUTME: email domain verification, and bulk import processing
 */
class AlumniVerificationService extends BaseService
{
    /**
     * Submit a new verification request.
     */
    public function submitVerification(array $data, int $userId): AlumniVerification
    {
        $this->ensureTenantContext();

        return $this->executeInTransaction(function () use ($data, $userId) {
            // Check if user already has a pending verification for this institution
            $existing = AlumniVerification::forUser($userId)
                ->forInstitution($data['institution_id'])
                ->pending()
                ->first();

            if ($existing) {
                throw new Exception('You already have a pending verification request for this institution.');
            }

            // Check if user is already approved for this institution
            $approved = AlumniVerification::forUser($userId)
                ->forInstitution($data['institution_id'])
                ->approved()
                ->first();

            if ($approved) {
                throw new Exception('You are already verified for this institution.');
            }

            // Determine verification method
            $method = $this->determineVerificationMethod($data, $userId);

            $verification = AlumniVerification::create([
                'user_id' => $userId,
                'institution_id' => $data['institution_id'],
                'status' => $method === 'email_domain' ? AlumniVerification::STATUS_APPROVED : AlumniVerification::STATUS_PENDING,
                'graduation_year' => $data['graduation_year'],
                'student_id' => $data['student_id'] ?? null,
                'degree' => $data['degree'] ?? null,
                'major' => $data['major'] ?? null,
                'verification_method' => $method,
                'supporting_documents' => $data['supporting_documents'] ?? null,
            ]);

            // Auto-approve if email domain matches
            if ($method === 'email_domain') {
                $verification->approve(
                    User::where('is_super_admin', true)->first()?->id ?? $userId,
                    'Auto-approved via email domain verification'
                );
            }

            $this->logActivity('verification_submitted', "Verification submitted for user {$userId}", [
                'verification_id' => $verification->id,
                'method' => $method,
            ]);

            return $verification;
        });
    }

    /**
     * Approve a verification request.
     */
    public function approveVerification(int $verificationId, int $reviewerId, ?string $notes = null): AlumniVerification
    {
        $this->ensureTenantContext();

        $verification = AlumniVerification::findOrFail($verificationId);

        if (! $verification->isPending()) {
            throw new Exception('This verification request is not pending review.');
        }

        $verification->approve($reviewerId, $notes);

        $this->logActivity('verification_approved', "Verification {$verificationId} approved by {$reviewerId}", [
            'verification_id' => $verificationId,
            'reviewer_id' => $reviewerId,
        ]);

        return $verification;
    }

    /**
     * Reject a verification request.
     */
    public function rejectVerification(int $verificationId, int $reviewerId, string $reason): AlumniVerification
    {
        $this->ensureTenantContext();

        $verification = AlumniVerification::findOrFail($verificationId);

        if (! $verification->isPending()) {
            throw new Exception('This verification request is not pending review.');
        }

        if (empty($reason)) {
            throw new Exception('A rejection reason is required.');
        }

        $verification->reject($reviewerId, $reason);

        $this->logActivity('verification_rejected', "Verification {$verificationId} rejected by {$reviewerId}", [
            'verification_id' => $verificationId,
            'reviewer_id' => $reviewerId,
            'reason' => $reason,
        ]);

        return $verification;
    }

    /**
     * Auto-verify user by email domain.
     */
    public function autoVerifyByEmail(int $userId): ?AlumniVerification
    {
        $this->ensureTenantContext();

        $user = User::findOrFail($userId);
        $institution = $this->getCurrentTenantId()
            ? Institution::find($this->getCurrentTenantId())
            : null;

        if (! $institution || ! $institution->domain) {
            return null;
        }

        $userDomain = substr(strrchr($user->email, '@'), 1);

        if ($userDomain !== $institution->domain) {
            return null;
        }

        // Check if already verified
        $existing = AlumniVerification::forUser($userId)
            ->forInstitution($institution->id)
            ->approved()
            ->first();

        if ($existing) {
            return $existing;
        }

        // Create auto-approved verification
        return $this->submitVerification([
            'institution_id' => $institution->id,
            'graduation_year' => $user->graduation_year ?? now()->year,
            'degree' => $user->degree,
        ], $userId);
    }

    /**
     * Bulk verify users from import.
     */
    public function bulkVerify(array $records, int $institutionId, int $importedBy): array
    {
        $this->ensureTenantContext();

        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($records),
        ];

        foreach ($records as $index => $record) {
            try {
                $validator = Validator::make($record, [
                    'email' => 'required|email',
                    'graduation_year' => 'required|integer|min:1900|max:'.(now()->year + 1),
                    'student_id' => 'nullable|string',
                    'degree' => 'nullable|string',
                    'major' => 'nullable|string',
                ]);

                if ($validator->fails()) {
                    $results['failed'][] = [
                        'row' => $index + 1,
                        'email' => $record['email'] ?? 'unknown',
                        'error' => $validator->errors()->first(),
                    ];

                    continue;
                }

                // Find or create user
                $user = User::where('email', $record['email'])->first();

                if (! $user) {
                    $results['failed'][] = [
                        'row' => $index + 1,
                        'email' => $record['email'],
                        'error' => 'User not found',
                    ];

                    continue;
                }

                // Check for existing approved verification
                $existing = AlumniVerification::forUser($user->id)
                    ->forInstitution($institutionId)
                    ->approved()
                    ->first();

                if ($existing) {
                    $results['failed'][] = [
                        'row' => $index + 1,
                        'email' => $record['email'],
                        'error' => 'Already verified',
                    ];

                    continue;
                }

                // Create bulk verification
                $verification = AlumniVerification::create([
                    'user_id' => $user->id,
                    'institution_id' => $institutionId,
                    'status' => AlumniVerification::STATUS_APPROVED,
                    'graduation_year' => $record['graduation_year'],
                    'student_id' => $record['student_id'] ?? null,
                    'degree' => $record['degree'] ?? null,
                    'major' => $record['major'] ?? null,
                    'verification_method' => AlumniVerification::METHOD_BULK_IMPORT,
                    'reviewed_by' => $importedBy,
                    'reviewed_at' => now(),
                ]);

                // Update user graduation info
                $user->update([
                    'graduation_year' => $record['graduation_year'],
                    'degree' => $record['degree'] ?? $user->degree,
                ]);

                $results['success'][] = [
                    'row' => $index + 1,
                    'email' => $record['email'],
                    'verification_id' => $verification->id,
                ];
            } catch (Exception $e) {
                $results['failed'][] = [
                    'row' => $index + 1,
                    'email' => $record['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->logActivity('bulk_verification_completed', "Bulk verification completed for institution {$institutionId}", [
            'institution_id' => $institutionId,
            'total' => $results['total'],
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
        ]);

        return $results;
    }

    /**
     * Check verification status for a user.
     */
    public function checkVerificationStatus(int $userId, ?int $institutionId = null): array
    {
        $query = AlumniVerification::forUser($userId);

        if ($institutionId) {
            $query->forInstitution($institutionId);
        }

        $verifications = $query->with(['institution', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'is_verified' => $verifications->contains(fn ($v) => $v->isApproved()),
            'verifications' => $verifications,
            'pending_count' => $verifications->where('status', AlumniVerification::STATUS_PENDING)->count(),
            'approved_count' => $verifications->where('status', AlumniVerification::STATUS_APPROVED)->count(),
            'rejected_count' => $verifications->where('status', AlumniVerification::STATUS_REJECTED)->count(),
        ];
    }

    /**
     * Upload supporting document.
     */
    public function uploadDocument(int $verificationId, UploadedFile $file, int $userId): array
    {
        $verification = AlumniVerification::findOrFail($verificationId);

        // Ensure user owns this verification
        if ($verification->user_id !== $userId) {
            throw new Exception('Unauthorized access to verification.');
        }

        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $extension = $file->getClientOriginalExtension();

        if (! in_array(strtolower($extension), $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed: '.implode(', ', $allowedTypes));
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file->getSize() > $maxSize) {
            throw new Exception('File size exceeds 10MB limit.');
        }

        $path = $file->store(
            'verification-documents/'.$verificationId,
            'private'
        );

        $verification->addDocument(
            $path,
            $extension,
            $file->getClientOriginalName()
        );

        $this->logActivity('document_uploaded', "Document uploaded for verification {$verificationId}", [
            'verification_id' => $verificationId,
            'file_name' => $file->getClientOriginalName(),
        ]);

        return [
            'path' => $path,
            'url' => asset('storage/'.$path),
            'name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Delete supporting document.
     */
    public function deleteDocument(int $verificationId, string $path, int $userId): bool
    {
        $verification = AlumniVerification::findOrFail($verificationId);

        // Ensure user owns this verification
        if ($verification->user_id !== $userId) {
            throw new Exception('Unauthorized access to verification.');
        }

        // Verify document exists in verification
        $documents = $verification->supporting_documents ?? [];
        $found = false;
        foreach ($documents as $doc) {
            if ($doc['path'] === $path) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw new Exception('Document not found.');
        }

        // Delete from storage
        Storage::disk('private')->delete($path);

        // Remove from verification record
        $verification->removeDocument($path);

        $this->logActivity('document_deleted', "Document deleted from verification {$verificationId}", [
            'verification_id' => $verificationId,
            'path' => $path,
        ]);

        return true;
    }

    /**
     * Get pending verifications for admin review.
     */
    public function getPendingVerifications(int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        return AlumniVerification::forInstitution($institutionId)
            ->pending()
            ->with(['user', 'institution'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get verification analytics.
     */
    public function getAnalytics(int $institutionId, ?int $days = 30): array
    {
        $baseQuery = AlumniVerification::forInstitution($institutionId);

        if ($days) {
            $baseQuery->recent($days);
        }

        return [
            'summary' => AlumniVerification::getStatistics($institutionId),
            'by_graduation_year' => $baseQuery->clone()
                ->selectRaw('graduation_year, status, COUNT(*) as count')
                ->groupBy('graduation_year', 'status')
                ->orderBy('graduation_year', 'desc')
                ->get(),
            'by_method' => $baseQuery->clone()
                ->selectRaw('verification_method, status, COUNT(*) as count')
                ->groupBy('verification_method', 'status')
                ->get(),
            'timeline' => $baseQuery->clone()
                ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
                ->groupBy('date', 'status')
                ->orderBy('date', 'asc')
                ->get(),
            'average_review_time' => $baseQuery->clone()
                ->whereNotNull('reviewed_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (reviewed_at - created_at))) as avg_seconds')
                ->value('avg_seconds'),
        ];
    }

    /**
     * Resubmit a rejected verification.
     */
    public function resubmitVerification(int $verificationId, array $data, int $userId): AlumniVerification
    {
        $verification = AlumniVerification::findOrFail($verificationId);

        if ($verification->user_id !== $userId) {
            throw new Exception('Unauthorized access to verification.');
        }

        if (! $verification->isRejected()) {
            throw new Exception('Only rejected verifications can be resubmitted.');
        }

        // Update with new data
        $verification->update([
            'status' => AlumniVerification::STATUS_PENDING,
            'graduation_year' => $data['graduation_year'] ?? $verification->graduation_year,
            'student_id' => $data['student_id'] ?? $verification->student_id,
            'degree' => $data['degree'] ?? $verification->degree,
            'major' => $data['major'] ?? $verification->major,
            'supporting_documents' => $data['supporting_documents'] ?? $verification->supporting_documents,
            'verification_method' => AlumniVerification::METHOD_MANUAL,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);

        $this->logActivity('verification_resubmitted', "Verification {$verificationId} resubmitted", [
            'verification_id' => $verificationId,
        ]);

        return $verification;
    }

    /**
     * Determine verification method based on data.
     */
    private function determineVerificationMethod(array $data, int $userId): string
    {
        $user = User::findOrFail($userId);
        $institution = Institution::findOrFail($data['institution_id']);

        // Check if user email matches institution domain
        if ($institution->domain) {
            $userDomain = substr(strrchr($user->email, '@'), 1);
            if ($userDomain === $institution->domain) {
                return AlumniVerification::METHOD_EMAIL_DOMAIN;
            }
        }

        return AlumniVerification::METHOD_MANUAL;
    }
}
