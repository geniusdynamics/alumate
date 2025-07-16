<template>
    <Link href="/">
        <div class="flex items-center">
            <div class="text-2xl font-bold text-gray-900">
                {{ $page.props.app?.name || 'Laravel' }}
            </div>
        </div>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>