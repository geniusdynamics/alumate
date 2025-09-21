<template>
    <div class="form-builder-panel h-full w-80 overflow-y-auto border-l border-gray-200 bg-white">
        <div class="border-b border-gray-200 p-4">
            <h3 class="text-lg font-semibold text-gray-900">Form Builder</h3>
            <p class="mt-1 text-sm text-gray-600">Create and configure forms with CRM integration</p>
        </div>

        <!-- Form Configuration -->
        <div class="space-y-4 p-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Form Name</label>
                <input
                    v-model="formConfig.name"
                    type="text"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter form name"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Description</label>
                <textarea
                    v-model="formConfig.description"
                    rows="3"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Form description"
                ></textarea>
            </div>

            <!-- CRM Integration Toggle -->
            <div class="border-t pt-4">
                <div class="mb-3 flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">CRM Integration</label>
                    <button
                        @click="toggleCrmIntegration"
                        :class="[
                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                            formConfig.crm_integration_config.enabled ? 'bg-blue-600' : 'bg-gray-200',
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                formConfig.crm_integration_config.enabled ? 'translate-x-6' : 'translate-x-1',
                            ]"
                        />
                    </button>
                </div>

                <div v-if="formConfig.crm_integration_config.enabled" class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">CRM Provider</label>
                        <select
                            v-model="formConfig.crm_integration_config.provider"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Provider</option>
                            <option value="salesforce">Salesforce</option>
                            <option value="hubspot">HubSpot</option>
                            <option value="pipedrive">Pipedrive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Field Types Palette -->
        <div class="border-t border-gray-200">
            <div class="p-4">
                <h4 class="mb-3 text-sm font-semibold text-gray-900">Field Types</h4>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="(fieldType, key) in fieldTypes"
                        :key="key"
                        @click="addField(key, fieldType)"
                        class="rounded-lg border border-gray-200 p-3 text-left transition-colors hover:border-blue-300 hover:bg-blue-50"
                    >
                        <div class="flex items-center space-x-2">
                            <Icon :name="fieldType.icon" class="h-4 w-4 text-gray-600" />
                            <span class="text-xs font-medium text-gray-700">{{ fieldType.label }}</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Icon } from '@/components/ui';
import { useFormBuilder } from '@/composables/useFormBuilder';
import { onMounted, reactive } from 'vue';

interface FormConfig {
    name: string;
    description: string;
    crm_integration_config: {
        enabled: boolean;
        provider: string;
        field_mappings: Record<string, string>;
    };
}

const { fieldTypes, loadFieldTypes, createForm } = useFormBuilder();

const formConfig = reactive<FormConfig>({
    name: '',
    description: '',
    crm_integration_config: {
        enabled: false,
        provider: '',
        field_mappings: {},
    },
});

const emit = defineEmits<{
    fieldAdded: [fieldType: string, fieldConfig: any];
    formConfigChanged: [config: FormConfig];
}>();

onMounted(async () => {
    await loadFieldTypes();
});

const toggleCrmIntegration = () => {
    formConfig.crm_integration_config.enabled = !formConfig.crm_integration_config.enabled;
    if (!formConfig.crm_integration_config.enabled) {
        formConfig.crm_integration_config.provider = '';
        formConfig.crm_integration_config.field_mappings = {};
    }
    emit('formConfigChanged', formConfig);
};

const addField = (fieldType: string, fieldConfig: any) => {
    emit('fieldAdded', fieldType, fieldConfig);
};
</script>
