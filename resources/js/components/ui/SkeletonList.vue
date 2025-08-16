<template>
    <div 
        class="skeleton-list"
        :class="[variantClasses, spacingClasses]"
        role="status"
        :aria-label="ariaLabel"
    >
        <div 
            v-for="item in count" 
            :key="item"
            class="skeleton-list__item"
            :class="itemClasses"
        >
            <!-- Avatar/Icon -->
            <SkeletonLoader 
                v-if="showAvatar"
                :shape="avatarShape" 
                :size="avatarSize" 
                class="skeleton-list__avatar"
            />
            
            <!-- Content -->
            <div class="skeleton-list__content">
                <!-- Primary text -->
                <SkeletonLoader 
                    shape="text" 
                    :size="primaryTextSize" 
                    :width="getPrimaryWidth(item)"
                    class="skeleton-list__primary"
                />
                
                <!-- Secondary text -->
                <SkeletonLoader 
                    v-if="showSecondary"
                    shape="text" 
                    size="sm" 
                    :width="getSecondaryWidth(item)"
                    class="skeleton-list__secondary"
                />
                
                <!-- Tertiary text -->
                <SkeletonLoader 
                    v-if="showTertiary"
                    shape="text" 
                    size="xs" 
                    :width="getTertiaryWidth(item)"
                    class="skeleton-list__tertiary"
                />
            </div>
            
            <!-- Actions/Meta -->
            <div v-if="showActions" class="skeleton-list__actions">
                <SkeletonLoader 
                    v-for="action in actionCount" 
                    :key="action"
                    shape="button" 
                    size="sm"
                    class="skeleton-list__action"
                />
            </div>
            
            <!-- Status indicator -->
            <SkeletonLoader 
                v-if="showStatus"
                shape="circle" 
                size="xs"
                class="skeleton-list__status"
            />
        </div>
        
        <!-- Screen reader text -->
        <span class="sr-only">{{ screenReaderText }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import SkeletonLoader from './SkeletonLoader.vue'

interface Props {
    // List configuration
    count?: number
    variant?: 'simple' | 'detailed' | 'compact' | 'card' | 'table'
    
    // Spacing
    spacing?: 'tight' | 'normal' | 'loose'
    
    // Content options
    showAvatar?: boolean
    showSecondary?: boolean
    showTertiary?: boolean
    showActions?: boolean
    showStatus?: boolean
    
    // Avatar configuration
    avatarShape?: 'avatar' | 'circle' | 'rectangle'
    avatarSize?: 'xs' | 'sm' | 'md' | 'lg'
    
    // Text configuration
    primaryTextSize?: 'xs' | 'sm' | 'md' | 'lg'
    
    // Actions
    actionCount?: number
    
    // Width variation
    varyWidths?: boolean
    
    // Accessibility
    ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
    count: 5,
    variant: 'simple',
    spacing: 'normal',
    showAvatar: true,
    showSecondary: true,
    showTertiary: false,
    showActions: false,
    showStatus: false,
    avatarShape: 'avatar',
    avatarSize: 'md',
    primaryTextSize: 'md',
    actionCount: 1,
    varyWidths: true
})

const variantClasses = computed(() => {
    const variants = {
        simple: 'skeleton-list--simple',
        detailed: 'skeleton-list--detailed',
        compact: 'skeleton-list--compact',
        card: 'skeleton-list--card',
        table: 'skeleton-list--table'
    }
    return variants[props.variant]
})

const spacingClasses = computed(() => {
    const spacing = {
        tight: 'skeleton-list--tight',
        normal: 'skeleton-list--normal',
        loose: 'skeleton-list--loose'
    }
    return spacing[props.spacing]
})

const itemClasses = computed(() => {
    const classes = ['skeleton-list__item-base']
    
    if (props.variant === 'card') {
        classes.push('skeleton-list__item-card')
    }
    
    return classes.join(' ')
})

// Generate varied widths for more realistic loading states
const getPrimaryWidth = (index: number) => {
    if (!props.varyWidths) return '70%'
    
    const widths = ['85%', '60%', '75%', '90%', '65%']
    return widths[(index - 1) % widths.length]
}

const getSecondaryWidth = (index: number) => {
    if (!props.varyWidths) return '50%'
    
    const widths = ['45%', '60%', '40%', '55%', '50%']
    return widths[(index - 1) % widths.length]
}

const getTertiaryWidth = (index: number) => {
    if (!props.varyWidths) return '30%'
    
    const widths = ['35%', '25%', '40%', '30%', '45%']
    return widths[(index - 1) % widths.length]
}

const screenReaderText = computed(() => {
    const variantText = {
        simple: 'list items',
        detailed: 'detailed list items',
        compact: 'compact list items',
        card: 'card list items',
        table: 'table rows'
    }
    
    return `Loading ${props.count} ${variantText[props.variant]}, please wait`
})

const ariaLabel = computed(() => {
    return props.ariaLabel || screenReaderText.value
})
</script>

<style scoped>
.skeleton-list {
    @apply w-full;
}

/* Spacing variants */
.skeleton-list--tight {
    @apply space-y-2;
}

.skeleton-list--normal {
    @apply space-y-3;
}

.skeleton-list--loose {
    @apply space-y-4;
}

/* List item base */
.skeleton-list__item-base {
    @apply flex items-center;
}

.skeleton-list__item-card {
    @apply p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700;
}

/* Avatar */
.skeleton-list__avatar {
    @apply flex-shrink-0 mr-3;
}

/* Content */
.skeleton-list__content {
    @apply flex-1 space-y-1;
}

.skeleton-list__primary {
    @apply block;
}

.skeleton-list__secondary {
    @apply block;
}

.skeleton-list__tertiary {
    @apply block;
}

/* Actions */
.skeleton-list__actions {
    @apply flex items-center space-x-2 ml-3;
}

.skeleton-list__action {
    @apply flex-shrink-0;
}

/* Status */
.skeleton-list__status {
    @apply flex-shrink-0 ml-2;
}

/* Variant-specific styles */
.skeleton-list--simple .skeleton-list__item-base {
    @apply py-2;
}

.skeleton-list--detailed .skeleton-list__item-base {
    @apply py-3;
}

.skeleton-list--detailed .skeleton-list__content {
    @apply space-y-2;
}

.skeleton-list--compact .skeleton-list__item-base {
    @apply py-1;
}

.skeleton-list--compact .skeleton-list__avatar {
    @apply mr-2;
}

.skeleton-list--compact .skeleton-list__content {
    @apply space-y-0;
}

.skeleton-list--table .skeleton-list__item-base {
    @apply py-3 px-4 border-b border-gray-200 dark:border-gray-700;
}

.skeleton-list--table .skeleton-list__content {
    @apply grid grid-cols-3 gap-4;
}

.skeleton-list--table .skeleton-list__primary,
.skeleton-list--table .skeleton-list__secondary,
.skeleton-list--table .skeleton-list__tertiary {
    @apply col-span-1;
}

/* Screen reader only class */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>