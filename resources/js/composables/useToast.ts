import { ref, reactive } from 'vue';

export interface Toast {
    id: string;
    title?: string;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
    duration?: number;
    action?: {
        label: string;
        onClick: () => void;
    };
}

const toasts = ref<Toast[]>([]);

const generateId = () => Date.now().toString(36) + Math.random().toString(36).substr(2);

export const useToast = () => {
    const toast = (options: Omit<Toast, 'id'>) => {
        const id = generateId();
        const newToast: Toast = {
            id,
            duration: 5000,
            ...options,
        };

        toasts.value.push(newToast);

        if (newToast.duration && newToast.duration > 0) {
            setTimeout(() => {
                remove(id);
            }, newToast.duration);
        }

        return id;
    };

    const success = (message: string, title?: string) => {
        return toast({ type: 'success', message, title });
    };

    const error = (message: string, title?: string) => {
        return toast({ type: 'error', message, title });
    };

    const warning = (message: string, title?: string) => {
        return toast({ type: 'warning', message, title });
    };

    const info = (message: string, title?: string) => {
        return toast({ type: 'info', message, title });
    };

    const remove = (id: string) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index > -1) {
            toasts.value.splice(index, 1);
        }
    };

    const clear = () => {
        toasts.value = [];
    };

    return {
        toasts: toasts.value,
        toast,
        success,
        error,
        warning,
        info,
        remove,
        clear,
    };
};
