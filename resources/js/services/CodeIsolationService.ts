/**
 * Code Isolation Service
 *
 * Provides secure execution environment for custom code to prevent
 * interference with the main application and other components.
 */

export interface IsolationConfig {
    allowedGlobals: string[];
    allowedAPIs: string[];
    timeoutMs: number;
    memoryLimitMB: number;
    sandboxed: boolean;
}

export interface ExecutionResult {
    success: boolean;
    result?: any;
    error?: string;
    warnings: string[];
    executionTime: number;
    memoryUsed: number;
}

export interface IsolatedEnvironment {
    id: string;
    config: IsolationConfig;
    globals: Record<string, any>;
    cleanup: () => void;
}

export class CodeIsolationService {
    private static instance: CodeIsolationService;
    private environments: Map<string, IsolatedEnvironment> = new Map();
    private defaultConfig: IsolationConfig = {
        allowedGlobals: ['console', 'Math', 'Date', 'JSON', 'Object', 'Array'],
        allowedAPIs: ['fetch', 'localStorage', 'sessionStorage'],
        timeoutMs: 5000,
        memoryLimitMB: 10,
        sandboxed: true,
    };

    private constructor() {}

    static getInstance(): CodeIsolationService {
        if (!CodeIsolationService.instance) {
            CodeIsolationService.instance = new CodeIsolationService();
        }
        return CodeIsolationService.instance;
    }

    /**
     * Create an isolated execution environment
     */
    createEnvironment(config: Partial<IsolationConfig> = {}): IsolatedEnvironment {
        const envId = this.generateEnvironmentId();
        const envConfig = { ...this.defaultConfig, ...config };

        // Create sandboxed globals
        const globals = this.createSandboxedGlobals(envConfig);

        const environment: IsolatedEnvironment = {
            id: envId,
            config: envConfig,
            globals,
            cleanup: () => this.cleanupEnvironment(envId),
        };

        this.environments.set(envId, environment);

        console.log(`Created isolated environment ${envId}`);
        return environment;
    }

    /**
     * Execute HTML code in isolation
     */
    async executeHTML(code: string, environment: IsolatedEnvironment, containerElement?: HTMLElement): Promise<ExecutionResult> {
        const startTime = performance.now();
        const warnings: string[] = [];

        try {
            // Sanitize HTML code
            const sanitizedCode = this.sanitizeHTML(code, warnings);

            if (containerElement) {
                // Create isolated container
                const isolatedContainer = this.createIsolatedContainer(containerElement);

                // Set innerHTML with sanitized code
                isolatedContainer.innerHTML = sanitizedCode;

                // Apply component isolation styles
                this.applyIsolationStyles(isolatedContainer);

                const executionTime = performance.now() - startTime;

                return {
                    success: true,
                    result: isolatedContainer,
                    warnings,
                    executionTime,
                    memoryUsed: this.estimateMemoryUsage(sanitizedCode),
                };
            } else {
                // Return sanitized HTML for preview
                const executionTime = performance.now() - startTime;

                return {
                    success: true,
                    result: sanitizedCode,
                    warnings,
                    executionTime,
                    memoryUsed: this.estimateMemoryUsage(sanitizedCode),
                };
            }
        } catch (error) {
            const executionTime = performance.now() - startTime;

            return {
                success: false,
                error: error instanceof Error ? error.message : 'Unknown error',
                warnings,
                executionTime,
                memoryUsed: 0,
            };
        }
    }

    /**
     * Execute CSS code in isolation
     */
    async executeCSS(code: string, environment: IsolatedEnvironment, scope?: string): Promise<ExecutionResult> {
        const startTime = performance.now();
        const warnings: string[] = [];

        try {
            // Sanitize CSS code
            const sanitizedCode = this.sanitizeCSS(code, warnings);

            // Scope CSS to prevent global interference
            const scopedCode = scope ? this.scopeCSS(sanitizedCode, scope) : sanitizedCode;

            // Create isolated style element
            const styleElement = document.createElement('style');
            styleElement.textContent = scopedCode;
            styleElement.setAttribute('data-isolation-id', environment.id);

            // Add to document head
            document.head.appendChild(styleElement);

            const executionTime = performance.now() - startTime;

            return {
                success: true,
                result: styleElement,
                warnings,
                executionTime,
                memoryUsed: this.estimateMemoryUsage(scopedCode),
            };
        } catch (error) {
            const executionTime = performance.now() - startTime;

            return {
                success: false,
                error: error instanceof Error ? error.message : 'Unknown error',
                warnings,
                executionTime,
                memoryUsed: 0,
            };
        }
    }

    /**
     * Execute JavaScript code in isolation
     */
    async executeJavaScript(code: string, environment: IsolatedEnvironment, context?: Record<string, any>): Promise<ExecutionResult> {
        const startTime = performance.now();
        const warnings: string[] = [];

        try {
            // Create execution timeout
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Execution timeout')), environment.config.timeoutMs);
            });

            // Create execution promise
            const executionPromise = this.executeJavaScriptSafely(code, environment, context, warnings);

            // Race between execution and timeout
            const result = await Promise.race([executionPromise, timeoutPromise]);

            const executionTime = performance.now() - startTime;

            return {
                success: true,
                result,
                warnings,
                executionTime,
                memoryUsed: this.estimateMemoryUsage(code),
            };
        } catch (error) {
            const executionTime = performance.now() - startTime;

            return {
                success: false,
                error: error instanceof Error ? error.message : 'Unknown error',
                warnings,
                executionTime,
                memoryUsed: 0,
            };
        }
    }

    /**
     * Sanitize HTML code
     */
    private sanitizeHTML(code: string, warnings: string[]): string {
        let sanitized = code;

        // Remove script tags
        const scriptMatches = sanitized.match(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi);
        if (scriptMatches) {
            warnings.push(`Removed ${scriptMatches.length} script tag(s) for security`);
            sanitized = sanitized.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        }

        // Remove event handlers
        const eventHandlers = sanitized.match(/on\w+\s*=\s*["'][^"']*["']/gi);
        if (eventHandlers) {
            warnings.push(`Removed ${eventHandlers.length} event handler(s) for security`);
            sanitized = sanitized.replace(/on\w+\s*=\s*["'][^"']*["']/gi, '');
        }

        // Remove javascript: URLs
        const jsUrls = sanitized.match(/javascript:/gi);
        if (jsUrls) {
            warnings.push(`Removed ${jsUrls.length} javascript: URL(s) for security`);
            sanitized = sanitized.replace(/javascript:/gi, '#');
        }

        // Remove potentially dangerous elements
        const dangerousElements = ['object', 'embed', 'applet', 'iframe'];
        dangerousElements.forEach((element) => {
            const regex = new RegExp(`<${element}\\b[^>]*>.*?<\\/${element}>`, 'gi');
            const matches = sanitized.match(regex);
            if (matches) {
                warnings.push(`Removed ${matches.length} ${element} element(s) for security`);
                sanitized = sanitized.replace(regex, '');
            }
        });

        return sanitized;
    }

    /**
     * Sanitize CSS code
     */
    private sanitizeCSS(code: string, warnings: string[]): string {
        let sanitized = code;

        // Remove CSS expressions
        const expressions = sanitized.match(/expression\s*\([^)]*\)/gi);
        if (expressions) {
            warnings.push(`Removed ${expressions.length} CSS expression(s) for security`);
            sanitized = sanitized.replace(/expression\s*\([^)]*\)/gi, '');
        }

        // Remove javascript: URLs in CSS
        const jsUrls = sanitized.match(/javascript:/gi);
        if (jsUrls) {
            warnings.push(`Removed ${jsUrls.length} javascript: URL(s) from CSS for security`);
            sanitized = sanitized.replace(/javascript:/gi, '');
        }

        // Remove @import with javascript:
        const jsImports = sanitized.match(/@import\s+url\s*\(\s*["']?javascript:/gi);
        if (jsImports) {
            warnings.push(`Removed ${jsImports.length} dangerous @import(s) for security`);
            sanitized = sanitized.replace(/@import\s+url\s*\(\s*["']?javascript:[^)]*\)/gi, '');
        }

        return sanitized;
    }

    /**
     * Scope CSS to prevent global interference
     */
    private scopeCSS(code: string, scope: string): string {
        // Simple CSS scoping - prepend scope to selectors
        return code.replace(/([^{}]+)\s*{/g, (match, selector) => {
            // Skip @rules and keyframes
            if (selector.trim().startsWith('@')) {
                return match;
            }

            // Add scope prefix to each selector
            const scopedSelector = selector
                .split(',')
                .map((s) => `${scope} ${s.trim()}`)
                .join(', ');

            return `${scopedSelector} {`;
        });
    }

    /**
     * Execute JavaScript safely
     */
    private async executeJavaScriptSafely(
        code: string,
        environment: IsolatedEnvironment,
        context: Record<string, any> = {},
        warnings: string[],
    ): Promise<any> {
        // Create restricted global context
        const restrictedGlobals = this.createRestrictedGlobals(environment, warnings);

        // Combine with provided context
        const executionContext = { ...restrictedGlobals, ...context };

        // Create function with restricted scope
        const paramNames = Object.keys(executionContext);
        const paramValues = Object.values(executionContext);

        // Wrap code in try-catch for better error handling
        const wrappedCode = `
      "use strict";
      try {
        ${code}
      } catch (error) {
        throw new Error('Runtime Error: ' + error.message);
      }
    `;

        // Create and execute function
        const func = new Function(...paramNames, wrappedCode);
        return func(...paramValues);
    }

    /**
     * Create sandboxed globals
     */
    private createSandboxedGlobals(config: IsolationConfig): Record<string, any> {
        const globals: Record<string, any> = {};

        // Add allowed globals
        config.allowedGlobals.forEach((globalName) => {
            if (globalName in window) {
                globals[globalName] = (window as any)[globalName];
            }
        });

        // Add restricted APIs
        config.allowedAPIs.forEach((apiName) => {
            if (apiName in window) {
                globals[apiName] = this.createRestrictedAPI(apiName, (window as any)[apiName]);
            }
        });

        return globals;
    }

    /**
     * Create restricted globals for JavaScript execution
     */
    private createRestrictedGlobals(environment: IsolatedEnvironment, warnings: string[]): Record<string, any> {
        const globals: Record<string, any> = {};

        // Add safe globals
        environment.config.allowedGlobals.forEach((globalName) => {
            if (globalName === 'console') {
                globals.console = this.createRestrictedConsole();
            } else if (globalName in window) {
                globals[globalName] = (window as any)[globalName];
            }
        });

        // Add document with restrictions
        globals.document = this.createRestrictedDocument(warnings);

        // Add window with restrictions
        globals.window = this.createRestrictedWindow(environment, warnings);

        return globals;
    }

    /**
     * Create restricted console
     */
    private createRestrictedConsole(): Console {
        return {
            log: (...args: any[]) => console.log('[Isolated]', ...args),
            error: (...args: any[]) => console.error('[Isolated]', ...args),
            warn: (...args: any[]) => console.warn('[Isolated]', ...args),
            info: (...args: any[]) => console.info('[Isolated]', ...args),
            debug: (...args: any[]) => console.debug('[Isolated]', ...args),
            trace: (...args: any[]) => console.trace('[Isolated]', ...args),
            group: (...args: any[]) => console.group('[Isolated]', ...args),
            groupEnd: () => console.groupEnd(),
            time: (label?: string) => console.time(`[Isolated] ${label}`),
            timeEnd: (label?: string) => console.timeEnd(`[Isolated] ${label}`),
            clear: () => {}, // Prevent clearing main console
            count: (label?: string) => console.count(`[Isolated] ${label}`),
            countReset: (label?: string) => console.countReset(`[Isolated] ${label}`),
            table: (data?: any) => console.table(data),
            dir: (obj: any) => console.dir(obj),
            dirxml: (...data: any[]) => console.dirxml(...data),
            assert: (condition?: boolean, ...data: any[]) => console.assert(condition, '[Isolated]', ...data),
            profile: () => {}, // Disabled
            profileEnd: () => {}, // Disabled
            timeStamp: () => {}, // Disabled
        } as Console;
    }

    /**
     * Create restricted document
     */
    private createRestrictedDocument(warnings: string[]): Partial<Document> {
        return {
            getElementById: (id: string) => {
                const element = document.getElementById(id);
                if (element && element.closest('[data-isolation-scope]')) {
                    return element;
                }
                warnings.push(`Access to element '${id}' outside isolation scope denied`);
                return null;
            },
            querySelector: (selector: string) => {
                const scopedSelector = '[data-isolation-scope] ' + selector;
                return document.querySelector(scopedSelector);
            },
            querySelectorAll: (selector: string) => {
                const scopedSelector = '[data-isolation-scope] ' + selector;
                return document.querySelectorAll(scopedSelector);
            },
            createElement: (tagName: string) => {
                const element = document.createElement(tagName);
                element.setAttribute('data-isolated', 'true');
                return element;
            },
            addEventListener: () => {
                warnings.push('Global event listeners not allowed in isolated code');
            },
            removeEventListener: () => {
                warnings.push('Global event listener removal not allowed in isolated code');
            },
        };
    }

    /**
     * Create restricted window
     */
    private createRestrictedWindow(environment: IsolatedEnvironment, warnings: string[]): Partial<Window> {
        return {
            alert: (message?: any) => {
                warnings.push('Alert dialogs are restricted in isolated code');
                console.log('[Isolated Alert]', message);
            },
            confirm: () => {
                warnings.push('Confirm dialogs are restricted in isolated code');
                return false;
            },
            prompt: () => {
                warnings.push('Prompt dialogs are restricted in isolated code');
                return null;
            },
            open: () => {
                warnings.push('Window.open is restricted in isolated code');
                return null;
            },
            close: () => {
                warnings.push('Window.close is restricted in isolated code');
            },
            location: {
                href: window.location.href,
                origin: window.location.origin,
                pathname: window.location.pathname,
                search: window.location.search,
                hash: window.location.hash,
            } as Location,
            setTimeout: (handler: Function, timeout?: number) => {
                if (timeout && timeout > environment.config.timeoutMs) {
                    warnings.push(`Timeout reduced from ${timeout}ms to ${environment.config.timeoutMs}ms`);
                    timeout = environment.config.timeoutMs;
                }
                return window.setTimeout(handler, timeout);
            },
            setInterval: (handler: Function, timeout?: number) => {
                if (timeout && timeout < 100) {
                    warnings.push('Interval timeout increased to 100ms minimum');
                    timeout = 100;
                }
                return window.setInterval(handler, timeout);
            },
            clearTimeout: window.clearTimeout.bind(window),
            clearInterval: window.clearInterval.bind(window),
        };
    }

    /**
     * Create restricted API
     */
    private createRestrictedAPI(apiName: string, originalAPI: any): any {
        switch (apiName) {
            case 'fetch':
                return this.createRestrictedFetch(originalAPI);
            case 'localStorage':
                return this.createRestrictedStorage(originalAPI, 'isolated-');
            case 'sessionStorage':
                return this.createRestrictedStorage(originalAPI, 'isolated-');
            default:
                return originalAPI;
        }
    }

    /**
     * Create restricted fetch
     */
    private createRestrictedFetch(originalFetch: typeof fetch): typeof fetch {
        return async (input: RequestInfo | URL, init?: RequestInit) => {
            // Only allow same-origin requests
            const url = typeof input === 'string' ? input : input instanceof URL ? input.href : input.url;

            if (!url.startsWith('/') && !url.startsWith(window.location.origin)) {
                throw new Error('Cross-origin requests not allowed in isolated code');
            }

            return originalFetch(input, init);
        };
    }

    /**
     * Create restricted storage
     */
    private createRestrictedStorage(originalStorage: Storage, prefix: string): Storage {
        return {
            getItem: (key: string) => originalStorage.getItem(prefix + key),
            setItem: (key: string, value: string) => originalStorage.setItem(prefix + key, value),
            removeItem: (key: string) => originalStorage.removeItem(prefix + key),
            clear: () => {
                // Only clear prefixed items
                const keys = Object.keys(originalStorage).filter((k) => k.startsWith(prefix));
                keys.forEach((key) => originalStorage.removeItem(key));
            },
            key: (index: number) => {
                const keys = Object.keys(originalStorage).filter((k) => k.startsWith(prefix));
                const key = keys[index];
                return key ? key.substring(prefix.length) : null;
            },
            get length() {
                return Object.keys(originalStorage).filter((k) => k.startsWith(prefix)).length;
            },
        };
    }

    /**
     * Create isolated container
     */
    private createIsolatedContainer(parentElement: HTMLElement): HTMLElement {
        const container = document.createElement('div');
        container.setAttribute('data-isolation-scope', 'true');
        container.className = 'isolated-code-container';

        parentElement.appendChild(container);
        return container;
    }

    /**
     * Apply isolation styles
     */
    private applyIsolationStyles(container: HTMLElement): void {
        // Apply CSS isolation
        container.style.isolation = 'isolate';
        container.style.position = 'relative';
        container.style.zIndex = '1';

        // Prevent overflow from affecting parent
        container.style.overflow = 'hidden';
        container.style.maxWidth = '100%';
        container.style.maxHeight = '100%';
    }

    /**
     * Cleanup environment
     */
    private cleanupEnvironment(envId: string): void {
        const environment = this.environments.get(envId);
        if (!environment) return;

        // Remove isolated style elements
        const styleElements = document.querySelectorAll(`style[data-isolation-id="${envId}"]`);
        styleElements.forEach((element) => element.remove());

        // Remove isolated containers
        const containers = document.querySelectorAll(`[data-isolation-scope="${envId}"]`);
        containers.forEach((container) => container.remove());

        // Clear environment
        this.environments.delete(envId);

        console.log(`Cleaned up isolated environment ${envId}`);
    }

    /**
     * Cleanup all environments
     */
    cleanupAllEnvironments(): void {
        Array.from(this.environments.keys()).forEach((envId) => {
            this.cleanupEnvironment(envId);
        });
    }

    /**
     * Estimate memory usage
     */
    private estimateMemoryUsage(code: string): number {
        // Simple estimation based on code length
        return new Blob([code]).size;
    }

    /**
     * Generate environment ID
     */
    private generateEnvironmentId(): string {
        return 'env-' + Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
}

// Export singleton instance
export const codeIsolationService = CodeIsolationService.getInstance();
