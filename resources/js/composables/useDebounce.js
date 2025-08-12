import { ref, watch } from 'vue'

export function useDebounce(fn, delay = 300) {
    let timeoutId = null
    
    return function (...args) {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => fn.apply(this, args), delay)
    }
}

export function useDebouncedRef(source, delay = 300) {
    const debounced = ref(source.value)
    
    watch(source, (newValue) => {
        clearTimeout(debounced._timeoutId)
        debounced._timeoutId = setTimeout(() => {
            debounced.value = newValue
        }, delay)
    })
    
    return debounced
}

export function useDebouncedWatch(source, callback, options = {}) {
    const { delay = 300, immediate = false } = options
    let timeoutId = null
    
    watch(
        source,
        (newValue, oldValue) => {
            clearTimeout(timeoutId)
            timeoutId = setTimeout(() => {
                callback(newValue, oldValue)
            }, delay)
        },
        { immediate }
    )
    
    return () => clearTimeout(timeoutId)
}