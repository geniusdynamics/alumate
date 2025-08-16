<?php

namespace App\Http\Controllers;

use App\Services\UserTrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserTrainingController extends Controller
{
    public function __construct(
        private UserTrainingService $trainingService
    ) {}

    /**
     * Show the user training dashboard
     */
    public function index(): Response
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'alumni';

        return Inertia::render('Training/Index', [
            'userGuides' => $this->trainingService->getUserGuides($role),
            'videoTutorials' => $this->trainingService->getVideoTutorials($role),
            'trainingProgress' => $this->trainingService->getTrainingProgress($user),
            'faqs' => $this->trainingService->getFAQs($role),
            'role' => $role,
        ]);
    }

    /**
     * Get user guides for a specific role
     */
    public function getUserGuides(Request $request): JsonResponse
    {
        $role = $request->get('role', auth()->user()->roles->first()?->name ?? 'alumni');

        return response()->json([
            'success' => true,
            'data' => $this->trainingService->getUserGuides($role),
        ]);
    }

    /**
     * Get video tutorials for a specific role
     */
    public function getVideoTutorials(Request $request): JsonResponse
    {
        $role = $request->get('role', auth()->user()->roles->first()?->name ?? 'alumni');

        return response()->json([
            'success' => true,
            'data' => $this->trainingService->getVideoTutorials($role),
        ]);
    }

    /**
     * Get onboarding sequence for a user role
     */
    public function getOnboardingSequence(Request $request): JsonResponse
    {
        $role = $request->get('role', auth()->user()->roles->first()?->name ?? 'alumni');

        return response()->json([
            'success' => true,
            'data' => $this->trainingService->getOnboardingSequence($role),
        ]);
    }

    /**
     * Get FAQs for a specific role
     */
    public function getFAQs(Request $request): JsonResponse
    {
        $role = $request->get('role', auth()->user()->roles->first()?->name ?? 'alumni');

        return response()->json([
            'success' => true,
            'data' => $this->trainingService->getFAQs($role),
        ]);
    }

    /**
     * Get training progress for the authenticated user
     */
    public function getTrainingProgress(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => $this->trainingService->getTrainingProgress($user),
        ]);
    }

    /**
     * Mark a training step as completed
     */
    public function markStepCompleted(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string',
        ]);

        $user = auth()->user();
        $success = $this->trainingService->markStepCompleted($user, $request->step_id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Step marked as completed' : 'Failed to mark step as completed',
            'data' => $this->trainingService->getTrainingProgress($user),
        ]);
    }

    /**
     * Show a specific user guide
     */
    public function showGuide(string $guideId): Response
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'alumni';
        $guides = $this->trainingService->getUserGuides($role);

        $guide = $guides->firstWhere('id', $guideId);

        if (! $guide) {
            abort(404, 'Guide not found');
        }

        return Inertia::render('Training/Guide', [
            'guide' => $guide,
            'role' => $role,
            'trainingProgress' => $this->trainingService->getTrainingProgress($user),
        ]);
    }

    /**
     * Show video tutorial player
     */
    public function showVideoTutorial(string $tutorialId): Response
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'alumni';
        $tutorials = $this->trainingService->getVideoTutorials($role);

        $tutorial = $tutorials->firstWhere('id', $tutorialId);

        if (! $tutorial) {
            abort(404, 'Tutorial not found');
        }

        return Inertia::render('Training/VideoTutorial', [
            'tutorial' => $tutorial,
            'role' => $role,
            'relatedTutorials' => $tutorials->where('category', $tutorial['category'])->take(3),
            'trainingProgress' => $this->trainingService->getTrainingProgress($user),
        ]);
    }

    /**
     * Show FAQ page
     */
    public function showFAQs(): Response
    {
        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'alumni';

        return Inertia::render('Training/FAQs', [
            'faqs' => $this->trainingService->getFAQs($role),
            'role' => $role,
        ]);
    }

    /**
     * Search training content
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $user = auth()->user();
        $role = $user->roles->first()?->name ?? 'alumni';
        $query = strtolower($request->query);

        // Search in guides
        $guides = $this->trainingService->getUserGuides($role)
            ->filter(function ($guide) use ($query) {
                return str_contains(strtolower($guide['title']), $query) ||
                       str_contains(strtolower($guide['description']), $query) ||
                       collect($guide['sections'])->some(fn ($section) => str_contains(strtolower($section), $query));
            });

        // Search in video tutorials
        $tutorials = $this->trainingService->getVideoTutorials($role)
            ->filter(function ($tutorial) use ($query) {
                return str_contains(strtolower($tutorial['title']), $query) ||
                       str_contains(strtolower($tutorial['description']), $query) ||
                       collect($tutorial['topics'])->some(fn ($topic) => str_contains(strtolower($topic), $query));
            });

        // Search in FAQs
        $faqs = $this->trainingService->getFAQs($role)
            ->filter(function ($faq) use ($query) {
                return str_contains(strtolower($faq['question']), $query) ||
                       str_contains(strtolower($faq['answer']), $query);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'guides' => $guides->values(),
                'tutorials' => $tutorials->values(),
                'faqs' => $faqs->values(),
                'total_results' => $guides->count() + $tutorials->count() + $faqs->count(),
            ],
        ]);
    }

    /**
     * Mark FAQ as helpful
     */
    public function markFAQHelpful(Request $request): JsonResponse
    {
        $request->validate([
            'faq_id' => 'required|string',
        ]);

        // In a real implementation, you would store this in the database
        // For now, we'll just return success

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
        ]);
    }

    /**
     * Submit training feedback
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:guide,tutorial,general',
            'content_id' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string|max:1000',
        ]);

        // In a real implementation, you would store this feedback in the database
        // For now, we'll just return success

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback! We use it to improve our training materials.',
        ]);
    }
}
