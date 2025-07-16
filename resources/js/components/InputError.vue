<template>
    <div v-show="message" class="text-sm text-red-600 mt-1">
        {{ message }}
    </div>
</template>

<script setup>
defineProps({
    message: String
});
</script>