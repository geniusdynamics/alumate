<?php

namespace App\Services;

use App\Models\EmailSequence;
use App\Models\SequenceEmail;
use App\Models\SequenceEnrollment;
use App\Models\EmailSend;
use App\Models\Lead;
use App\Models\EmailTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

/**
 * Email Sequence Service
 *
 * Core business logic for managing automated email sequences, lead enrollment,
 * and sequence progression with tenant isolation and comprehensive error handling.
 */
class EmailSequenceService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'email_sequences_';
    private const CACHE_DURATION = 300; // 5 minutes
    private const SEQUENCE_CACHE_DURATION = 600; // 10 minutes

    /**
     * Create a new email sequence
     *
     * @param array $data Sequence data
     * @return EmailSequence
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createSequence(array $data): EmailSequence
    {
        $this->validateSequenceData($data);

        try {
            DB::beginTransaction();

            $sequence = EmailSequence::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'audience_type' => $data['audience_type'],
                'trigger_type' => $data['trigger_type'],
                'trigger_conditions' => $data['trigger_conditions'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'tenant_id' => tenant()->id,
            ]);

            // Create sequence emails if provided
            if (isset($data['emails']) && is_array($data['emails'])) {
                $this->createSequenceEmails($sequence, $data['emails']);
            }

            DB::commit();

            // Clear relevant caches
            $this->clearSequenceCache();

            Log::info('Email sequence created', [
                'sequence_id' => $sequence->id,
                'tenant_id' => tenant()->id,
                'name' => $sequence->name,
            ]);

            return $sequence;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create email sequence', [
                'error' => $e->getMessage(),
                'data' => $data,
                'tenant_id' => tenant()->id,
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing email sequence
     *
     * @param int $sequenceId
     * @param array $data
     * @return EmailSequence
     */
    public function updateSequence(int $sequenceId, array $data): EmailSequence
    {
        $sequence = $this->getSequenceById($sequenceId);
        $this->validateSequenceData($data, false);

        try {
            DB::beginTransaction();

            $sequence->update([
                'name' => $data['name'] ?? $sequence->name,
                'description' => $data['description'] ?? $sequence->description,
                'audience_type' => $data['audience_type'] ?? $sequence->audience_type,
                'trigger_type' => $data['trigger_type'] ?? $sequence->trigger_type,
                'trigger_conditions' => $data['trigger_conditions'] ?? $sequence->trigger_conditions,
                'is_active' => $data['is_active'] ?? $sequence->is_active,
            ]);

            // Update sequence emails if provided
            if (isset($data['emails']) && is_array($data['emails'])) {
                $this->updateSequenceEmails($sequence, $data['emails']);
            }

            DB::commit();

            // Clear caches
            $this->clearSequenceCache($sequenceId);

            Log::info('Email sequence updated', [
                'sequence_id' => $sequenceId,
                'tenant_id' => tenant()->id,
            ]);

            return $sequence;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update email sequence', [
                'sequence_id' => $sequenceId,
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);
            throw $e;
        }
    }

    /**
     * Delete an email sequence
     *
     * @param int $sequenceId
     * @return bool
     */
    public function deleteSequence(int $sequenceId): bool
    {
        $sequence = $this->getSequenceById($sequenceId);

        try {
            DB::beginTransaction();

            // Delete associated enrollments and sends
            $sequence->enrollments()->delete();
            $sequence->emails()->delete();
            $sequence->delete();

            DB::commit();

            // Clear caches
            $this->clearSequenceCache($sequenceId);

            Log::info('Email sequence deleted', [
                'sequence_id' => $sequenceId,
                'tenant_id' => tenant()->id,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete email sequence', [
                'sequence_id' => $sequenceId,
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);
            throw $e;
        }
    }

    /**
     * Get sequence by ID with tenant isolation
     *
     * @param int $sequenceId
     * @return EmailSequence
     * @throws ModelNotFoundException
     */
    public function getSequenceById(int $sequenceId): EmailSequence
    {
        $cacheKey = self::CACHE_PREFIX . "sequence_{$sequenceId}";

        return Cache::remember($cacheKey, self::SEQUENCE_CACHE_DURATION, function () use ($sequenceId) {
            return EmailSequence::where('tenant_id', tenant()->id)
                ->with(['emails.template', 'enrollments'])
                ->findOrFail($sequenceId);
        });
    }

    /**
     * Get all sequences for current tenant
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllSequences(array $filters = []): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'all_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = EmailSequence::where('tenant_id', tenant()->id)
                ->with(['emails', 'enrollments']);

            // Apply filters
            if (isset($filters['audience_type'])) {
                $query->where('audience_type', $filters['audience_type']);
            }

            if (isset($filters['trigger_type'])) {
                $query->where('trigger_type', $filters['trigger_type']);
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            return $query->orderBy('created_at', 'desc')->get();
        });
    }

    /**
     * Enroll a lead in a sequence
     *
     * @param int $sequenceId
     * @param int $leadId
     * @return SequenceEnrollment
     * @throws \Exception
     */
    public function enrollLead(int $sequenceId, int $leadId): SequenceEnrollment
    {
        $sequence = $this->getSequenceById($sequenceId);
        $lead = Lead::where('tenant_id', tenant()->id)->findOrFail($leadId);

        // Check if lead is already enrolled
        $existingEnrollment = SequenceEnrollment::where('sequence_id', $sequenceId)
            ->where('lead_id', $leadId)
            ->first();

        if ($existingEnrollment) {
            throw new \Exception('Lead is already enrolled in this sequence');
        }

        try {
            DB::beginTransaction();

            $enrollment = SequenceEnrollment::create([
                'sequence_id' => $sequenceId,
                'lead_id' => $leadId,
                'current_step' => 0,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);

            DB::commit();

            // Clear caches
            $this->clearSequenceCache($sequenceId);

            Log::info('Lead enrolled in sequence', [
                'sequence_id' => $sequenceId,
                'lead_id' => $leadId,
                'enrollment_id' => $enrollment->id,
                'tenant_id' => tenant()->id,
            ]);

            return $enrollment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to enroll lead in sequence', [
                'sequence_id' => $sequenceId,
                'lead_id' => $leadId,
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);
            throw $e;
        }
    }

    /**
     * Process sequence progression for a lead
     *
     * @param int $enrollmentId
     * @return bool
     */
    public function processSequenceProgression(int $enrollmentId): bool
    {
        $enrollment = SequenceEnrollment::with(['sequence.emails'])
            ->findOrFail($enrollmentId);

        if ($enrollment->status !== 'active') {
            return false;
        }

        $sequence = $enrollment->sequence;
        $currentStep = $enrollment->current_step;

        // Get next email in sequence
        $nextEmail = $sequence->emails()
            ->where('send_order', '>', $currentStep)
            ->orderBy('send_order')
            ->first();

        if (!$nextEmail) {
            // Sequence completed
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info('Sequence completed for lead', [
                'enrollment_id' => $enrollmentId,
                'lead_id' => $enrollment->lead_id,
                'sequence_id' => $sequence->id,
            ]);

            return true;
        }

        // Check if it's time to send the next email
        $enrolledAt = Carbon::parse($enrollment->enrolled_at);
        $sendTime = $enrolledAt->addHours($nextEmail->delay_hours);

        if (now()->gte($sendTime)) {
            $this->sendSequenceEmail($enrollment, $nextEmail);
            $enrollment->update(['current_step' => $nextEmail->send_order]);
        }

        return true;
    }

    /**
     * Pause sequence enrollment
     *
     * @param int $enrollmentId
     * @return bool
     */
    public function pauseEnrollment(int $enrollmentId): bool
    {
        $enrollment = SequenceEnrollment::findOrFail($enrollmentId);

        $enrollment->update(['status' => 'paused']);

        Log::info('Sequence enrollment paused', [
            'enrollment_id' => $enrollmentId,
            'lead_id' => $enrollment->lead_id,
        ]);

        return true;
    }

    /**
     * Resume sequence enrollment
     *
     * @param int $enrollmentId
     * @return bool
     */
    public function resumeEnrollment(int $enrollmentId): bool
    {
        $enrollment = SequenceEnrollment::findOrFail($enrollmentId);

        $enrollment->update(['status' => 'active']);

        Log::info('Sequence enrollment resumed', [
            'enrollment_id' => $enrollmentId,
            'lead_id' => $enrollment->lead_id,
        ]);

        return true;
    }

    /**
     * Unsubscribe lead from sequence
     *
     * @param int $enrollmentId
     * @return bool
     */
    public function unsubscribeFromSequence(int $enrollmentId): bool
    {
        $enrollment = SequenceEnrollment::findOrFail($enrollmentId);

        $enrollment->update(['status' => 'unsubscribed']);

        Log::info('Lead unsubscribed from sequence', [
            'enrollment_id' => $enrollmentId,
            'lead_id' => $enrollment->lead_id,
        ]);

        return true;
    }

    /**
     * Validate sequence configuration
     *
     * @param int $sequenceId
     * @return array Validation results
     */
    public function validateSequenceConfiguration(int $sequenceId): array
    {
        $sequence = $this->getSequenceById($sequenceId);
        $errors = [];
        $warnings = [];

        // Check if sequence has emails
        if ($sequence->emails->isEmpty()) {
            $errors[] = 'Sequence must have at least one email';
        }

        // Check email ordering
        $emails = $sequence->emails->sortBy('send_order');
        $expectedOrder = 1;
        foreach ($emails as $email) {
            if ($email->send_order !== $expectedOrder) {
                $warnings[] = "Email order gap detected at step {$expectedOrder}";
                break;
            }
            $expectedOrder++;
        }

        // Check template references
        foreach ($sequence->emails as $email) {
            if (!$email->template) {
                $errors[] = "Email step {$email->send_order} references invalid template";
            }
        }

        // Check trigger conditions
        if ($sequence->trigger_type !== 'manual' && empty($sequence->trigger_conditions)) {
            $warnings[] = 'Trigger conditions are recommended for automated sequences';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get sequence statistics
     *
     * @param int $sequenceId
     * @return array
     */
    public function getSequenceStats(int $sequenceId): array
    {
        $sequence = $this->getSequenceById($sequenceId);

        $enrollments = $sequence->enrollments;
        $totalEnrollments = $enrollments->count();
        $activeEnrollments = $enrollments->where('status', 'active')->count();
        $completedEnrollments = $enrollments->where('status', 'completed')->count();

        // Calculate completion rate
        $completionRate = $totalEnrollments > 0
            ? round(($completedEnrollments / $totalEnrollments) * 100, 2)
            : 0;

        // Get email send statistics
        $emailSends = EmailSend::whereIn('enrollment_id', $enrollments->pluck('id'))->get();
        $totalSends = $emailSends->count();
        $deliveredSends = $emailSends->where('status', 'delivered')->count();
        $openedSends = $emailSends->where('status', 'opened')->count();
        $clickedSends = $emailSends->where('status', 'clicked')->count();

        $deliveryRate = $totalSends > 0 ? round(($deliveredSends / $totalSends) * 100, 2) : 0;
        $openRate = $deliveredSends > 0 ? round(($openedSends / $deliveredSends) * 100, 2) : 0;
        $clickRate = $deliveredSends > 0 ? round(($clickedSends / $deliveredSends) * 100, 2) : 0;

        return [
            'enrollments' => [
                'total' => $totalEnrollments,
                'active' => $activeEnrollments,
                'completed' => $completedEnrollments,
                'completion_rate' => $completionRate,
            ],
            'performance' => [
                'total_sends' => $totalSends,
                'delivery_rate' => $deliveryRate,
                'open_rate' => $openRate,
                'click_rate' => $clickRate,
            ],
        ];
    }

    /**
     * Duplicate an existing sequence
     *
     * @param int $sequenceId
     * @param string $newName
     * @return EmailSequence
     */
    public function duplicateSequence(int $sequenceId, string $newName): EmailSequence
    {
        $originalSequence = $this->getSequenceById($sequenceId);

        try {
            DB::beginTransaction();

            $newSequence = EmailSequence::create([
                'name' => $newName,
                'description' => $originalSequence->description,
                'audience_type' => $originalSequence->audience_type,
                'trigger_type' => $originalSequence->trigger_type,
                'trigger_conditions' => $originalSequence->trigger_conditions,
                'is_active' => false, // Start as inactive
                'tenant_id' => tenant()->id,
            ]);

            // Duplicate sequence emails
            foreach ($originalSequence->emails as $email) {
                SequenceEmail::create([
                    'sequence_id' => $newSequence->id,
                    'template_id' => $email->template_id,
                    'subject_line' => $email->subject_line,
                    'delay_hours' => $email->delay_hours,
                    'send_order' => $email->send_order,
                    'trigger_conditions' => $email->trigger_conditions,
                ]);
            }

            DB::commit();

            // Clear caches
            $this->clearSequenceCache();

            Log::info('Email sequence duplicated', [
                'original_sequence_id' => $sequenceId,
                'new_sequence_id' => $newSequence->id,
                'tenant_id' => tenant()->id,
            ]);

            return $newSequence;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to duplicate email sequence', [
                'sequence_id' => $sequenceId,
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);
            throw $e;
        }
    }

    /**
     * Validate sequence data
     *
     * @param array $data
     * @param bool $isNew
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateSequenceData(array $data, bool $isNew = true): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'audience_type' => 'required|in:individual,institutional,employer',
            'trigger_type' => 'required|in:form_submission,page_visit,behavior,manual',
            'trigger_conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ];

        if (isset($data['emails'])) {
            $rules['emails'] = 'array';
            $rules['emails.*.template_id'] = 'required|exists:email_templates,id';
            $rules['emails.*.subject_line'] = 'nullable|string|max:255';
            $rules['emails.*.delay_hours'] = 'required|integer|min:0';
            $rules['emails.*.send_order'] = 'required|integer|min:1';
        }

        Validator::make($data, $rules)->validate();
    }

    /**
     * Create sequence emails
     *
     * @param EmailSequence $sequence
     * @param array $emailsData
     */
    private function createSequenceEmails(EmailSequence $sequence, array $emailsData): void
    {
        foreach ($emailsData as $emailData) {
            SequenceEmail::create([
                'sequence_id' => $sequence->id,
                'template_id' => $emailData['template_id'],
                'subject_line' => $emailData['subject_line'] ?? null,
                'delay_hours' => $emailData['delay_hours'],
                'send_order' => $emailData['send_order'],
                'trigger_conditions' => $emailData['trigger_conditions'] ?? null,
            ]);
        }
    }

    /**
     * Update sequence emails
     *
     * @param EmailSequence $sequence
     * @param array $emailsData
     */
    private function updateSequenceEmails(EmailSequence $sequence, array $emailsData): void
    {
        // Delete existing emails
        $sequence->emails()->delete();

        // Create new emails
        $this->createSequenceEmails($sequence, $emailsData);
    }

    /**
     * Send sequence email
     *
     * @param SequenceEnrollment $enrollment
     * @param SequenceEmail $sequenceEmail
     */
    private function sendSequenceEmail(SequenceEnrollment $enrollment, SequenceEmail $sequenceEmail): void
    {
        // This would integrate with the email marketing service
        // For now, we'll create a record of the send
        EmailSend::create([
            'enrollment_id' => $enrollment->id,
            'sequence_email_id' => $sequenceEmail->id,
            'lead_id' => $enrollment->lead_id,
            'subject' => $sequenceEmail->subject_line ?? $sequenceEmail->template->subject ?? 'Sequence Email',
            'status' => 'queued',
        ]);

        Log::info('Sequence email queued for sending', [
            'enrollment_id' => $enrollment->id,
            'sequence_email_id' => $sequenceEmail->id,
            'lead_id' => $enrollment->lead_id,
        ]);
    }

    /**
     * Clear sequence-related caches
     *
     * @param int|null $sequenceId
     */
    private function clearSequenceCache(?int $sequenceId = null): void
    {
        if ($sequenceId) {
            Cache::forget(self::CACHE_PREFIX . "sequence_{$sequenceId}");
        }

        Cache::forget(self::CACHE_PREFIX . 'all');
        Cache::forget(self::CACHE_PREFIX . 'all_' . md5('')); // Clear all filtered caches
    }
}