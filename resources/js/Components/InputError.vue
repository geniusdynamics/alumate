<template>
    <div v-show="message" class="mt-1 text-sm text-red-600">
        {{ message }}
    </div>
</template>

<script setup>
defineProps({
    message: String,
});
</script>
