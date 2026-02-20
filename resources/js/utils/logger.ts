/**
 * logger.ts
 *
 * A lightweight logging utility that emits output only in development.
 * In production (import.meta.env.PROD === true) every method is a no-op,
 * so no internal state or debug information leaks to the browser console.
 *
 * Usage:
 *   import { logger } from '@/Utils/logger';
 *   logger.log('Loaded component', props);
 *   logger.warn('Unexpected value', value);
 *   logger.error('Request failed', error);
 */

const isDev = import.meta.env.DEV;

/* eslint-disable no-console */
export const logger = {
    log: isDev
        ? (...args: unknown[]) => console.log('[app]', ...args)
        : () => undefined,

    info: isDev
        ? (...args: unknown[]) => console.info('[app]', ...args)
        : () => undefined,

    warn: isDev
        ? (...args: unknown[]) => console.warn('[app]', ...args)
        : () => undefined,

    error: isDev
        ? (...args: unknown[]) => console.error('[app]', ...args)
        : () => undefined,

    debug: isDev
        ? (...args: unknown[]) => console.debug('[app]', ...args)
        : () => undefined,

    /**
     * Group related log entries (dev only).
     * Automatically calls console.groupCollapsed so groups are collapsed by default.
     */
    group: isDev
        ? (label: string, fn: () => void) => {
              console.groupCollapsed(`[app] ${label}`);
              fn();
              console.groupEnd();
          }
        : (_label: string, _fn: () => void) => undefined,
} as const;
/* eslint-enable no-console */
