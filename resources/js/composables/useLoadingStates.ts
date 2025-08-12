import { ref, computed, reactive } from 'vue'

export interface LoadingState {
    isLoading: boolean
    type: 'skeleton' | 'spinner' | 'contextual' | 'shimmer'
    context?: string
    message?: string
    progress?: number
    steps?: string[]
    currentStep?: number
    error?: string | null
}

export interface LoadingConfig {
    type?: 'skeleton' | 'spinner' | 'contextual' | 'shimmer'
    context?: string
    message?: string
    showProgress?: boolean
    steps?: string[]
}

/**
 * Composable for managing loading states with contextual feedback
 */
export function useLoadingStates() {
    // Global loading states
    const loadingStates = reactive<Record<string, LoadingState>>({})
    
    // Default loading state
    const defaultState: LoadingState = {
        isLoading: false,
        type: 'spinner',
        context: 'loading',
        message: 'Loading...',
        progress: 0,
        currentStep: 0,
        error: null
    }
    
    /**
     * Start a loading state
     */
    const startLoading = (
        key: string, 
        config: LoadingConfig = {}
    ): void => {
        loadingStates[key] = {
            ...defaultState,
            isLoading: true,
            type: config.type || 'spinner',
            context: config.context || 'loading',
            message: config.message || getContextualMessage(config.context || 'loading'),
            steps: config.steps || [],
            currentStep: 0,
            error: null
        }
    }
    
    /**
     * Update loading progress
     */
    const updateProgress = (
        key: string, 
        progress: number, 
        message?: string
    ): void => {
        if (loadingStates[key]) {
            loadingStates[key].progress = Math.max(0, Math.min(100, progress))
            if (message) {
                loadingStates[key].message = message
            }
        }
    }
    
    /**
     * Update current step
     */
    const updateStep = (
        key: string, 
        stepIndex: number, 
        message?: string
    ): void => {
        if (loadingStates[key] && loadingStates[key].steps) {
            loadingStates[key].currentStep = Math.max(0, Math.min(
                loadingStates[key].steps!.length - 1, 
                stepIndex
            ))
            if (message) {
                loadingStates[key].message = message
            }
        }
    }
    
    /**
     * Update loading message
     */
    const updateMessage = (key: string, message: string): void => {
        if (loadingStates[key]) {
            loadingStates[key].message = message
        }
    }
    
    /**
     * Set loading error
     */
    const setError = (key: string, error: string): void => {
        if (loadingStates[key]) {
            loadingStates[key].error = error
            loadingStates[key].isLoading = false
        }
    }
    
    /**
     * Stop loading state
     */
    const stopLoading = (key: string): void => {
        if (loadingStates[key]) {
            loadingStates[key].isLoading = false
            loadingStates[key].error = null
        }
    }
    
    /**
     * Clear loading state
     */
    const clearLoading = (key: string): void => {
        delete loadingStates[key]
    }
    
    /**
     * Get loading state
     */
    const getLoadingState = (key: string): LoadingState | null => {
        return loadingStates[key] || null
    }
    
    /**
     * Check if any loading state is active
     */
    const hasActiveLoading = computed((): boolean => {
        return Object.values(loadingStates).some(state => state.isLoading)
    })
    
    /**
     * Get all active loading states
     */
    const activeLoadingStates = computed((): Record<string, LoadingState> => {
        return Object.fromEntries(
            Object.entries(loadingStates).filter(([_, state]) => state.isLoading)
        )
    })
    
    /**
     * Async wrapper that manages loading state
     */
    const withLoading = async <T>(
        key: string,
        asyncFn: () => Promise<T>,
        config: LoadingConfig = {}
    ): Promise<T> => {
        try {
            startLoading(key, config)
            const result = await asyncFn()
            stopLoading(key)
            return result
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'An error occurred'
            setError(key, errorMessage)
            throw error
        }
    }
    
    /**
     * Multi-step async wrapper
     */
    const withSteppedLoading = async <T>(
        key: string,
        steps: string[],
        asyncFn: (updateStep: (index: number, message?: string) => void) => Promise<T>,
        config: Omit<LoadingConfig, 'steps'> = {}
    ): Promise<T> => {
        try {
            startLoading(key, { ...config, steps })
            
            const stepUpdater = (index: number, message?: string) => {
                updateStep(key, index, message)
            }
            
            const result = await asyncFn(stepUpdater)
            stopLoading(key)
            return result
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'An error occurred'
            setError(key, errorMessage)
            throw error
        }
    }
    
    /**
     * Progress-based async wrapper
     */
    const withProgressLoading = async <T>(
        key: string,
        asyncFn: (updateProgress: (progress: number, message?: string) => void) => Promise<T>,
        config: LoadingConfig = {}
    ): Promise<T> => {
        try {
            startLoading(key, { ...config, showProgress: true })
            
            const progressUpdater = (progress: number, message?: string) => {
                updateProgress(key, progress, message)
            }
            
            const result = await asyncFn(progressUpdater)
            stopLoading(key)
            return result
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'An error occurred'
            setError(key, errorMessage)
            throw error
        }
    }
    
    return {
        // State
        loadingStates: readonly(loadingStates),
        hasActiveLoading,
        activeLoadingStates,
        
        // Methods
        startLoading,
        stopLoading,
        clearLoading,
        updateProgress,
        updateStep,
        updateMessage,
        setError,
        getLoadingState,
        
        // Async wrappers
        withLoading,
        withSteppedLoading,
        withProgressLoading
    }
}

/**
 * Get contextual loading messages
 */
function getContextualMessage(context: string): string {
    const messages: Record<string, string> = {
        loading: 'Loading content...',
        saving: 'Saving your changes...',
        uploading: 'Uploading files...',
        processing: 'Processing your request...',
        searching: 'Searching for results...',
        connecting: 'Establishing connection...',
        syncing: 'Synchronizing data...',
        deleting: 'Deleting item...',
        updating: 'Updating information...',
        creating: 'Creating new item...',
        fetching: 'Fetching data...',
        submitting: 'Submitting form...',
        authenticating: 'Authenticating...',
        validating: 'Validating input...',
        generating: 'Generating content...',
        analyzing: 'Analyzing data...',
        optimizing: 'Optimizing performance...',
        backing_up: 'Creating backup...',
        restoring: 'Restoring data...',
        importing: 'Importing data...',
        exporting: 'Exporting data...'
    }
    
    return messages[context] || 'Loading...'
}

/**
 * Predefined loading configurations for common scenarios
 */
export const LoadingPresets = {
    // Data fetching
    fetchingPosts: {
        type: 'skeleton' as const,
        context: 'fetching',
        message: 'Loading posts...'
    },
    
    fetchingProfile: {
        type: 'skeleton' as const,
        context: 'fetching',
        message: 'Loading profile...'
    },
    
    fetchingJobs: {
        type: 'skeleton' as const,
        context: 'fetching',
        message: 'Loading job opportunities...'
    },
    
    // Form submissions
    savingProfile: {
        type: 'contextual' as const,
        context: 'saving',
        message: 'Saving your profile changes...'
    },
    
    creatingPost: {
        type: 'contextual' as const,
        context: 'creating',
        message: 'Publishing your post...'
    },
    
    // File operations
    uploadingFiles: {
        type: 'contextual' as const,
        context: 'uploading',
        message: 'Uploading your files...',
        showProgress: true
    },
    
    // Search operations
    searchingAlumni: {
        type: 'spinner' as const,
        context: 'searching',
        message: 'Finding alumni...'
    },
    
    // Multi-step operations
    profileSetup: {
        type: 'contextual' as const,
        context: 'processing',
        steps: [
            'Creating your profile',
            'Setting up preferences',
            'Connecting to your network',
            'Finalizing setup'
        ]
    },
    
    dataImport: {
        type: 'contextual' as const,
        context: 'importing',
        steps: [
            'Validating data format',
            'Processing records',
            'Creating relationships',
            'Finalizing import'
        ]
    }
} as const

/**
 * Hook for specific loading scenarios
 */
export function useSpecificLoading(key: string, preset?: keyof typeof LoadingPresets) {
    const { 
        startLoading, 
        stopLoading, 
        getLoadingState, 
        updateProgress, 
        updateStep,
        setError,
        withLoading,
        withSteppedLoading,
        withProgressLoading
    } = useLoadingStates()
    
    const state = computed(() => getLoadingState(key))
    const isLoading = computed(() => state.value?.isLoading || false)
    const error = computed(() => state.value?.error || null)
    
    const start = (config?: LoadingConfig) => {
        const finalConfig = preset ? LoadingPresets[preset] : config
        startLoading(key, finalConfig)
    }
    
    const stop = () => stopLoading(key)
    
    return {
        state,
        isLoading,
        error,
        start,
        stop,
        updateProgress: (progress: number, message?: string) => updateProgress(key, progress, message),
        updateStep: (stepIndex: number, message?: string) => updateStep(key, stepIndex, message),
        setError: (error: string) => setError(key, error),
        withLoading: <T>(asyncFn: () => Promise<T>, config?: LoadingConfig) => 
            withLoading(key, asyncFn, config),
        withSteppedLoading: <T>(
            steps: string[],
            asyncFn: (updateStep: (index: number, message?: string) => void) => Promise<T>,
            config?: Omit<LoadingConfig, 'steps'>
        ) => withSteppedLoading(key, steps, asyncFn, config),
        withProgressLoading: <T>(
            asyncFn: (updateProgress: (progress: number, message?: string) => void) => Promise<T>,
            config?: LoadingConfig
        ) => withProgressLoading(key, asyncFn, config)
    }
}