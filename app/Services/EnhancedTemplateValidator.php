<?php

namespace App\Services;

use App\Exceptions\TemplateValidationException;
use App\Exceptions\TemplateSecurityException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

/**
 * Enhanced Template Validator
 *
 * Comprehensive template structure validation and sanitization service.
 * Extends existing validation with advanced schema validation,
 * semantic checking, and accessibility validation.
 */
class EnhancedTemplateValidator
{
    protected TemplateStructureSanitizer $sanitizer;
    protected TemplateSecurityValidator $securityValidator;

    protected array $schemaDefinitions = [
        'common' => [
            'required_fields' => ['sections'],
            'section_fields' => ['type', 'config', 'order'],
            'config_schema' => [
                'text' => ['content', 'style', 'alignment'],
                'image' => ['url', 'alt', 'width', 'height', 'caption'],
                'video' => ['url', 'poster', 'autoplay', 'controls'],
                'button' => ['text', 'url', 'style', 'target'],
                'form' => ['fields', 'submit_text', 'action'],
                'hero' => ['title', 'subtitle', 'background', 'cta'],
                'statistics' => ['items'],
                'testimonials' => ['items'],
            ]
        ],
        'landing_page' => [
            'max_sections' => 10,
            'required_section_types' => ['hero'],
            'recommended_section_types' => ['form', 'testimonials', 'button'],
        ],
        'homepage' => [
            'max_sections' => 15,
            'recommended_section_types' => ['hero', 'statistics', 'news', 'cta'],
        ],
        'email' => [
            'max_sections' => 5,
            'allowed_section_types' => ['header', 'content', 'footer'],
        ],
    ];

    protected array $accessibilityRules = [
        'color_contrast' => [
            'text_on_white' => '#000000',
            'text_on_dark' => '#FFFFFF',
        ],
        'alt_text_requirements' => ['image', 'video'],
        'semantic_structure' => ['header', 'nav', 'main', 'footer'],
        'keyboard_navigation' => ['button', 'link', 'form'],
    ];

    public function __construct()
    {
        $this->sanitizer = new TemplateStructureSanitizer();
        $this->securityValidator = new TemplateSecurityValidator();
    }

    /**
     * Comprehensive template validation
     *
     * @param array $templateStructure Template structure to validate
     * @param string $templateType Template type (landing, homepage, email, etc.)
     * @param array $validationOptions Additional validation options
     * @return array Validation results with detailed feedback
     */
    public function validateTemplate(array $templateStructure, string $templateType = 'landing_page', array $validationOptions = []): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'suggestions' => [],
            'metrics' => [
                'sections_count' => 0,
                'section_types' => [],
                'validation_time' => 0,
            ],
            'accessibility_score' => 100,
        ];

        $startTime = microtime(true);

        try {
            // Step 1: Sanitize structure
            $structure = $this->sanitizer->sanitize($templateStructure);

            // Step 2: Basic structure validation
            $structureErrors = $this->validateBasicStructure($structure, $templateType);
            $results['errors'] = array_merge($results['errors'], $structureErrors);

            // Step 3: Schema validation
            $schemaErrors = $this->validateSchemaCompliance($structure, $templateType);
            $results['errors'] = array_merge($results['errors'], $schemaErrors);

            // Step 4: Security validation
            $this->securityValidator->validate($structure);

            // Step 5: Accessibility validation
            $accessibilityResults = $this->validateAccessibility($structure);
            $results = array_merge($results, $accessibilityResults);

            // Step 6: Semantic validation
            $semanticWarnings = $this->validateSemanticStructure($structure, $templateType);
            $results['warnings'] = array_merge($results['warnings'], $semanticWarnings);

            // Step 7: Performance validation
            $performanceSuggestions = $this->validatePerformance($structure);
            $results['suggestions'] = array_merge($results['suggestions'], $performanceSuggestions);

            // Calculate metrics
            $results['metrics']['sections_count'] = isset($templateStructure['sections']) ? count($templateStructure['sections']) : 0;
            $results['metrics']['section_types'] = $this->extractSectionTypes($templateStructure);
            $results['metrics']['validation_time'] = round((microtime(true) - $startTime) * 1000, 2); // ms

            // Overall validity
            $results['valid'] = empty($results['errors']);

            if (!$results['valid']) {
                throw new TemplateValidationException(
                    "Template validation failed with " . count($results['errors']) . " errors"
                );
            }

        } catch (\Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = [
                'type' => 'validation_exception',
                'message' => $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Validate basic template structure
     */
    protected function validateBasicStructure(array $structure, string $templateType): array
    {
        $errors = [];

        // Check required fields
        if (empty($structure)) {
            $errors[] = [
                'type' => 'empty_structure',
                'message' => 'Template structure cannot be empty',
            ];
            return $errors;
        }

        $commonSchema = $this->schemaDefinitions['common'];
        $templateSchema = $this->schemaDefinitions[$templateType] ?? [];

        // Check required fields
        foreach ($commonSchema['required_fields'] as $field) {
            if (!isset($structure[$field]) || empty($structure[$field])) {
                $errors[] = [
                    'type' => 'missing_field',
                    'field' => $field,
                    'message' => "Required field '{$field}' is missing or empty",
                ];
            }
        }

        // Validate sections structure
        if (isset($structure['sections']) && is_array($structure['sections'])) {
            foreach ($structure['sections'] as $index => $section) {
                $sectionErrors = $this->validateSection($section, $index, $templateSchema);
                $errors = array_merge($errors, $sectionErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate individual section
     */
    protected function validateSection(array $section, int $index, array $templateSchema): array
    {
        $errors = [];

        // Check required section fields
        $requiredFields = $this->schemaDefinitions['common']['section_fields'];
        foreach ($requiredFields as $field) {
            if ($field === 'type' && (!isset($section[$field]) || empty($section[$field]))) {
                $errors[] = [
                    'type' => 'invalid_section',
                    'section_index' => $index,
                    'message' => "Section {$index} is missing required 'type' field",
                ];
            }
        }

        // Validate section configuration
        if (isset($section['type']) && isset($section['config'])) {
            $configErrors = $this->validateSectionConfig($section['type'], $section['config']);
            foreach ($configErrors as $configError) {
                $configError['section_index'] = $index;
                $configError['section_type'] = $section['type'];
                $errors[] = $configError;
            }
        }

        // Check allowed section types for specific template types
        if (isset($templateSchema['allowed_section_types']) && isset($section['type'])) {
            if (!in_array($section['type'], $templateSchema['allowed_section_types'])) {
                $templateTypeName = isset($templateSchema['template_type']) ? $templateSchema['template_type'] : 'template type';
                $errors[] = [
                    'type' => 'invalid_section_type',
                    'section_index' => $index,
                    'section_type' => $section['type'],
                    'message' => "Section type '{$section['type']}' is not allowed for {$templateTypeName}",
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate section configuration schema
     */
    protected function validateSectionConfig(string $sectionType, array $config): array
    {
        $errors = [];

        $configSchema = $this->schemaDefinitions['common']['config_schema'][$sectionType] ?? [];

        foreach ($configSchema as $field => $rules) {
            if (!isset($config[$field]) || empty($config[$field])) {
                $errors[] = [
                    'type' => 'missing_config_field',
                    'field' => $field,
                    'message' => "Required config field '{$field}' is missing for section type '{$sectionType}'",
                ];
            }
        }

        // Validate data types
        $errors = array_merge($errors, $this->validateConfigDataTypes($config, $configSchema));

        return $errors;
    }

    /**
     * Validate configuration data types
     */
    protected function validateConfigDataTypes(array $config, array $schema): array
    {
        $errors = [];

        // Basic type checking can be implemented here
        // This could be expanded with Laravel validation rules

        return $errors;
    }

    /**
     * Validate schema compliance
     */
    protected function validateSchemaCompliance(array $structure, string $templateType): array
    {
        $errors = [];

        $templateSchema = $this->schemaDefinitions[$templateType] ?? [];

        if (isset($templateSchema['max_sections']) && isset($structure['sections'])) {
            $sectionsCount = count($structure['sections']);
            if ($sectionsCount > $templateSchema['max_sections']) {
                $errors[] = [
                    'type' => 'too_many_sections',
                    'message' => "Template has {$sectionsCount} sections but maximum allowed is {$templateSchema['max_sections']} for template type '{$templateType}'",
                ];
            }
        }

        if (isset($templateSchema['required_section_types'])) {
            $availableTypes = $this->extractSectionTypes($structure);
            foreach ($templateSchema['required_section_types'] as $requiredType) {
                if (!in_array($requiredType, $availableTypes)) {
                    $errors[] = [
                        'type' => 'missing_required_section_type',
                        'section_type' => $requiredType,
                        'message' => "Template is missing required section type '{$requiredType}'",
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * Validate accessibility compliance
     */
    protected function validateAccessibility(array $structure): array
    {
        $accessibilityResults = [
            'errors' => [],
            'warnings' => [],
            'score' => 100,
        ];

        $sections = $structure['sections'] ?? [];

        foreach ($sections as $index => $section) {
            // Check alt text for images
            if ($section['type'] === 'image' && isset($section['config'])) {
                if (!isset($section['config']['alt']) || empty($section['config']['alt'])) {
                    $accessibilityResults['warnings'][] = [
                        'type' => 'missing_alt_text',
                        'section_index' => $index,
                        'message' => 'Image section is missing alt text for accessibility',
                    ];
                    $accessibilityResults['score'] -= 10;
                }
            }

            // Check for semantic heading structure
            if ($section['type'] === 'text' && isset($section['config']['content'])) {
                $contentScore = $this->checkContentAccessibility($section['config']['content']);
                $accessibilityResults['score'] += $contentScore;
            }

            // Check for keyboard navigation elements
            if (in_array($section['type'], ['button', 'form'])) {
                $accessibilityResults['score'] += 5; // Bonus for keyboard navigable elements
            }
        }

        return $accessibilityResults;
    }

    /**
     * Validate semantic structure
     */
    protected function validateSemanticStructure(array $structure, string $templateType): array
    {
        $warnings = [];

        $recommendedTypes = $this->schemaDefinitions[$templateType]['recommended_section_types'] ?? [];

        if (!empty($recommendedTypes)) {
            $availableTypes = $this->extractSectionTypes($structure);
            $missingTypes = array_diff($recommendedTypes, $availableTypes);

            if (!empty($missingTypes)) {
                $warnings[] = [
                    'type' => 'missing_recommended_sections',
                    'missing_types' => $missingTypes,
                    'message' => 'Consider adding these recommended section types: ' . implode(', ', $missingTypes),
                ];
            }
        }

        return $warnings;
    }

    /**
     * Validate performance considerations
     */
    protected function validatePerformance(array $structure): array
    {
        $suggestions = [];

        $sections = $structure['sections'] ?? [];
        $sectionCount = count($sections);

        if ($sectionCount > 8) {
            $suggestions[] = [
                'type' => 'performance_optimization',
                'message' => 'High section count may impact performance. Consider consolidating sections.',
            ];
        }

        // Check for large images or videos
        foreach ($sections as $index => $section) {
            if (in_array($section['type'], ['image', 'video']) && isset($section['config'])) {
                if (!isset($section['config']['width']) || $section['config']['width'] > 1920) {
                    $suggestions[] = [
                        'type' => 'optimize_media',
                        'section_index' => $index,
                        'message' => 'Consider optimizing media size for better performance',
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Extract section types from structure
     */
    protected function extractSectionTypes(array $structure): array
    {
        $sections = $structure['sections'] ?? [];
        $types = [];

        foreach ($sections as $section) {
            if (isset($section['type'])) {
                $types[] = $section['type'];
            }
        }

        return array_unique($types);
    }

    /**
     * Check content accessibility
     */
    protected function checkContentAccessibility(string $content): int
    {
        $score = 0;

        // Check for heading hierarchy
        if (preg_match('/<h[1-6]/', $content)) {
            $score += 5;
        }

        // Check for semantic elements
        if (preg_match('/<(article|section|nav|aside)/', $content)) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Sanitize and validate template in one operation
     */
    public function sanitizeAndValidate(array $structure, string $templateType = 'landing_page'): array
    {
        $sanitizedStructure = $this->sanitizer->sanitize($structure);
        return $this->validateTemplate($sanitizedStructure, $templateType);
    }

    /**
     * Get validation rules for a template type
     */
    public function getValidationRules(string $templateType): array
    {
        return $this->schemaDefinitions[$templateType] ?? $this->schemaDefinitions['common'];
    }

    /**
     * Get template type suggestions based on content
     */
    public function suggestTemplateType(array $structure): array
    {
        $sectionTypes = $this->extractSectionTypes($structure);
        $sectionCount = count($structure['sections'] ?? []);

        $suggestions = [];

        if (in_array('hero', $sectionTypes) && in_array('form', $sectionTypes) && $sectionCount <= 10) {
            $suggestions[] = [
                'type' => 'landing_page',
                'confidence' => 0.9,
                'reason' => 'Contains hero and form sections typical of landing pages',
            ];
        }

        if (in_array('hero', $sectionTypes) && in_array('statistics', $sectionTypes) && $sectionCount > 10) {
            $suggestions[] = [
                'type' => 'homepage',
                'confidence' => 0.8,
                'reason' => 'Contains hero, statistics, and multiple sections typical of homepages',
            ];
        }

        if (in_array('header', $sectionTypes) && in_array('footer', $sectionTypes) && $sectionCount <= 5) {
            $suggestions[] = [
                'type' => 'email',
                'confidence' => 0.7,
                'reason' => 'Contains header and footer sections typical of email templates',
            ];
        }

        return $suggestions;
    }
}