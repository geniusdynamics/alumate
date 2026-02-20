import { useToast as useVueToastification } from 'vue-toastification';

/**
 * Thin wrapper around vue-toastification that provides a consistent,
 * app-wide toast notification API.
 *
 * Usage:
 *   import { useAppToast } from '@/Composables/useAppToast';
 *   const toast = useAppToast();
 *   toast.success('Saved!');
 *   toast.error('Something went wrong.');
 *   toast.info('Loading…');
 *   toast.warning('Check your input.');
 */
export function useAppToast() {
    const toast = useVueToastification();

    return {
        success: (message: string) => toast.success(message),
        error: (message: string) => toast.error(message),
        info: (message: string) => toast.info(message),
        warning: (message: string) => toast.warning(message),
    };
}
