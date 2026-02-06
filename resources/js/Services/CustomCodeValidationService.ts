/**
 * Custom Code Validation Service
 *
 * Provides comprehensive validation for custom HTML, CSS, and JavaScript code
 * with security scanning, syntax checking, and performance analysis.
 */

export interface ValidationResult {
    isValid: boolean;
    errors: ValidationError[];
    warnings: ValidationWarning[];
    info: ValidationInfo[];
    securityIssues: SecurityIssue[];
    performanceIssues: PerformanceIssue[];
}

export interface ValidationError {
    line: number;
    column: number;
    message: string;
    code: string;
    severity: 'error';
    ruleId?: string;
}

export interface ValidationWarning {
    line: number;
    column: number;
    message: string;
    code: string;
    severity: 'warning';
    ruleId?: string;
}

export interface ValidationInfo {
    line: number;
    column: number;
    message: string;
    code: string;
    severity: 'info';
    ruleId?: string;
}

export interface SecurityIssue {
    severity: 'low' | 'medium' | 'high' | 'critical';
    message: string;
    line?: number;
    column?: number;
    remediation?: string;
    cwe?: string;
}

export interface PerformanceIssue {
    type: 'memory' | 'cpu' | 'network' | 'rendering';
    message: string;
    severity: 'low' | 'medium' | 'high';
    line?: number;
    column?: number;
    recommendation?: string;
}

export type CodeType = 'html' | 'css' | 'javascript';

export class CustomCodeValidationService {
    private static instance: CustomCodeValidationService;

    private constructor() {}

    static getInstance(): CustomCodeValidationService {
        if (!CustomCodeValidationService.instance) {
            CustomCodeValidationService.instance = new CustomCodeValidationService();
        }
        return CustomCodeValidationService.instance;
    }

    /**
     * Validate custom code based on type
     */
    async validateCode(code: string, type: CodeType): Promise<ValidationResult> {
        const result: ValidationResult = {
            isValid: true,
            errors: [],
            warnings: [],
            info: [],
            securityIssues: [],
            performanceIssues: [],
        };

        try {
            // Basic syntax validation
            const syntaxResult = await this.validateSyntax(code, type);
            result.errors.push(...syntaxResult.errors);
            result.warnings.push(...syntaxResult.warnings);

            // Security scanning
            const securityResult = await this.scanSecurity(code, type);
            result.securityIssues.push(...securityResult);

            // Performance analysis
            const performanceResult = await this.analyzePerformance(code, type);
            result.performanceIssues.push(...performanceResult);

            // Update overall validity
            result.isValid = result.errors.length === 0;
        } catch (error) {
            console.error('Validation error:', error);
            result.errors.push({
                line: 1,
                column: 1,
                message: 'Validation failed due to internal error',
                code: 'VALIDATION_ERROR',
                severity: 'error',
            });
            result.isValid = false;
        }

        return result;
    }

    /**
     * Validate code syntax
     */
    private async validateSyntax(
        code: string,
        type: CodeType,
    ): Promise<{
        errors: ValidationError[];
        warnings: ValidationWarning[];
    }> {
        const errors: ValidationError[] = [];
        const warnings: ValidationWarning[] = [];

        switch (type) {
            case 'html':
                const htmlResult = this.validateHTML(code);
                errors.push(...htmlResult.errors);
                warnings.push(...htmlResult.warnings);
                break;

            case 'css':
                const cssResult = this.validateCSS(code);
                errors.push(...cssResult.errors);
                warnings.push(...cssResult.warnings);
                break;

            case 'javascript':
                const jsResult = this.validateJavaScript(code);
                errors.push(...jsResult.errors);
                warnings.push(...jsResult.warnings);
                break;
        }

        return { errors, warnings };
    }

    /**
     * Validate HTML syntax and structure
     */
    private validateHTML(code: string): {
        errors: ValidationError[];
        warnings: ValidationWarning[];
    } {
        const errors: ValidationError[] = [];
        const warnings: ValidationWarning[] = [];
        const lines = code.split('\n');

        // Track tag stack for proper nesting
        const tagStack: Array<{ tag: string; line: number; column: number }> = [];

        lines.forEach((line, lineIndex) => {
            const lineNumber = lineIndex + 1;
            const trimmedLine = line.trim();

            // Skip empty lines and comments
            if (!trimmedLine || trimmedLine.startsWith('<!--')) return;

            // Find HTML tags
            const tagRegex = /<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/g;
            let match;

            while ((match = tagRegex.exec(line)) !== null) {
                const tag = match[1].toLowerCase();
                const isClosing = match[0].startsWith('</');
                const column = match.index + 1;

                if (isClosing) {
                    // Closing tag
                    if (tagStack.length === 0) {
                        errors.push({
                            line: lineNumber,
                            column,
                            message: `Unexpected closing tag </${tag}>`,
                            code: 'UNEXPECTED_CLOSING_TAG',
                            severity: 'error',
                        });
                    } else {
                        const lastTag = tagStack.pop();
                        if (lastTag && lastTag.tag !== tag) {
                            errors.push({
                                line: lineNumber,
                                column,
                                message: `Mismatched closing tag </${tag}>, expected </${lastTag.tag}>`,
                                code: 'MISMATCHED_TAG',
                                severity: 'error',
                            });
                        }
                    }
                } else {
                    // Opening tag
                    // Self-closing tags don't need to be pushed to stack
                    if (!match[0].includes('/>') && !this.isSelfClosingTag(tag)) {
                        tagStack.push({ tag, line: lineNumber, column });
                    }
                }
            }

            // Check for unclosed attributes
            const attrRegex = /<[^>]*\s+([a-zA-Z][a-zA-Z0-9-]*)(?:\s*=\s*["'][^"']*["'])?/g;
            while ((match = attrRegex.exec(line)) !== null) {
                const attr = match[1];
                const attrValue = match[0].substring(match[0].indexOf(attr) + attr.length);

                if (attrValue.includes('=') && !attrValue.includes('"') && !attrValue.includes("'")) {
                    warnings.push({
                        line: lineNumber,
                        column: match.index + match[0].indexOf(attr) + 1,
                        message: `Attribute "${attr}" is missing quotes`,
                        code: 'UNQUOTED_ATTRIBUTE',
                        severity: 'warning',
                    });
                }
            }
        });

        // Check for unclosed tags
        tagStack.forEach((unclosed) => {
            errors.push({
                line: unclosed.line,
                column: unclosed.column,
                message: `Unclosed tag <${unclosed.tag}>`,
                code: 'UNCLOSED_TAG',
                severity: 'error',
            });
        });

        return { errors, warnings };
    }

    /**
     * Check if tag is self-closing
     */
    private isSelfClosingTag(tag: string): boolean {
        const selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
        return selfClosingTags.includes(tag);
    }

    /**
     * Validate CSS syntax and best practices
     */
    private validateCSS(code: string): {
        errors: ValidationError[];
        warnings: ValidationWarning[];
    } {
        const errors: ValidationError[] = [];
        const warnings: ValidationWarning[] = [];
        const lines = code.split('\n');

        lines.forEach((line, lineIndex) => {
            const lineNumber = lineIndex + 1;
            const trimmedLine = line.trim();

            // Skip empty lines and comments
            if (!trimmedLine || trimmedLine.startsWith('/*') || trimmedLine.startsWith('//')) return;

            // Check for missing semicolons
            if (
                trimmedLine.includes(':') &&
                !trimmedLine.endsWith(';') &&
                !trimmedLine.endsWith('{') &&
                !trimmedLine.endsWith('}') &&
                !trimmedLine.startsWith('@')
            ) {
                const colonIndex = line.indexOf(':');
                if (colonIndex > 0) {
                    const afterColon = line.substring(colonIndex);
                    if (!afterColon.includes(';')) {
                        warnings.push({
                            line: lineNumber,
                            column: line.length,
                            message: 'Missing semicolon after CSS property value',
                            code: 'MISSING_SEMICOLON',
                            severity: 'warning',
                        });
                    }
                }
            }

            // Check for invalid CSS properties
            const propertyRegex = /([a-zA-Z-]+)\s*:/g;
            let match;
            while ((match = propertyRegex.exec(line)) !== null) {
                const property = match[1];
                if (!this.isValidCSSProperty(property)) {
                    warnings.push({
                        line: lineNumber,
                        column: match.index + 1,
                        message: `Unknown CSS property "${property}"`,
                        code: 'UNKNOWN_PROPERTY',
                        severity: 'warning',
                    });
                }
            }
        });

        return { errors, warnings };
    }

    /**
     * Check if CSS property is valid
     */
    private isValidCSSProperty(property: string): boolean {
        const validProperties = [
            'align-content',
            'align-items',
            'align-self',
            'all',
            'animation',
            'animation-delay',
            'animation-direction',
            'animation-duration',
            'animation-fill-mode',
            'animation-iteration-count',
            'animation-name',
            'animation-play-state',
            'animation-timing-function',
            'backface-visibility',
            'background',
            'background-attachment',
            'background-blend-mode',
            'background-clip',
            'background-color',
            'background-image',
            'background-origin',
            'background-position',
            'background-repeat',
            'background-size',
            'border',
            'border-bottom',
            'border-bottom-color',
            'border-bottom-left-radius',
            'border-bottom-right-radius',
            'border-bottom-style',
            'border-bottom-width',
            'border-collapse',
            'border-color',
            'border-image',
            'border-image-outset',
            'border-image-repeat',
            'border-image-slice',
            'border-image-source',
            'border-image-width',
            'border-left',
            'border-left-color',
            'border-left-style',
            'border-left-width',
            'border-radius',
            'border-right',
            'border-right-color',
            'border-right-style',
            'border-right-width',
            'border-spacing',
            'border-style',
            'border-top',
            'border-top-color',
            'border-top-left-radius',
            'border-top-right-radius',
            'border-top-style',
            'border-top-width',
            'border-width',
            'bottom',
            'box-decoration-break',
            'box-shadow',
            'box-sizing',
            'break-after',
            'break-before',
            'break-inside',
            'caption-side',
            'caret-color',
            'clear',
            'clip',
            'clip-path',
            'color',
            'column-count',
            'column-fill',
            'column-gap',
            'column-rule',
            'column-rule-color',
            'column-rule-style',
            'column-rule-width',
            'column-span',
            'column-width',
            'columns',
            'content',
            'counter-increment',
            'counter-reset',
            'cursor',
            'direction',
            'display',
            'empty-cells',
            'filter',
            'flex',
            'flex-basis',
            'flex-direction',
            'flex-flow',
            'flex-grow',
            'flex-shrink',
            'flex-wrap',
            'float',
            'font',
            'font-family',
            'font-feature-settings',
            'font-kerning',
            'font-language-override',
            'font-size',
            'font-size-adjust',
            'font-stretch',
            'font-style',
            'font-synthesis',
            'font-variant',
            'font-variant-alternates',
            'font-variant-caps',
            'font-variant-east-asian',
            'font-variant-ligatures',
            'font-variant-numeric',
            'font-variant-position',
            'font-weight',
            'gap',
            'grid',
            'grid-area',
            'grid-auto-columns',
            'grid-auto-flow',
            'grid-auto-rows',
            'grid-column',
            'grid-column-end',
            'grid-column-gap',
            'grid-column-start',
            'grid-gap',
            'grid-row',
            'grid-row-end',
            'grid-row-gap',
            'grid-row-start',
            'grid-template',
            'grid-template-areas',
            'grid-template-columns',
            'grid-template-rows',
            'hanging-punctuation',
            'height',
            'hyphens',
            'image-rendering',
            'isolation',
            'justify-content',
            'justify-items',
            'justify-self',
            'left',
            'letter-spacing',
            'line-break',
            'line-height',
            'list-style',
            'list-style-image',
            'list-style-position',
            'list-style-type',
            'margin',
            'margin-bottom',
            'margin-left',
            'margin-right',
            'margin-top',
            'mask',
            'mask-clip',
            'mask-composite',
            'mask-image',
            'mask-mode',
            'mask-origin',
            'mask-position',
            'mask-repeat',
            'mask-size',
            'mask-type',
            'max-height',
            'max-width',
            'min-height',
            'min-width',
            'mix-blend-mode',
            'object-fit',
            'object-position',
            'opacity',
            'order',
            'orphans',
            'outline',
            'outline-color',
            'outline-offset',
            'outline-style',
            'outline-width',
            'overflow',
            'overflow-wrap',
            'overflow-x',
            'overflow-y',
            'padding',
            'padding-bottom',
            'padding-left',
            'padding-right',
            'padding-top',
            'page-break-after',
            'page-break-before',
            'page-break-inside',
            'perspective',
            'perspective-origin',
            'pointer-events',
            'position',
            'quotes',
            'resize',
            'right',
            'row-gap',
            'scroll-behavior',
            'shape-image-threshold',
            'shape-margin',
            'shape-outside',
            'tab-size',
            'table-layout',
            'text-align',
            'text-align-last',
            'text-combine-upright',
            'text-decoration',
            'text-decoration-color',
            'text-decoration-line',
            'text-decoration-style',
            'text-decoration-thickness',
            'text-emphasis',
            'text-emphasis-color',
            'text-emphasis-position',
            'text-emphasis-style',
            'text-indent',
            'text-justify',
            'text-orientation',
            'text-overflow',
            'text-rendering',
            'text-shadow',
            'text-transform',
            'text-underline-offset',
            'text-underline-position',
            'top',
            'transform',
            'transform-origin',
            'transform-style',
            'transition',
            'transition-delay',
            'transition-duration',
            'transition-property',
            'transition-timing-function',
            'unicode-bidi',
            'user-select',
            'vertical-align',
            'visibility',
            'white-space',
            'widows',
            'width',
            'will-change',
            'word-break',
            'word-spacing',
            'word-wrap',
            'writing-mode',
            'z-index',
        ];

        return validProperties.includes(property);
    }

    /**
     * Validate JavaScript syntax and best practices
     */
    private validateJavaScript(code: string): {
        errors: ValidationError[];
        warnings: ValidationWarning[];
    } {
        const errors: ValidationError[] = [];
        const warnings: ValidationWarning[] = [];
        const lines = code.split('\n');

        lines.forEach((line, lineIndex) => {
            const lineNumber = lineIndex + 1;
            const trimmedLine = line.trim();

            // Skip empty lines and comments
            if (!trimmedLine || trimmedLine.startsWith('//') || trimmedLine.startsWith('/*')) return;

            // Check for missing semicolons
            if (
                trimmedLine &&
                !trimmedLine.endsWith(';') &&
                !trimmedLine.endsWith('{') &&
                !trimmedLine.endsWith('}') &&
                !trimmedLine.endsWith(',') &&
                !trimmedLine.startsWith('if') &&
                !trimmedLine.startsWith('for') &&
                !trimmedLine.startsWith('while') &&
                !trimmedLine.startsWith('function') &&
                !trimmedLine.startsWith('class') &&
                !trimmedLine.includes('console.log(')
            ) {
                warnings.push({
                    line: lineNumber,
                    column: line.length,
                    message: 'Missing semicolon',
                    code: 'MISSING_SEMICOLON',
                    severity: 'warning',
                });
            }

            // Check for potential issues
            if (trimmedLine.includes('eval(')) {
                warnings.push({
                    line: lineNumber,
                    column: line.indexOf('eval(') + 1,
                    message: 'Use of eval() is potentially unsafe',
                    code: 'EVAL_USAGE',
                    severity: 'warning',
                });
            }

            if (trimmedLine.includes('innerHTML')) {
                warnings.push({
                    line: lineNumber,
                    column: line.indexOf('innerHTML') + 1,
                    message: 'Direct innerHTML manipulation can be unsafe',
                    code: 'INNERHTML_USAGE',
                    severity: 'warning',
                });
            }
        });

        return { errors, warnings };
    }

    /**
     * Scan code for security vulnerabilities
     */
    private async scanSecurity(code: string, type: CodeType): Promise<SecurityIssue[]> {
        const issues: SecurityIssue[] = [];

        // XSS vulnerabilities
        if (type === 'html' || type === 'javascript') {
            if (code.includes('innerHTML') || code.includes('outerHTML')) {
                issues.push({
                    severity: 'medium',
                    message: 'Potential XSS vulnerability through innerHTML/outerHTML manipulation',
                    remediation: 'Use textContent or createElement/appendChild instead',
                });
            }

            if (code.includes('eval(') || code.includes('Function(')) {
                issues.push({
                    severity: 'high',
                    message: 'Use of eval() or Function() constructor can execute malicious code',
                    remediation: 'Avoid using eval() and Function() constructor',
                });
            }

            if (code.includes('document.write') || code.includes('document.writeln')) {
                issues.push({
                    severity: 'medium',
                    message: 'document.write can be exploited for XSS attacks',
                    remediation: 'Use modern DOM manipulation methods',
                });
            }
        }

        // CSS injection
        if (type === 'css') {
            if (code.includes('javascript:') || code.includes('vbscript:')) {
                issues.push({
                    severity: 'high',
                    message: 'CSS expression or javascript: URLs can be exploited',
                    remediation: 'Avoid CSS expressions and javascript: URLs',
                });
            }
        }

        return issues;
    }

    /**
     * Analyze code for performance issues
     */
    private async analyzePerformance(code: string, type: CodeType): Promise<PerformanceIssue[]> {
        const issues: PerformanceIssue[] = [];

        if (type === 'javascript') {
            // Check for inefficient patterns
            if (code.includes('document.getElementById') && code.includes('for')) {
                issues.push({
                    type: 'rendering',
                    message: 'DOM queries inside loops can cause performance issues',
                    severity: 'medium',
                    recommendation: 'Cache DOM queries outside of loops',
                });
            }

            if (code.includes('setTimeout') || code.includes('setInterval')) {
                issues.push({
                    type: 'cpu',
                    message: 'Timers can cause memory leaks if not properly cleared',
                    severity: 'low',
                    recommendation: 'Ensure timers are cleared when no longer needed',
                });
            }
        }

        if (type === 'css') {
            // Check for expensive CSS selectors
            if (code.includes('*') || code.includes('[class*=""]')) {
                issues.push({
                    type: 'rendering',
                    message: 'Universal selectors and complex attribute selectors can be slow',
                    severity: 'low',
                    recommendation: 'Use more specific selectors when possible',
                });
            }
        }

        return issues;
    }

    /**
     * Lint code for style and consistency issues
     */
    async lintCode(
        code: string,
        type: CodeType,
    ): Promise<{
        isValid: boolean;
        issues: Array<ValidationError | ValidationWarning | ValidationInfo>;
        fixedCode?: string;
    }> {
        const result = await this.validateCode(code, type);
        const issues = [...result.errors, ...result.warnings, ...result.info];

        return {
            isValid: result.isValid,
            issues,
            fixedCode: this.attemptAutoFix(code, type, issues),
        };
    }

    /**
     * Attempt to auto-fix common issues
     */
    private attemptAutoFix(code: string, type: CodeType, issues: Array<ValidationError | ValidationWarning | ValidationInfo>): string {
        let fixedCode = code;

        issues.forEach((issue) => {
            if (issue.code === 'MISSING_SEMICOLON' && issue.severity === 'warning') {
                // Add semicolon at end of line
                const lines = fixedCode.split('\n');
                if (lines[issue.line - 1] && !lines[issue.line - 1].trim().endsWith(';')) {
                    lines[issue.line - 1] = lines[issue.line - 1].trimEnd() + ';';
                    fixedCode = lines.join('\n');
                }
            }
        });

        return fixedCode;
    }

    /**
     * Analyze code complexity and maintainability
     */
    async analyzeCode(
        code: string,
        type: CodeType,
    ): Promise<{
        complexity: number;
        maintainability: number;
        securityIssues: SecurityIssue[];
        performanceIssues: PerformanceIssue[];
        suggestions: string[];
    }> {
        const securityIssues = await this.scanSecurity(code, type);
        const performanceIssues = await this.analyzePerformance(code, type);

        let complexity = 1;
        let maintainability = 100;

        if (type === 'javascript') {
            // Calculate cyclomatic complexity
            const functionMatches = code.match(/function\s+\w+\s*\(/g) || [];
            const ifMatches = code.match(/\bif\s*\(/g) || [];
            const loopMatches = code.match(/\b(for|while|do)\s*\(/g) || [];

            complexity = 1 + functionMatches.length + ifMatches.length + loopMatches.length;

            // Adjust maintainability based on complexity
            if (complexity > 10) {
                maintainability -= 20;
            } else if (complexity > 5) {
                maintainability -= 10;
            }

            // Adjust for code length
            if (code.length > 1000) {
                maintainability -= 10;
            }
        }

        const suggestions: string[] = [];

        if (complexity > 10) {
            suggestions.push('Consider breaking down complex functions into smaller, more manageable pieces');
        }

        if (securityIssues.length > 0) {
            suggestions.push('Review and fix security issues before deployment');
        }

        if (performanceIssues.length > 0) {
            suggestions.push('Address performance issues to improve user experience');
        }

        return {
            complexity,
            maintainability: Math.max(0, maintainability),
            securityIssues,
            performanceIssues,
            suggestions,
        };
    }
}

// Export singleton instance
export const customCodeValidationService = CustomCodeValidationService.getInstance();
