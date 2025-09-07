<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EnrollUsersRequest;
use App\Http\Requests\Api\StoreEmailSequenceRequest;
use App\Http\Requests\Api\StoreSequenceEmailRequest;
use App\Http\Requests\Api\UpdateEmailSequenceRequest;
use App\Http\Requests\Api\UpdateSequenceEmailRequest;
use App\Http\Resources\EmailSequenceResource;
use App\Http\Resources\SequenceEmailResource;
use App\Http\Resources\SequenceEnrollmentResource;
use App\Models\EmailSequence;
use App\Models\SequenceEmail;
use App\Models\SequenceEnrollment;
use App\Services\EmailSequenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Email Sequence API Controller
 *
 * Handles all email sequence management operations including CRUD operations,
 * email management within sequences, and user enrollment management.
 */
class EmailSequenceController extends Controller
{
    public function __construct(
        protected EmailSequenceService $emailSequenceService
    ) {}

    /**
     * Get all email sequences for the authenticated user's tenant
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = EmailSequence::with(['sequenceEmails', 'sequenceEnrollments'])
            ->where('tenant_id', tenant()->id);

        // Apply filters
        if ($request->has('audience_type')) {
            $query->where('audience_type', $request->audience_type);
        }

        if ($request->has('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
        }

        $sequences = $query->latest()->paginate(15);

        return response()->json([
            'sequences' => EmailSequenceResource::collection($sequences),
            'stats' => [
                'total' => EmailSequence::where('tenant_id', tenant()->id)->count(),
                'active' => EmailSequence::where('tenant_id', tenant()->id)->where('is_active', true)->count(),
                'inactive' => EmailSequence::where('tenant_id', tenant()->id)->where('is_active', false)->count(),
            ],
        ]);
    }

    /**
     * Create a new email sequence
     *
     * @param StoreEmailSequenceRequest $request
     * @return JsonResponse
     */
    public function store(StoreEmailSequenceRequest $request): JsonResponse
    {
        try {
            $sequence = $this->emailSequenceService->createSequence($request->validated());

            return response()->json([
                'message' => 'Email sequence created successfully',
                'sequence' => new EmailSequenceResource($sequence->load(['sequenceEmails', 'sequenceEnrollments'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create email sequence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific email sequence
     *
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function show(EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('view', $sequence);

        return response()->json([
            'sequence' => new EmailSequenceResource($sequence->load(['sequenceEmails.template', 'sequenceEnrollments.lead'])),
            'stats' => $sequence->getSequenceStats(),
        ]);
    }

    /**
     * Update an email sequence
     *
     * @param UpdateEmailSequenceRequest $request
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function update(UpdateEmailSequenceRequest $request, EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('update', $sequence);

        try {
            $updatedSequence = $this->emailSequenceService->updateSequence($sequence->id, $request->validated());

            return response()->json([
                'message' => 'Email sequence updated successfully',
                'sequence' => new EmailSequenceResource($updatedSequence->load(['sequenceEmails', 'sequenceEnrollments'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update email sequence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an email sequence
     *
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function destroy(EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('delete', $sequence);

        try {
            $this->emailSequenceService->deleteSequence($sequence->id);

            return response()->json([
                'message' => 'Email sequence deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete email sequence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get emails for a specific sequence
     *
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function getEmails(EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('view', $sequence);

        $emails = $sequence->sequenceEmails()
            ->with('template')
            ->orderBy('send_order')
            ->get();

        return response()->json([
            'emails' => SequenceEmailResource::collection($emails),
            'count' => $emails->count(),
        ]);
    }

    /**
     * Add an email to a sequence
     *
     * @param StoreSequenceEmailRequest $request
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function addEmail(StoreSequenceEmailRequest $request, EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('update', $sequence);

        try {
            $email = $this->emailSequenceService->addEmailToSequence($sequence->id, $request->validated());

            return response()->json([
                'message' => 'Email added to sequence successfully',
                'email' => new SequenceEmailResource($email->load('template')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add email to sequence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a sequence email
     *
     * @param UpdateSequenceEmailRequest $request
     * @param EmailSequence $sequence
     * @param SequenceEmail $email
     * @return JsonResponse
     */
    public function updateEmail(UpdateSequenceEmailRequest $request, EmailSequence $sequence, SequenceEmail $email): JsonResponse
    {
        Gate::authorize('update', $sequence);

        // Ensure the email belongs to the sequence
        if ($email->sequence_id !== $sequence->id) {
            return response()->json([
                'message' => 'Email does not belong to this sequence',
            ], 404);
        }

        try {
            $updatedEmail = $this->emailSequenceService->updateSequenceEmail($email->id, $request->validated());

            return response()->json([
                'message' => 'Sequence email updated successfully',
                'email' => new SequenceEmailResource($updatedEmail->load('template')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update sequence email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove an email from a sequence
     *
     * @param EmailSequence $sequence
     * @param SequenceEmail $email
     * @return JsonResponse
     */
    public function removeEmail(EmailSequence $sequence, SequenceEmail $email): JsonResponse
    {
        Gate::authorize('update', $sequence);

        // Ensure the email belongs to the sequence
        if ($email->sequence_id !== $sequence->id) {
            return response()->json([
                'message' => 'Email does not belong to this sequence',
            ], 404);
        }

        try {
            $this->emailSequenceService->removeEmailFromSequence($email->id);

            return response()->json([
                'message' => 'Email removed from sequence successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove email from sequence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get enrollments for a specific sequence
     *
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function getEnrollments(EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('view', $sequence);

        $enrollments = $sequence->sequenceEnrollments()
            ->with('lead')
            ->latest()
            ->paginate(15);

        return response()->json([
            'enrollments' => SequenceEnrollmentResource::collection($enrollments),
            'stats' => [
                'total' => $sequence->sequenceEnrollments()->count(),
                'active' => $sequence->sequenceEnrollments()->where('status', 'active')->count(),
                'completed' => $sequence->sequenceEnrollments()->where('status', 'completed')->count(),
                'paused' => $sequence->sequenceEnrollments()->where('status', 'paused')->count(),
                'unsubscribed' => $sequence->sequenceEnrollments()->where('status', 'unsubscribed')->count(),
            ],
        ]);
    }

    /**
     * Enroll users in a sequence
     *
     * @param EnrollUsersRequest $request
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function enroll(EnrollUsersRequest $request, EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('update', $sequence);

        try {
            $enrollments = $this->emailSequenceService->enrollUsers($sequence->id, $request->validated()['user_ids']);

            return response()->json([
                'message' => 'Users enrolled successfully',
                'enrollments' => SequenceEnrollmentResource::collection($enrollments),
                'count' => count($enrollments),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to enroll users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unenroll a user from a sequence
     *
     * @param EmailSequence $sequence
     * @param int $userId
     * @return JsonResponse
     */
    public function unenroll(EmailSequence $sequence, int $userId): JsonResponse
    {
        Gate::authorize('update', $sequence);

        try {
            $this->emailSequenceService->unenrollUser($sequence->id, $userId);

            return response()->json([
                'message' => 'User unenrolled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to unenroll user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate an email sequence
     *
     * @param EmailSequence $sequence
     * @param Request $request
     * @return JsonResponse
     */
    public function duplicate(EmailSequence $sequence, Request $request): JsonResponse
    {
        Gate::authorize('view', $sequence);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $duplicatedSequence = $this->emailSequenceService->duplicateSequence($sequence->id, $request->name);

            return response()->json([
                'message' => 'Email sequence duplicated successfully',
                'sequence' => new EmailSequenceResource($duplicatedSequence->load(['sequenceEmails', 'sequenceEnrollments'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to duplicate email sequence',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle sequence active status
     *
     * @param EmailSequence $sequence
     * @return JsonResponse
     */
    public function toggleActive(EmailSequence $sequence): JsonResponse
    {
        Gate::authorize('update', $sequence);

        try {
            $sequence->update(['is_active' => !$sequence->is_active]);

            return response()->json([
                'message' => 'Sequence status updated successfully',
                'sequence' => new EmailSequenceResource($sequence),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update sequence status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}