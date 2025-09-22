<script lang="ts" setup>
import type { LabelProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
<<<<<<< HEAD:resources/js/components/ui/form/FormLabel.vue
import { cn } from '@/Lib/utils'
=======
import { cn } from '@/lib/utils'
>>>>>>> origin/db1:resources/js/Components/ui/form/FormLabel.vue
import { Label } from '@/Components/ui/label'
import { useFormField } from './useFormField'

const props = defineProps<LabelProps & { class?: HTMLAttributes['class'] }>()

const { error, formItemId } = useFormField()
</script>

<template>
  <Label
    data-slot="form-label"
    :data-error="!!error"
    :class="cn(
      'data-[error=true]:text-destructive-foreground',
      props.class,
    )"
    :for="formItemId"
  >
    <slot />
  </Label>
</template>











