<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFormBuilderRequest;
use App\Http\Requests\FormSubmissionRequest;
use App\Http\Requests\UpdateFormBuilderRequest;
use App\Models\FormBuilder;
use App\Services\FormBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormBuilderController extends Controller
{
    public function __construct(
        private FormBuilderService $formBuilderService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $forms = FormBuilder::with('fields')
            ->when($request->page_id, fn ($query) => $query->where('page_id', $request->page_id))
            ->when($request->is_active !== null, fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($forms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFormBuilderRequest $request): JsonResponse
    {
        $form = $this->formBuilderService->createForm($request->validated());

        return response()->json([
            'message' => 'Form created successfully',
            'form' => $form,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FormBuilder $form): JsonResponse
    {
        $form->load(['fields', 'submissions' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json($form);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFormBuilderRequest $request, FormBuilder $form): JsonResponse
    {
        $form = $this->formBuilderService->updateForm($form, $request->validated());

        return response()->json([
            'message' => 'Form updated successfully',
            'form' => $form,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FormBuilder $form): JsonResponse
    {
        $form->delete();

        return response()->json([
            'message' => 'Form deleted successfully',
        ]);
    }

    /**
     * Submit form data
     */
    public function submit(FormSubmissionRequest $request, FormBuilder $form): JsonResponse
    {
        if (! $form->is_active) {
            return response()->json([
                'message' => 'Form is not active',
            ], 422);
        }

        try {
            $submission = $this->formBuilderService->processFormSubmission(
                $form,
                $request->validated(),
                [
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'referrer_url' => $request->header('referer'),
                    'utm_source' => $request->utm_source,
                    'utm_medium' => $request->utm_medium,
                    'utm_campaign' => $request->utm_campaign,
                ]
            );

            return response()->json([
                'message' => $form->success_message ?? 'Thank you for your submission!',
                'submission_id' => $submission->id,
                'redirect_url' => $form->redirect_url,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $form->error_message ?? 'Please correct the errors below.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Evaluate conditional logic for form fields
     */
    public function evaluateConditionalLogic(Request $request, FormBuilder $form): JsonResponse
    {
        $submissionData = $request->all();
        $visibleFields = $this->formBuilderService->evaluateConditionalLogic($form, $submissionData);

        return response()->json([
            'visible_fields' => $visibleFields,
        ]);
    }

    /**
     * Get form field types
     */
    public function getFieldTypes(): JsonResponse
    {
        $fieldTypes = [
            'text' => [
                'label' => 'Text Input',
                'icon' => 'text-fields',
                'validation_options' => ['required', 'min', 'max', 'regex'],
            ],
            'email' => [
                'label' => 'Email Input',
                'icon' => 'email',
                'validation_options' => ['required', 'email'],
            ],
            'phone' => [
                'label' => 'Phone Number',
                'icon' => 'phone',
                'validation_options' => ['required', 'regex'],
            ],
            'textarea' => [
                'label' => 'Text Area',
                'icon' => 'text-area',
                'validation_options' => ['required', 'min', 'max'],
            ],
            'select' => [
                'label' => 'Dropdown Select',
                'icon' => 'select',
                'validation_options' => ['required', 'in'],
                'requires_options' => true,
            ],
            'radio' => [
                'label' => 'Radio Buttons',
                'icon' => 'radio-button',
                'validation_options' => ['required', 'in'],
                'requires_options' => true,
            ],
            'checkbox' => [
                'label' => 'Checkboxes',
                'icon' => 'checkbox',
                'validation_options' => ['required', 'array'],
                'requires_options' => true,
            ],
            'file' => [
                'label' => 'File Upload',
                'icon' => 'file-upload',
                'validation_options' => ['required', 'file', 'mimes', 'max'],
            ],
            'date' => [
                'label' => 'Date Picker',
                'icon' => 'calendar',
                'validation_options' => ['required', 'date', 'after', 'before'],
            ],
            'number' => [
                'label' => 'Number Input',
                'icon' => 'number',
                'validation_options' => ['required', 'numeric', 'min', 'max'],
            ],
            'url' => [
                'label' => 'URL Input',
                'icon' => 'link',
                'validation_options' => ['required', 'url'],
            ],
            'hidden' => [
                'label' => 'Hidden Field',
                'icon' => 'hidden',
                'validation_options' => [],
            ],
        ];

        return response()->json($fieldTypes);
    }
}
