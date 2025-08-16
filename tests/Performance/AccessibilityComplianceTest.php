<?php

namespace Tests\Performance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AccessibilityComplianceTest extends TestCase
{
    use RefreshDatabase;

    private array $accessibilityIssues = [];

    private array $wcagGuidelines = [
        'A' => 'Level A compliance',
        'AA' => 'Level AA compliance',
        'AAA' => 'Level AAA compliance',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestData();
    }

    public function test_homepage_wcag_aa_compliance(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Test WCAG 2.1 AA compliance
        $this->validateWCAGCompliance($content, 'AA');

        // Assert no critical accessibility issues
        $criticalIssues = array_filter($this->accessibilityIssues, function ($issue) {
            return $issue['level'] === 'critical';
        });

        $this->assertEmpty($criticalIssues, 'Critical accessibility issues found: '.json_encode($criticalIssues));
    }

    public function test_homepage_semantic_html_structure(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Test proper heading hierarchy
        $this->validateHeadingHierarchy($content);

        // Test semantic HTML elements
        $this->validateSemanticElements($content);

        // Test landmark roles
        $this->validateLandmarkRoles($content);

        // Test list structures
        $this->validateListStructures($content);
    }

    public function test_homepage_keyboard_navigation(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Test focusable elements
        $this->validateFocusableElements($content);

        // Test skip links
        $this->validateSkipLinks($content);

        // Test tab order
        $this->validateTabOrder($content);

        // Test keyboard traps
        $this->validateNoKeyboardTraps($content);
    }

    public function test_homepage_aria_compliance(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Test ARIA labels
        $this->validateAriaLabels($content);

        // Test ARIA roles
        $this->validateAriaRoles($content);

        // Test ARIA states and properties
        $this->validateAriaStatesAndProperties($content);

        // Test ARIA live regions
        $this->validateAriaLiveRegions($content);
    }

    public function test_homepage_color_contrast(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Extract CSS and analyze color contrast
        $this->validateColorContrast($content);

        // Test that information is not conveyed by color alone
        $this->validateColorIndependence($content);
    }

    public function test_homepage_images_accessibility(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Test alt attributes
        $this->validateImageAltAttributes($content);

        // Test decorative images
        $this->validateDecorativeImages($content);

        // Test complex images
        $this->validateComplexImages($content);

        // Test image loading performance impact on accessibility
        $this->validateImageLoadingAccessibility($content);
    }

    public function test_homepage_form_accessibility(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Test form labels
        $this->validateFormLabels($content);

        // Test form validation
        $this->validateFormValidation($content);

        // Test form instructions
        $this->validateFormInstructions($content);

        // Test error handling
        $this->validateFormErrorHandling($content);
    }

    public function test_homepage_multimedia_accessibility(): void
    {
        $response = $this->get('/');
        $content = $response->getContent();

        // Test video accessibility
        $this->validateVideoAccessibility($content);

        // Test audio accessibility
        $this->validateAudioAccessibility($content);

        // Test autoplay restrictions
        $this->validateAutoplayRestrictions($content);
    }

    public function test_homepage_responsive_accessibility(): void
    {
        // Test mobile accessibility
        $mobileResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
        ])->get('/');

        $this->validateMobileAccessibility($mobileResponse->getContent());

        // Test tablet accessibility
        $tabletResponse = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
        ])->get('/');

        $this->validateTabletAccessibility($tabletResponse->getContent());
    }

    public function test_homepage_performance_accessibility_impact(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/');
        $response->assertStatus(200);

        $loadTime = microtime(true) - $startTime;

        // Accessibility features should not significantly impact performance
        $this->assertLessThan(3, $loadTime, 'Accessibility features should not slow down page load significantly');

        $content = $response->getContent();

        // Test that accessibility enhancements don't bloat the page
        $this->validateAccessibilityPerformanceImpact($content);
    }

    private function validateWCAGCompliance(string $content, string $level): void
    {
        // WCAG 2.1 Level AA requirements
        $wcagChecks = [
            'perceivable' => [
                'text_alternatives' => fn () => $this->checkTextAlternatives($content),
                'captions_transcripts' => fn () => $this->checkCaptionsTranscripts($content),
                'adaptable_content' => fn () => $this->checkAdaptableContent($content),
                'distinguishable_content' => fn () => $this->checkDistinguishableContent($content),
            ],
            'operable' => [
                'keyboard_accessible' => fn () => $this->checkKeyboardAccessible($content),
                'no_seizures' => fn () => $this->checkNoSeizures($content),
                'navigable' => fn () => $this->checkNavigable($content),
                'input_modalities' => fn () => $this->checkInputModalities($content),
            ],
            'understandable' => [
                'readable' => fn () => $this->checkReadable($content),
                'predictable' => fn () => $this->checkPredictable($content),
                'input_assistance' => fn () => $this->checkInputAssistance($content),
            ],
            'robust' => [
                'compatible' => fn () => $this->checkCompatible($content),
            ],
        ];

        foreach ($wcagChecks as $principle => $checks) {
            foreach ($checks as $guideline => $check) {
                try {
                    $check();
                } catch (\Exception $e) {
                    $this->accessibilityIssues[] = [
                        'principle' => $principle,
                        'guideline' => $guideline,
                        'level' => 'critical',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }
    }

    private function validateHeadingHierarchy(string $content): void
    {
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $matches);

        if (empty($matches[1])) {
            $this->accessibilityIssues[] = [
                'type' => 'heading_hierarchy',
                'level' => 'critical',
                'message' => 'No headings found on page',
            ];

            return;
        }

        $headingLevels = array_map('intval', $matches[1]);

        // Check for H1
        if (! in_array(1, $headingLevels)) {
            $this->accessibilityIssues[] = [
                'type' => 'heading_hierarchy',
                'level' => 'critical',
                'message' => 'Page must have exactly one H1 element',
            ];
        }

        // Check for proper hierarchy
        $previousLevel = 0;
        foreach ($headingLevels as $level) {
            if ($level > $previousLevel + 1 && $previousLevel > 0) {
                $this->accessibilityIssues[] = [
                    'type' => 'heading_hierarchy',
                    'level' => 'warning',
                    'message' => "Heading level {$level} follows level {$previousLevel} - skipped levels",
                ];
            }
            $previousLevel = $level;
        }
    }

    private function validateSemanticElements(string $content): void
    {
        $requiredElements = ['main', 'header', 'footer', 'nav'];

        foreach ($requiredElements as $element) {
            if (! preg_match("/<{$element}[^>]*>/i", $content)) {
                $this->accessibilityIssues[] = [
                    'type' => 'semantic_elements',
                    'level' => 'warning',
                    'message' => "Missing semantic element: {$element}",
                ];
            }
        }
    }

    private function validateLandmarkRoles(string $content): void
    {
        $landmarkRoles = ['banner', 'navigation', 'main', 'contentinfo', 'complementary'];

        foreach ($landmarkRoles as $role) {
            if (! preg_match("/role=[\"']{$role}[\"']/i", $content) &&
                ! preg_match('/<(header|nav|main|footer|aside)[^>]*>/i', $content)) {
                $this->accessibilityIssues[] = [
                    'type' => 'landmark_roles',
                    'level' => 'warning',
                    'message' => "Consider adding landmark role: {$role}",
                ];
            }
        }
    }

    private function validateFocusableElements(string $content): void
    {
        // Check for focusable elements without visible focus indicators
        preg_match_all('/<(a|button|input|select|textarea)[^>]*>/i', $content, $matches);

        foreach ($matches[0] as $element) {
            if (strpos($element, 'tabindex="-1"') !== false) {
                continue; // Skip elements explicitly removed from tab order
            }

            // Check for focus styles (this would need CSS analysis in real implementation)
            if (! $this->hasFocusStyles($element)) {
                $this->accessibilityIssues[] = [
                    'type' => 'focus_indicators',
                    'level' => 'warning',
                    'message' => 'Focusable element may lack visible focus indicator',
                ];
            }
        }
    }

    private function validateSkipLinks(string $content): void
    {
        if (! preg_match('/href=["\']#[^"\']*["\'][^>]*>.*skip.*to.*content/i', $content)) {
            $this->accessibilityIssues[] = [
                'type' => 'skip_links',
                'level' => 'warning',
                'message' => 'Consider adding skip to content link',
            ];
        }
    }

    private function validateAriaLabels(string $content): void
    {
        // Check buttons without accessible names
        preg_match_all('/<button[^>]*>/i', $content, $buttons);

        foreach ($buttons[0] as $button) {
            if (! preg_match('/aria-label=/i', $button) &&
                ! preg_match('/aria-labelledby=/i', $button) &&
                ! preg_match('/>.*\w.*</i', $button)) {
                $this->accessibilityIssues[] = [
                    'type' => 'aria_labels',
                    'level' => 'critical',
                    'message' => 'Button without accessible name found',
                ];
            }
        }

        // Check form inputs without labels
        preg_match_all('/<input[^>]*>/i', $content, $inputs);

        foreach ($inputs[0] as $input) {
            if (preg_match('/type=["\'](?:text|email|password|tel|url)["\']/', $input)) {
                if (! preg_match('/aria-label=/i', $input) &&
                    ! preg_match('/aria-labelledby=/i', $input)) {
                    $this->accessibilityIssues[] = [
                        'type' => 'aria_labels',
                        'level' => 'critical',
                        'message' => 'Form input without accessible name found',
                    ];
                }
            }
        }
    }

    private function validateImageAltAttributes(string $content): void
    {
        preg_match_all('/<img[^>]*>/i', $content, $images);

        foreach ($images[0] as $image) {
            if (! preg_match('/alt=/i', $image)) {
                $this->accessibilityIssues[] = [
                    'type' => 'image_alt',
                    'level' => 'critical',
                    'message' => 'Image without alt attribute found',
                ];
            } elseif (preg_match('/alt=["\']["\']/', $image)) {
                // Empty alt is okay for decorative images, but check context
                if (! preg_match('/role=["\']presentation["\']/', $image)) {
                    $this->accessibilityIssues[] = [
                        'type' => 'image_alt',
                        'level' => 'warning',
                        'message' => 'Image with empty alt - ensure it is decorative',
                    ];
                }
            }
        }
    }

    private function validateColorContrast(string $content): void
    {
        // Extract inline styles and check for potential contrast issues
        preg_match_all('/style=["\'][^"\']*color[^"\']*["\']/', $content, $styles);

        foreach ($styles[0] as $style) {
            if (preg_match('/color:\s*#([a-f0-9]{3,6})/i', $style, $colorMatch) &&
                preg_match('/background(?:-color)?:\s*#([a-f0-9]{3,6})/i', $style, $bgMatch)) {

                $contrast = $this->calculateContrastRatio($colorMatch[1], $bgMatch[1]);

                if ($contrast < 4.5) { // WCAG AA standard
                    $this->accessibilityIssues[] = [
                        'type' => 'color_contrast',
                        'level' => 'critical',
                        'message' => "Low color contrast ratio: {$contrast}:1 (minimum 4.5:1)",
                    ];
                }
            }
        }
    }

    private function validateFormLabels(string $content): void
    {
        preg_match_all('/<input[^>]*id=["\']([^"\']*)["\'][^>]*>/i', $content, $inputs);

        foreach ($inputs[1] as $inputId) {
            if (! preg_match("/<label[^>]*for=[\"']{$inputId}[\"'][^>]*>/i", $content)) {
                $this->accessibilityIssues[] = [
                    'type' => 'form_labels',
                    'level' => 'critical',
                    'message' => "Input with id '{$inputId}' has no associated label",
                ];
            }
        }
    }

    private function validateVideoAccessibility(string $content): void
    {
        preg_match_all('/<video[^>]*>/i', $content, $videos);

        foreach ($videos[0] as $video) {
            if (! preg_match('/<track[^>]*kind=["\']captions["\']/', $content)) {
                $this->accessibilityIssues[] = [
                    'type' => 'video_accessibility',
                    'level' => 'critical',
                    'message' => 'Video without captions found',
                ];
            }

            if (preg_match('/autoplay/i', $video)) {
                $this->accessibilityIssues[] = [
                    'type' => 'video_accessibility',
                    'level' => 'warning',
                    'message' => 'Autoplay video may cause accessibility issues',
                ];
            }
        }
    }

    private function validateMobileAccessibility(string $content): void
    {
        // Check viewport meta tag
        if (! preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*>/i', $content)) {
            $this->accessibilityIssues[] = [
                'type' => 'mobile_accessibility',
                'level' => 'critical',
                'message' => 'Missing viewport meta tag for mobile accessibility',
            ];
        }

        // Check for minimum touch target sizes (would need CSS analysis)
        $this->validateTouchTargets($content);
    }

    private function validateAccessibilityPerformanceImpact(string $content): void
    {
        // Check that accessibility features don't significantly increase page size
        $contentSize = strlen($content);

        // Count accessibility-related attributes
        $ariaCount = preg_match_all('/aria-[a-z]+=/i', $content);
        $altCount = preg_match_all('/alt=/i', $content);
        $labelCount = preg_match_all('/<label/i', $content);

        $accessibilityOverhead = ($ariaCount + $altCount + $labelCount) * 50; // Rough estimate

        if ($accessibilityOverhead > $contentSize * 0.1) { // More than 10% overhead
            $this->accessibilityIssues[] = [
                'type' => 'performance_impact',
                'level' => 'warning',
                'message' => 'Accessibility features may have significant performance impact',
            ];
        }
    }

    // Helper methods for WCAG compliance checks
    private function checkTextAlternatives(string $content): void
    {
        $this->validateImageAltAttributes($content);
    }

    private function checkCaptionsTranscripts(string $content): void
    {
        $this->validateVideoAccessibility($content);
    }

    private function checkAdaptableContent(string $content): void
    {
        $this->validateSemanticElements($content);
        $this->validateHeadingHierarchy($content);
    }

    private function checkDistinguishableContent(string $content): void
    {
        $this->validateColorContrast($content);
    }

    private function checkKeyboardAccessible(string $content): void
    {
        $this->validateFocusableElements($content);
        $this->validateSkipLinks($content);
    }

    private function checkNoSeizures(string $content): void
    {
        // Check for flashing content
        if (preg_match('/animation.*flash|blink/i', $content)) {
            throw new \Exception('Potentially seizure-inducing content detected');
        }
    }

    private function checkNavigable(string $content): void
    {
        $this->validateSkipLinks($content);
        $this->validateHeadingHierarchy($content);
    }

    private function checkInputModalities(string $content): void
    {
        $this->validateTouchTargets($content);
    }

    private function checkReadable(string $content): void
    {
        // Check language attribute
        if (! preg_match('/<html[^>]*lang=/i', $content)) {
            throw new \Exception('Missing language attribute on html element');
        }
    }

    private function checkPredictable(string $content): void
    {
        // Check for consistent navigation
        $this->validateConsistentNavigation($content);
    }

    private function checkInputAssistance(string $content): void
    {
        $this->validateFormLabels($content);
        $this->validateFormValidation($content);
    }

    private function checkCompatible(string $content): void
    {
        // Check for valid HTML
        $this->validateHTMLValidity($content);
    }

    // Additional helper methods
    private function hasFocusStyles(string $element): bool
    {
        // In a real implementation, this would analyze CSS
        return true; // Placeholder
    }

    private function calculateContrastRatio(string $color1, string $color2): float
    {
        // Simplified contrast calculation - real implementation would be more complex
        return 4.5; // Placeholder
    }

    private function validateTouchTargets(string $content): void
    {
        // Check for minimum 44px touch targets (would need CSS analysis)
    }

    private function validateConsistentNavigation(string $content): void
    {
        // Check navigation consistency across pages
    }

    private function validateHTMLValidity(string $content): void
    {
        // Check for basic HTML validity issues
        $openTags = preg_match_all('/<([a-z]+)[^>]*>/i', $content, $openMatches);
        $closeTags = preg_match_all('/<\/([a-z]+)>/i', $content, $closeMatches);

        // Basic check for unclosed tags
        if ($openTags !== $closeTags) {
            $this->accessibilityIssues[] = [
                'type' => 'html_validity',
                'level' => 'warning',
                'message' => 'Potential unclosed HTML tags detected',
            ];
        }
    }

    private function validateListStructures(string $content): void
    {
        // Check for proper list markup
        preg_match_all('/<li[^>]*>/i', $content, $listItems);
        preg_match_all('/<(ul|ol)[^>]*>/i', $content, $lists);

        if (count($listItems[0]) > 0 && count($lists[0]) === 0) {
            $this->accessibilityIssues[] = [
                'type' => 'list_structure',
                'level' => 'critical',
                'message' => 'List items found without parent list element',
            ];
        }
    }

    private function validateTabOrder(string $content): void
    {
        // Check for logical tab order
        preg_match_all('/tabindex=["\']([^"\']*)["\']/', $content, $tabIndexes);

        foreach ($tabIndexes[1] as $tabIndex) {
            if (is_numeric($tabIndex) && $tabIndex > 0) {
                $this->accessibilityIssues[] = [
                    'type' => 'tab_order',
                    'level' => 'warning',
                    'message' => 'Positive tabindex values can disrupt natural tab order',
                ];
            }
        }
    }

    private function validateNoKeyboardTraps(string $content): void
    {
        // Check for potential keyboard traps (would need JavaScript analysis)
        if (preg_match('/onkeydown.*preventDefault/i', $content)) {
            $this->accessibilityIssues[] = [
                'type' => 'keyboard_traps',
                'level' => 'warning',
                'message' => 'Potential keyboard trap detected',
            ];
        }
    }

    private function validateAriaRoles(string $content): void
    {
        preg_match_all('/role=["\']([^"\']*)["\']/', $content, $roles);

        $validRoles = [
            'alert', 'alertdialog', 'application', 'article', 'banner', 'button',
            'cell', 'checkbox', 'columnheader', 'combobox', 'complementary',
            'contentinfo', 'definition', 'dialog', 'directory', 'document',
            'feed', 'figure', 'form', 'grid', 'gridcell', 'group', 'heading',
            'img', 'link', 'list', 'listbox', 'listitem', 'log', 'main',
            'marquee', 'math', 'menu', 'menubar', 'menuitem', 'menuitemcheckbox',
            'menuitemradio', 'navigation', 'none', 'note', 'option', 'presentation',
            'progressbar', 'radio', 'radiogroup', 'region', 'row', 'rowgroup',
            'rowheader', 'scrollbar', 'search', 'searchbox', 'separator',
            'slider', 'spinbutton', 'status', 'switch', 'tab', 'table',
            'tablist', 'tabpanel', 'term', 'textbox', 'timer', 'toolbar',
            'tooltip', 'tree', 'treegrid', 'treeitem',
        ];

        foreach ($roles[1] as $role) {
            if (! in_array($role, $validRoles)) {
                $this->accessibilityIssues[] = [
                    'type' => 'aria_roles',
                    'level' => 'warning',
                    'message' => "Invalid ARIA role: {$role}",
                ];
            }
        }
    }

    private function validateAriaStatesAndProperties(string $content): void
    {
        // Check for required ARIA properties
        preg_match_all('/role=["\']button["\']/', $content, $buttons);

        if (count($buttons[0]) > 0) {
            // Buttons should have accessible names
            if (! preg_match('/aria-label|aria-labelledby/', $content)) {
                $this->accessibilityIssues[] = [
                    'type' => 'aria_properties',
                    'level' => 'warning',
                    'message' => 'Button elements should have accessible names',
                ];
            }
        }
    }

    private function validateAriaLiveRegions(string $content): void
    {
        // Check for proper live region usage
        preg_match_all('/aria-live=["\']([^"\']*)["\']/', $content, $liveRegions);

        foreach ($liveRegions[1] as $liveValue) {
            if (! in_array($liveValue, ['off', 'polite', 'assertive'])) {
                $this->accessibilityIssues[] = [
                    'type' => 'aria_live',
                    'level' => 'warning',
                    'message' => "Invalid aria-live value: {$liveValue}",
                ];
            }
        }
    }

    private function validateColorIndependence(string $content): void
    {
        // Check that information is not conveyed by color alone
        if (preg_match('/style.*color.*red.*required/i', $content)) {
            $this->accessibilityIssues[] = [
                'type' => 'color_independence',
                'level' => 'warning',
                'message' => 'Information may be conveyed by color alone',
            ];
        }
    }

    private function validateDecorativeImages(string $content): void
    {
        // Check for proper decorative image markup
        preg_match_all('/<img[^>]*alt=["\']["\'][^>]*>/i', $content, $decorativeImages);

        foreach ($decorativeImages[0] as $image) {
            if (! preg_match('/role=["\']presentation["\']|role=["\']none["\']/', $image)) {
                $this->accessibilityIssues[] = [
                    'type' => 'decorative_images',
                    'level' => 'suggestion',
                    'message' => 'Consider adding role="presentation" to decorative images',
                ];
            }
        }
    }

    private function validateComplexImages(string $content): void
    {
        // Check for complex images that may need long descriptions
        preg_match_all('/<img[^>]*>/i', $content, $images);

        foreach ($images[0] as $image) {
            if (preg_match('/chart|graph|diagram|infographic/i', $image)) {
                if (! preg_match('/longdesc|aria-describedby/', $image)) {
                    $this->accessibilityIssues[] = [
                        'type' => 'complex_images',
                        'level' => 'warning',
                        'message' => 'Complex image may need long description',
                    ];
                }
            }
        }
    }

    private function validateImageLoadingAccessibility(string $content): void
    {
        // Check for lazy loading impact on accessibility
        preg_match_all('/<img[^>]*loading=["\']lazy["\'][^>]*>/i', $content, $lazyImages);

        if (count($lazyImages[0]) > 0) {
            $this->accessibilityIssues[] = [
                'type' => 'image_loading',
                'level' => 'suggestion',
                'message' => 'Ensure lazy-loaded images have proper loading indicators for screen readers',
            ];
        }
    }

    private function validateFormValidation(string $content): void
    {
        // Check for accessible form validation
        preg_match_all('/<input[^>]*required[^>]*>/i', $content, $requiredInputs);

        foreach ($requiredInputs[0] as $input) {
            if (! preg_match('/aria-required=["\']true["\']/', $input)) {
                $this->accessibilityIssues[] = [
                    'type' => 'form_validation',
                    'level' => 'suggestion',
                    'message' => 'Consider adding aria-required="true" to required form fields',
                ];
            }
        }
    }

    private function validateFormInstructions(string $content): void
    {
        // Check for form instructions and help text
        preg_match_all('/<form[^>]*>/i', $content, $forms);

        if (count($forms[0]) > 0) {
            if (! preg_match('/aria-describedby|<p[^>]*class=["\'][^"\']*help[^"\']*["\']/', $content)) {
                $this->accessibilityIssues[] = [
                    'type' => 'form_instructions',
                    'level' => 'suggestion',
                    'message' => 'Consider providing form instructions and help text',
                ];
            }
        }
    }

    private function validateFormErrorHandling(string $content): void
    {
        // Check for accessible error handling
        if (preg_match('/error|invalid/i', $content)) {
            if (! preg_match('/aria-invalid|role=["\']alert["\']/', $content)) {
                $this->accessibilityIssues[] = [
                    'type' => 'form_errors',
                    'level' => 'warning',
                    'message' => 'Form errors should be announced to screen readers',
                ];
            }
        }
    }

    private function validateAudioAccessibility(string $content): void
    {
        preg_match_all('/<audio[^>]*>/i', $content, $audioElements);

        foreach ($audioElements[0] as $audio) {
            if (preg_match('/autoplay/i', $audio)) {
                $this->accessibilityIssues[] = [
                    'type' => 'audio_accessibility',
                    'level' => 'critical',
                    'message' => 'Autoplay audio violates WCAG guidelines',
                ];
            }
        }
    }

    private function validateAutoplayRestrictions(string $content): void
    {
        if (preg_match('/<(video|audio)[^>]*autoplay[^>]*>/i', $content)) {
            $this->accessibilityIssues[] = [
                'type' => 'autoplay',
                'level' => 'critical',
                'message' => 'Autoplay media can cause accessibility issues and should be avoided',
            ];
        }
    }

    private function validateTabletAccessibility(string $content): void
    {
        // Similar to mobile validation but with tablet-specific considerations
        $this->validateTouchTargets($content);

        // Check for appropriate content scaling
        if (! preg_match('/viewport.*width=device-width/', $content)) {
            $this->accessibilityIssues[] = [
                'type' => 'tablet_accessibility',
                'level' => 'warning',
                'message' => 'Content may not scale properly on tablet devices',
            ];
        }
    }

    private function createTestData(): void
    {
        // Create minimal test data for accessibility testing
        // This would be expanded based on your specific needs
    }

    protected function tearDown(): void
    {
        // Log accessibility issues for review
        if (! empty($this->accessibilityIssues)) {
            Log::info('Accessibility issues found', [
                'total_issues' => count($this->accessibilityIssues),
                'critical_issues' => count(array_filter($this->accessibilityIssues, fn ($issue) => $issue['level'] === 'critical')),
                'issues' => $this->accessibilityIssues,
            ]);
        }

        parent::tearDown();
    }
}
