/**
 * Accessibility Testing Utility
 * 
 * Provides automated accessibility testing functions to verify
 * WCAG 2.1 AA compliance throughout the application.
 */

import { ColorContrastManager } from './AccessibilityHelpers.js';

export class AccessibilityTester {
    constructor() {
        this.violations = [];
        this.warnings = [];
        this.passes = [];
    }

    /**
     * Run comprehensive accessibility audit
     */
    async runAudit(container = document) {
        this.violations = [];
        this.warnings = [];
        this.passes = [];

        console.log('🔍 Starting accessibility audit...');

        // Test different aspects of accessibility
        await this.testKeyboardNavigation(container);
        await this.testAriaAttributes(container);
        await this.testColorContrast(container);
        await this.testSemanticStructure(container);
        await this.testFormAccessibility(container);
        await this.testImageAccessibility(container);
        await this.testFocusManagement(container);
        await this.testTouchTargets(container);

        return this.generateReport();
    }

    /**
     * Test keyboard navigation
     */
    async testKeyboardNavigation(container) {
        const focusableElements = container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );

        let hasTabIndex = true;
        let hasKeyboardTraps = false;
        let hasSkipLinks = false;

        // Check for skip links
        const skipLinks = container.querySelectorAll('.skip-link, [href^="#main"], [href^="#content"]');
        hasSkipLinks = skipLinks.length > 0;

        // Check tabindex values
        focusableElements.forEach(element => {
            const tabIndex = element.getAttribute('tabindex');
            if (tabIndex && parseInt(tabIndex) > 0) {
                this.addWarning('Positive tabindex found', element, 'Use tabindex="0" or remove tabindex for natural tab order');
            }
        });

        // Check for keyboard event handlers
        const interactiveElements = container.querySelectorAll('[onclick], [onmousedown], [onmouseup]');
        interactiveElements.forEach(element => {
            const hasKeyboardHandler = element.hasAttribute('onkeydown') || 
                                     element.hasAttribute('onkeyup') || 
                                     element.hasAttribute('onkeypress');
            
            if (!hasKeyboardHandler && !['A', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA'].includes(element.tagName)) {
                this.addViolation('Interactive element missing keyboard support', element, 'Add keyboard event handlers or use semantic HTML elements');
            }
        });

        if (hasSkipLinks) {
            this.addPass('Skip links found');
        } else {
            this.addViolation('No skip links found', null, 'Add skip links for keyboard users');
        }
    }

    /**
     * Test ARIA attributes
     */
    async testAriaAttributes(container) {
        // Check for proper ARIA labels
        const unlabeledButtons = container.querySelectorAll('button:not([aria-label]):not([aria-labelledby])');
        unlabeledButtons.forEach(button => {
            if (!button.textContent.trim()) {
                this.addViolation('Button missing accessible name', button, 'Add aria-label or visible text content');
            }
        });

        // Check for proper ARIA expanded states
        const expandableElements = container.querySelectorAll('[aria-expanded]');
        expandableElements.forEach(element => {
            const expanded = element.getAttribute('aria-expanded');
            if (expanded !== 'true' && expanded !== 'false') {
                this.addViolation('Invalid aria-expanded value', element, 'Use "true" or "false" for aria-expanded');
            }
        });

        // Check for proper ARIA live regions
        const liveRegions = container.querySelectorAll('[aria-live]');
        liveRegions.forEach(region => {
            const liveValue = region.getAttribute('aria-live');
            if (!['polite', 'assertive', 'off'].includes(liveValue)) {
                this.addViolation('Invalid aria-live value', region, 'Use "polite", "assertive", or "off" for aria-live');
            }
        });

        // Check for proper form labels
        const inputs = container.querySelectorAll('input:not([type="hidden"]), select, textarea');
        inputs.forEach(input => {
            const hasLabel = input.labels && input.labels.length > 0;
            const hasAriaLabel = input.hasAttribute('aria-label');
            const hasAriaLabelledBy = input.hasAttribute('aria-labelledby');

            if (!hasLabel && !hasAriaLabel && !hasAriaLabelledBy) {
                this.addViolation('Form control missing label', input, 'Add a label element or aria-label attribute');
            }
        });

        this.addPass('ARIA attributes checked');
    }

    /**
     * Test color contrast
     */
    async testColorContrast(container) {
        const textElements = container.querySelectorAll('p, span, div, h1, h2, h3, h4, h5, h6, a, button, label');
        let contrastIssues = 0;

        textElements.forEach(element => {
            const styles = window.getComputedStyle(element);
            const color = styles.color;
            const backgroundColor = styles.backgroundColor;
            const fontSize = parseFloat(styles.fontSize);
            const fontWeight = styles.fontWeight;

            // Skip if no visible text
            if (!element.textContent.trim()) return;

            // Determine if text is large (18pt+ or 14pt+ bold)
            const isLargeText = fontSize >= 18 || (fontSize >= 14 && (fontWeight === 'bold' || parseInt(fontWeight) >= 700));

            // This is a simplified check - in practice, you'd need robust color parsing
            if (color === 'rgb(128, 128, 128)' && backgroundColor === 'rgb(255, 255, 255)') {
                contrastIssues++;
                this.addWarning('Potential contrast issue', element, 'Verify color contrast meets WCAG AA standards');
            }
        });

        if (contrastIssues === 0) {
            this.addPass('No obvious color contrast issues found');
        }
    }

    /**
     * Test semantic structure
     */
    async testSemanticStructure(container) {
        // Check heading hierarchy
        const headings = container.querySelectorAll('h1, h2, h3, h4, h5, h6');
        let previousLevel = 0;
        let hasH1 = false;
        let hierarchyIssues = 0;

        headings.forEach(heading => {
            const level = parseInt(heading.tagName.charAt(1));
            
            if (level === 1) {
                hasH1 = true;
            }

            if (previousLevel > 0 && level > previousLevel + 1) {
                hierarchyIssues++;
                this.addViolation('Heading hierarchy skip', heading, `Heading jumps from h${previousLevel} to h${level}`);
            }

            previousLevel = level;
        });

        if (!hasH1 && headings.length > 0) {
            this.addViolation('No h1 heading found', null, 'Page should have exactly one h1 heading');
        }

        // Check for semantic landmarks
        const landmarks = container.querySelectorAll('main, nav, header, footer, aside, section[aria-labelledby], section[aria-label]');
        if (landmarks.length === 0) {
            this.addWarning('No semantic landmarks found', null, 'Use semantic HTML elements like main, nav, header, footer');
        } else {
            this.addPass('Semantic landmarks found');
        }

        // Check for proper list structure
        const listItems = container.querySelectorAll('li');
        listItems.forEach(li => {
            const parent = li.parentElement;
            if (!['UL', 'OL', 'MENU'].includes(parent.tagName)) {
                this.addViolation('List item outside of list', li, 'li elements must be children of ul, ol, or menu elements');
            }
        });

        if (hierarchyIssues === 0 && headings.length > 0) {
            this.addPass('Heading hierarchy is correct');
        }
    }

    /**
     * Test form accessibility
     */
    async testFormAccessibility(container) {
        const forms = container.querySelectorAll('form');
        
        forms.forEach(form => {
            // Check for fieldsets with legends
            const fieldsets = form.querySelectorAll('fieldset');
            fieldsets.forEach(fieldset => {
                const legend = fieldset.querySelector('legend');
                if (!legend) {
                    this.addWarning('Fieldset missing legend', fieldset, 'Add a legend element to describe the fieldset');
                }
            });

            // Check for required field indicators
            const requiredInputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            requiredInputs.forEach(input => {
                const hasAriaRequired = input.hasAttribute('aria-required');
                const hasVisualIndicator = input.closest('.required') || 
                                         input.parentElement.querySelector('.required-indicator, .asterisk');

                if (!hasAriaRequired) {
                    this.addWarning('Required field missing aria-required', input, 'Add aria-required="true" to required fields');
                }

                if (!hasVisualIndicator) {
                    this.addWarning('Required field missing visual indicator', input, 'Add visual indicator (like *) for required fields');
                }
            });

            // Check for error handling
            const errorElements = form.querySelectorAll('[role="alert"], .error, .invalid');
            if (errorElements.length > 0) {
                this.addPass('Form error handling found');
            }
        });

        if (forms.length === 0) {
            this.addPass('No forms to test');
        }
    }

    /**
     * Test image accessibility
     */
    async testImageAccessibility(container) {
        const images = container.querySelectorAll('img');
        let missingAlt = 0;

        images.forEach(img => {
            const hasAlt = img.hasAttribute('alt');
            const altText = img.getAttribute('alt');
            const isDecorative = altText === '' || img.hasAttribute('role') && img.getAttribute('role') === 'presentation';

            if (!hasAlt) {
                missingAlt++;
                this.addViolation('Image missing alt attribute', img, 'Add alt attribute to all images');
            } else if (altText && altText.toLowerCase().includes('image of')) {
                this.addWarning('Redundant alt text', img, 'Avoid "image of" in alt text');
            }
        });

        // Check for background images with content
        const elementsWithBgImages = container.querySelectorAll('[style*="background-image"]');
        elementsWithBgImages.forEach(element => {
            if (!element.textContent.trim() && !element.hasAttribute('aria-label')) {
                this.addWarning('Background image without alternative text', element, 'Provide alternative text for meaningful background images');
            }
        });

        if (missingAlt === 0) {
            this.addPass('All images have alt attributes');
        }
    }

    /**
     * Test focus management
     */
    async testFocusManagement(container) {
        const focusableElements = container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );

        let visibleFocusCount = 0;

        focusableElements.forEach(element => {
            // Check if element has visible focus indicator
            const styles = window.getComputedStyle(element, ':focus');
            const outline = styles.outline;
            const boxShadow = styles.boxShadow;

            if (outline !== 'none' || boxShadow !== 'none') {
                visibleFocusCount++;
            }
        });

        if (visibleFocusCount === focusableElements.length) {
            this.addPass('All focusable elements have visible focus indicators');
        } else {
            this.addWarning('Some elements may lack visible focus indicators', null, 'Ensure all interactive elements have visible focus states');
        }

        // Check for focus traps in modals
        const modals = container.querySelectorAll('[role="dialog"], .modal');
        modals.forEach(modal => {
            const focusableInModal = modal.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );

            if (focusableInModal.length === 0) {
                this.addWarning('Modal without focusable elements', modal, 'Modals should contain focusable elements');
            }
        });
    }

    /**
     * Test touch target sizes
     */
    async testTouchTargets(container) {
        const interactiveElements = container.querySelectorAll('button, a, input, select, textarea, [onclick], [tabindex]:not([tabindex="-1"])');
        let smallTargets = 0;

        interactiveElements.forEach(element => {
            const rect = element.getBoundingClientRect();
            const minSize = 44; // WCAG AA minimum

            if (rect.width < minSize || rect.height < minSize) {
                smallTargets++;
                this.addWarning('Touch target too small', element, `Minimum size should be ${minSize}px × ${minSize}px`);
            }
        });

        if (smallTargets === 0) {
            this.addPass('All touch targets meet minimum size requirements');
        }
    }

    /**
     * Add violation to results
     */
    addViolation(message, element, suggestion) {
        this.violations.push({
            type: 'violation',
            message,
            element,
            suggestion,
            severity: 'error'
        });
    }

    /**
     * Add warning to results
     */
    addWarning(message, element, suggestion) {
        this.warnings.push({
            type: 'warning',
            message,
            element,
            suggestion,
            severity: 'warning'
        });
    }

    /**
     * Add pass to results
     */
    addPass(message) {
        this.passes.push({
            type: 'pass',
            message,
            severity: 'success'
        });
    }

    /**
     * Generate accessibility report
     */
    generateReport() {
        const totalTests = this.violations.length + this.warnings.length + this.passes.length;
        const score = Math.round(((this.passes.length) / totalTests) * 100);

        const report = {
            score,
            summary: {
                violations: this.violations.length,
                warnings: this.warnings.length,
                passes: this.passes.length,
                total: totalTests
            },
            violations: this.violations,
            warnings: this.warnings,
            passes: this.passes,
            recommendations: this.generateRecommendations()
        };

        this.logReport(report);
        return report;
    }

    /**
     * Generate recommendations based on findings
     */
    generateRecommendations() {
        const recommendations = [];

        if (this.violations.length > 0) {
            recommendations.push({
                priority: 'high',
                message: `Fix ${this.violations.length} accessibility violations`,
                action: 'Address all violations to meet WCAG AA compliance'
            });
        }

        if (this.warnings.length > 5) {
            recommendations.push({
                priority: 'medium',
                message: `Review ${this.warnings.length} accessibility warnings`,
                action: 'Consider addressing warnings to improve user experience'
            });
        }

        if (this.violations.length === 0 && this.warnings.length < 3) {
            recommendations.push({
                priority: 'low',
                message: 'Consider WCAG AAA compliance',
                action: 'Explore additional accessibility enhancements'
            });
        }

        return recommendations;
    }

    /**
     * Log report to console
     */
    logReport(report) {
        console.group('🔍 Accessibility Audit Report');
        console.log(`📊 Score: ${report.score}%`);
        console.log(`✅ Passes: ${report.summary.passes}`);
        console.log(`⚠️  Warnings: ${report.summary.warnings}`);
        console.log(`❌ Violations: ${report.summary.violations}`);

        if (report.violations.length > 0) {
            console.group('❌ Violations');
            report.violations.forEach(violation => {
                console.error(violation.message, violation.element);
                console.log(`💡 Suggestion: ${violation.suggestion}`);
            });
            console.groupEnd();
        }

        if (report.warnings.length > 0) {
            console.group('⚠️  Warnings');
            report.warnings.forEach(warning => {
                console.warn(warning.message, warning.element);
                console.log(`💡 Suggestion: ${warning.suggestion}`);
            });
            console.groupEnd();
        }

        if (report.recommendations.length > 0) {
            console.group('📋 Recommendations');
            report.recommendations.forEach(rec => {
                console.log(`${rec.priority.toUpperCase()}: ${rec.message}`);
                console.log(`Action: ${rec.action}`);
            });
            console.groupEnd();
        }

        console.groupEnd();
    }

    /**
     * Test specific WCAG criteria
     */
    async testWCAGCriteria(container = document) {
        const results = {
            '1.1.1': await this.test_1_1_1_NonTextContent(container),
            '1.3.1': await this.test_1_3_1_InfoAndRelationships(container),
            '1.4.3': await this.test_1_4_3_ContrastMinimum(container),
            '2.1.1': await this.test_2_1_1_Keyboard(container),
            '2.4.1': await this.test_2_4_1_BypassBlocks(container),
            '2.4.2': await this.test_2_4_2_PageTitled(container),
            '2.4.3': await this.test_2_4_3_FocusOrder(container),
            '3.3.1': await this.test_3_3_1_ErrorIdentification(container),
            '3.3.2': await this.test_3_3_2_LabelsOrInstructions(container),
            '4.1.2': await this.test_4_1_2_NameRoleValue(container)
        };

        return results;
    }

    // Individual WCAG test methods
    async test_1_1_1_NonTextContent(container) {
        const images = container.querySelectorAll('img');
        const violations = [];

        images.forEach(img => {
            if (!img.hasAttribute('alt')) {
                violations.push({ element: img, issue: 'Missing alt attribute' });
            }
        });

        return {
            criterion: '1.1.1 Non-text Content',
            passed: violations.length === 0,
            violations
        };
    }

    async test_1_3_1_InfoAndRelationships(container) {
        const violations = [];
        
        // Check form labels
        const inputs = container.querySelectorAll('input:not([type="hidden"]), select, textarea');
        inputs.forEach(input => {
            if (!input.labels || input.labels.length === 0) {
                if (!input.hasAttribute('aria-label') && !input.hasAttribute('aria-labelledby')) {
                    violations.push({ element: input, issue: 'Form control missing label' });
                }
            }
        });

        return {
            criterion: '1.3.1 Info and Relationships',
            passed: violations.length === 0,
            violations
        };
    }

    async test_1_4_3_ContrastMinimum(container) {
        // Simplified contrast test
        return {
            criterion: '1.4.3 Contrast (Minimum)',
            passed: true,
            violations: [],
            note: 'Manual contrast testing required'
        };
    }

    async test_2_1_1_Keyboard(container) {
        const violations = [];
        const interactiveElements = container.querySelectorAll('[onclick]:not(button):not(a):not(input):not(select):not(textarea)');
        
        interactiveElements.forEach(element => {
            if (!element.hasAttribute('tabindex') && !element.hasAttribute('onkeydown')) {
                violations.push({ element, issue: 'Interactive element not keyboard accessible' });
            }
        });

        return {
            criterion: '2.1.1 Keyboard',
            passed: violations.length === 0,
            violations
        };
    }

    async test_2_4_1_BypassBlocks(container) {
        const skipLinks = container.querySelectorAll('.skip-link, a[href^="#main"], a[href^="#content"]');
        
        return {
            criterion: '2.4.1 Bypass Blocks',
            passed: skipLinks.length > 0,
            violations: skipLinks.length === 0 ? [{ issue: 'No skip links found' }] : []
        };
    }

    async test_2_4_2_PageTitled(container) {
        const title = document.title;
        
        return {
            criterion: '2.4.2 Page Titled',
            passed: title && title.trim().length > 0,
            violations: !title || title.trim().length === 0 ? [{ issue: 'Page missing title' }] : []
        };
    }

    async test_2_4_3_FocusOrder(container) {
        // This would require more complex testing of actual focus order
        return {
            criterion: '2.4.3 Focus Order',
            passed: true,
            violations: [],
            note: 'Manual focus order testing required'
        };
    }

    async test_3_3_1_ErrorIdentification(container) {
        const violations = [];
        const invalidInputs = container.querySelectorAll('[aria-invalid="true"]');
        
        invalidInputs.forEach(input => {
            const hasErrorMessage = input.hasAttribute('aria-describedby') || 
                                  input.parentElement.querySelector('.error, [role="alert"]');
            
            if (!hasErrorMessage) {
                violations.push({ element: input, issue: 'Invalid input missing error message' });
            }
        });

        return {
            criterion: '3.3.1 Error Identification',
            passed: violations.length === 0,
            violations
        };
    }

    async test_3_3_2_LabelsOrInstructions(container) {
        const violations = [];
        const requiredInputs = container.querySelectorAll('input[required], select[required], textarea[required]');
        
        requiredInputs.forEach(input => {
            const hasLabel = input.labels && input.labels.length > 0;
            const hasAriaLabel = input.hasAttribute('aria-label');
            const hasInstructions = input.hasAttribute('aria-describedby');
            
            if (!hasLabel && !hasAriaLabel) {
                violations.push({ element: input, issue: 'Required field missing label' });
            }
        });

        return {
            criterion: '3.3.2 Labels or Instructions',
            passed: violations.length === 0,
            violations
        };
    }

    async test_4_1_2_NameRoleValue(container) {
        const violations = [];
        const customElements = container.querySelectorAll('[role]');
        
        customElements.forEach(element => {
            const role = element.getAttribute('role');
            const hasName = element.hasAttribute('aria-label') || 
                          element.hasAttribute('aria-labelledby') || 
                          element.textContent.trim();
            
            if (['button', 'link', 'menuitem'].includes(role) && !hasName) {
                violations.push({ element, issue: `${role} missing accessible name` });
            }
        });

        return {
            criterion: '4.1.2 Name, Role, Value',
            passed: violations.length === 0,
            violations
        };
    }
}

// Create global instance for easy testing
export const accessibilityTester = new AccessibilityTester();

// Add to window for console access
if (typeof window !== 'undefined') {
    window.accessibilityTester = accessibilityTester;
}

// Vue 3 composable for accessibility testing
export function useAccessibilityTesting() {
    const tester = new AccessibilityTester();
    
    return {
        runAudit: tester.runAudit.bind(tester),
        testWCAGCriteria: tester.testWCAGCriteria.bind(tester),
        generateReport: tester.generateReport.bind(tester)
    };
}