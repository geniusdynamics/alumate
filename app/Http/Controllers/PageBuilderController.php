<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Services\PublishingWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ABOUTME: PageBuilderController handles the page builder interface for creating and managing landing pages.
 * ABOUTME: This controller provides the UI interface for the drag-and-drop page builder functionality.
 */
class PageBuilderController extends Controller
{
    public function __construct(
        private PublishingWorkflowService $publishingWorkflowService
    ) {}

    /**
     * Display a listing of pages in the page builder
     */
    public function index(Request $request): Response
    {
        $pages = LandingPage::query()
            ->when($request->search, fn($query) => $query->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn($query) => $query->where('status', $request->status))
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return Inertia::render('PageBuilder/Index', [
            'pages' => $pages,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    /**
     * Show the form for creating a new page
     */
    public function create(): Response
    {
        return Inertia::render('PageBuilder/Create');
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
            'content' => 'nullable|array'
        ]);

        $page = LandingPage::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'template_id' => $validated['template_id'] ?? null,
            'content' => $validated['content'] ?? [],
            'status' => 'draft',
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Page created successfully',
            'page' => $page
        ], 201);
    }

    /**
     * Show the form for editing the specified page
     */
    public function edit(LandingPage $page): Response
    {
        return Inertia::render('PageBuilder/Edit', [
            'page' => $page
        ]);
    }

    /**
     * Update the specified page
     */
    public function update(Request $request, LandingPage $page): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'sometimes|array',
            'meta_data' => 'sometimes|array'
        ]);

        $page->update($validated);

        return response()->json([
            'message' => 'Page updated successfully',
            'page' => $page
        ]);
    }

    /**
     * Publish the specified page
     */
    public function publish(LandingPage $page): JsonResponse
    {
        try {
            $this->publishingWorkflowService->publishPage($page);
            
            return response()->json([
                'message' => 'Page published successfully',
                'page' => $page->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to publish page: ' . $e->getMessage()
            ], 500);
        }
    }
}