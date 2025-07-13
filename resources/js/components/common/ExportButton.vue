<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Download, FileText, FileSpreadsheet } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

interface Props {
    exportUrl: string;
    params?: Record<string, any>;
    formats?: ('csv' | 'json')[];
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    params: () => ({}),
    formats: () => ['csv', 'json'],
    loading: false,
});

const exportData = (format: string) => {
    const url = new URL(props.exportUrl, window.location.origin);
    
    // Add format parameter
    url.searchParams.append('format', format);
    
    // Add other parameters
    Object.entries(props.params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.append(key, String(value));
        }
    });
    
    // Create a temporary link and trigger download
    const link = document.createElement('a');
    link.href = url.toString();
    link.download = `export.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const formatConfig = {
    csv: {
        label: 'CSV',
        icon: FileSpreadsheet,
        description: 'Comma-separated values',
    },
    json: {
        label: 'JSON',
        icon: FileText,
        description: 'JavaScript Object Notation',
    },
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button variant="outline" size="sm" :disabled="loading">
                <Download class="mr-2 h-4 w-4" />
                Export
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="format in formats"
                :key="format"
                @click="exportData(format)"
                class="cursor-pointer"
            >
                <component :is="formatConfig[format].icon" class="mr-2 h-4 w-4" />
                <div class="flex flex-col">
                    <span class="font-medium">{{ formatConfig[format].label }}</span>
                    <span class="text-xs text-muted-foreground">{{ formatConfig[format].description }}</span>
                </div>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
