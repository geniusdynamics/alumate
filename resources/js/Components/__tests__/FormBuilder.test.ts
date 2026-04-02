import type { CRMIntegration, FieldType, FormConfig, FormField } from '@/types/forms';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { io, type Socket } from 'socket.io-client';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import FormBuilder from '../FormBuilder.vue';

// Mock child Components
vi.mock('./FormFieldComponent.vue', () => ({
    default: {
        name: 'FormFieldComponent',
        template: '<div data-testid="field-component">Field {{ field.label }}</div>',
        props: ['field', 'index'],
        emits: ['update', 'remove', 'move', 'duplicate', 'select'],
    },
}));
vi.mock('./FormFieldEditor.vue', () => ({
    default: {
        name: 'FormFieldEditor',
        template: '<div>Editor</div>',
        props: ['field'],
        emits: ['update', 'add-validation', 'remove-validation'],
    },
}));
vi.mock('./FormPreview.vue', () => ({
    default: {
        name: 'FormPreview',
        template: '<form @submit.prevent="$emit(\'submit\', formData)"><slot></slot></form>',
        props: ['form-config', 'fields', 'is-submitting'],
        emits: ['submit'],
    },
}));
vi.mock('./FormSettingsModal.vue', () => ({
    default: {
        name: 'FormSettingsModal',
        template: '<div>Settings Modal</div>',
        props: ['config'],
        emits: ['update', 'close'],
    },
}));
vi.mock('./CRMIntegrationModal.vue', () => ({
    default: {
        name: 'CRMIntegrationModal',
        template: '<div>CRM Modal</div>',
        props: ['tenant-id', 'form-id', 'fields', 'integration'],
        emits: ['update', 'close'],
    },
}));

// Mock axios
vi.mock('axios');
const mockedAxios = vi.mocked(axios, true);
mockedAxios.get.mockResolvedValue({ data: { config: {}, fields: [], crmIntegration: null } });
mockedAxios.post.mockResolvedValue({ data: { id: 'new-form-id' } });
mockedAxios.put.mockResolvedValue({ data: {} });

// Mock socket.io-client
const mockSocket: Partial<Socket> = {
    emit: vi.fn(),
    on: vi.fn((event: string, callback: (data: any) => void) => {
        // Mock socket events
        if (event === 'connect') {
            callback(undefined);
        } else if (event === 'form:change') {
            callback({ type: 'remote_change', tenantId: 'tenant-1' });
        }
    }),
    disconnect: vi.fn(),
    connect: vi.fn(),
};
vi.mocked(io).mockReturnValue(mockSocket as Socket);

describe('FormBuilder.vue', () => {
    let wrapper: any;

    beforeEach(async () => {
        vi.clearAllMocks();
        wrapper = mount(FormBuilder, {
            props: {
                formId: 'test-form-1',
                tenantId: 'tenant-1',
                socketUrl: 'http://localhost:3000',
            },
            global: {
                stubs: {
                    TextIcon: { template: '<span>T</span>' },
                    EmailIcon: { template: '<span>@</span>' },
                    SelectIcon: { template: '<span>▼</span>' },
                    CheckboxIcon: { template: '<span>☐</span>' },
                    RadioIcon: { template: '<span>○</span>' },
                    TextareaIcon: { template: '<span>📝</span>' },
                },
            },
        });
        await flushPromises();
    });

    afterEach(() => {
        if (wrapper) wrapper.unmount();
    });

    describe('Unit Tests - Mounting and Basic Functionality', () => {
        it('mounts without errors and initializes form', () => {
            expect(wrapper.exists()).toBe(true);
            expect(wrapper.vm.isLoading).toBe(false);
            expect(wrapper.vm.formConfig.name).toBe('New Form');
            expect(wrapper.vm.formFields).toEqual([]);
        });

        it('renders header with form title and status', () => {
            expect(wrapper.find('.form-title').text()).toContain('New Form');
            expect(wrapper.find('.form-status').text()).toBe('Draft');
        });

        it('toggles preview mode correctly', async () => {
            const previewBtn = wrapper.find('.preview-btn');
            await previewBtn.trigger('click');
            expect(wrapper.vm.isPreviewMode).toBe(true);
            expect(wrapper.find('.form-preview').exists()).toBe(true);
            expect(wrapper.find('.exit-preview-btn').exists()).toBe(true);

            await wrapper.find('.exit-preview-btn').trigger('click');
            expect(wrapper.vm.isPreviewMode).toBe(false);
        });

        it('shows form settings modal', async () => {
            const settingsBtn = wrapper.find('.settings-btn');
            await settingsBtn.trigger('click');
            expect(wrapper.vm.showFormSettings).toBe(true);
            expect(wrapper.findComponent({ name: 'FormSettingsModal' }).exists()).toBe(true);
        });

        it('shows CRM settings modal', async () => {
            const crmBtn = wrapper.find('.crm-btn');
            await crmBtn.trigger('click');
            expect(wrapper.vm.showCRMSettings).toBe(true);
            expect(wrapper.findComponent({ name: 'CRMIntegrationModal' }).exists()).toBe(true);
        });
    });

    describe('Unit Tests - Field Operations', () => {
        it('adds new field on drag and drop', async () => {
            // Simulate drag start
            const dragEvent = new DragEvent('dragstart', { bubbles: true });
            (dragEvent as any).dataTransfer = { setData: vi.fn() } as any;
            const fieldTypeItem = wrapper.findAll('.field-type-item')[0];
            await fieldTypeItem.trigger('dragstart', dragEvent);

            // Simulate drop
            const dropEvent = new DragEvent('drop', { bubbles: true });
            (dropEvent as any).dataTransfer = { getData: () => JSON.stringify({ type: 'text' }) } as any;
            const dropZone = wrapper.find('.form-drop-zone');
            await dropZone.trigger('drop', dropEvent);

            expect(wrapper.vm.formFields).toHaveLength(1);
            expect(wrapper.vm.formFields[0].type).toBe('text');
            expect(wrapper.vm.formFields[0].label).toBe('Text Field');
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'field_added',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('updates field properties', async () => {
            // Add a field first
            const addFieldSpy = vi.spyOn(wrapper.vm, 'addField');
            wrapper.vm.addField('text' as FieldType);
            expect(addFieldSpy).toHaveBeenCalled();

            const updatedField: Partial<FormField> = { label: 'Updated Label', required: true };
            const index = 0;
            await wrapper.vm.updateField(updatedField as FormField, index);

            expect(wrapper.vm.formFields[0].label).toBe('Updated Label');
            expect(wrapper.vm.formFields[0].required).toBe(true);
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'field_updated',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('removes field', async () => {
            wrapper.vm.addField('text' as FieldType);
            await wrapper.vm.removeField(0);
            expect(wrapper.vm.formFields).toHaveLength(0);
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'field_removed',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('moves field to new position', async () => {
            wrapper.vm.addField('text' as FieldType);
            wrapper.vm.addField('email' as FieldType);
            await wrapper.vm.moveField(0, 1); // Move first to second position
            expect(wrapper.vm.formFields[0].type).toBe('email');
            expect(wrapper.vm.formFields[1].type).toBe('text');
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'field_moved',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('duplicates field', async () => {
            wrapper.vm.addField('text' as FieldType);
            await wrapper.vm.duplicateField(0);
            expect(wrapper.vm.formFields).toHaveLength(2);
            expect(wrapper.vm.formFields[1].label).toBe('Text Field (Copy)');
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'field_duplicated',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('selects field for editing', () => {
            wrapper.vm.addField('text' as FieldType);
            wrapper.vm.selectField(wrapper.vm.formFields[0]);
            expect(wrapper.vm.selectedField).toEqual(wrapper.vm.formFields[0]);
        });

        it('updates selected field', async () => {
            wrapper.vm.addField('text' as FieldType);
            wrapper.vm.selectField(wrapper.vm.formFields[0]);
            const updatedField: Partial<FormField> = { label: 'Selected Updated' };
            await wrapper.vm.updateSelectedField(updatedField as FormField);
            expect(wrapper.vm.selectedField?.label).toBe('Selected Updated');
            expect(wrapper.vm.formFields[0].label).toBe('Selected Updated');
        });
    });

    describe('Unit Tests - Validation and Submission', () => {
        it('adds validation rule to field', async () => {
            wrapper.vm.addField('email' as FieldType);
            wrapper.vm.selectField(wrapper.vm.formFields[0]);
            const mockRule = { type: 'email', message: 'Invalid email' };
            await wrapper.vm.addFieldValidation(mockRule);
            expect(wrapper.vm.selectedField?.validation).toEqual(expect.objectContaining(mockRule));
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'validation_added',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('removes validation rule from field', async () => {
            wrapper.vm.addField('email' as FieldType);
            wrapper.vm.selectField(wrapper.vm.formFields[0]);
            wrapper.vm.selectedField!.validation = { email: { type: 'email' } };
            await wrapper.vm.removeFieldValidation('email');
            expect(wrapper.vm.selectedField?.validation).toBeUndefined();
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'validation_removed',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('validates form data correctly - positive case', () => {
            wrapper.vm.addField('text' as FieldType);
            wrapper.vm.formFields[0].required = true;
            const formData = { [wrapper.vm.formFields[0].id]: 'valid value' };
            const errors = wrapper.vm.validateFormData(formData);
            expect(errors).toEqual([]);
        });

        it('validates form data correctly - negative cases', () => {
            wrapper.vm.addField('email' as FieldType);
            wrapper.vm.formFields[0].required = true;
            const invalidData = { [wrapper.vm.formFields[0].id]: 'invalid-email' };
            const errors = wrapper.vm.validateFormData(invalidData);
            expect(errors).toContain('Email Address must be a valid email address');
        });

        it('handles form submission successfully with CRM', async () => {
            wrapper.vm.addField('text' as FieldType);
            const mockFormData = { [wrapper.vm.formFields[0].id]: 'test@example.com' };
            wrapper.vm.crmIntegration = {
                crmType: 'hubspot',
                connectionId: 'conn1',
                fieldMapping: [{ formFieldId: wrapper.vm.formFields[0].id, crmFieldId: 'email' }],
            } as CRMIntegration;

            mockedAxios.post.mockResolvedValueOnce({ data: { leadId: 'lead1' } }); // CRM
            mockedAxios.post.mockResolvedValueOnce({ data: {} }); // submission

            await wrapper.vm.handleFormSubmission(mockFormData);

            expect(mockedAxios.post).toHaveBeenCalledWith(
                '/api/crm/submit',
                expect.objectContaining({
                    tenantId: 'tenant-1',
                }),
            );
            expect(mockedAxios.post).toHaveBeenNthCalledWith(
                2,
                '/api/forms/test-form-1/submissions',
                expect.objectContaining({
                    tenantId: 'tenant-1',
                }),
            );
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:submit',
                expect.objectContaining({
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('handles form submission failure', async () => {
            wrapper.vm.addField('text' as FieldType);
            const mockFormData = { [wrapper.vm.formFields[0].id]: '' };
            wrapper.vm.formFields[0].required = true;

            await wrapper.vm.handleFormSubmission(mockFormData);

            expect(wrapper.emitted('error')).toBeTruthy();
        });

        it('submits to CRM with field mapping and tenant context', async () => {
            const mockCRMData = { email: 'test@example.com' };
            wrapper.vm.crmIntegration = {
                crmType: 'salesforce',
                connectionId: 'conn1',
                fieldMapping: [{ formFieldId: 'field1', crmFieldId: 'email' }],
            } as CRMIntegration;
            mockedAxios.post.mockResolvedValueOnce({ data: { contactId: 'contact1' } });

            await wrapper.vm.submitToCRM({ field1: 'test@example.com' });

            expect(mockedAxios.post).toHaveBeenCalledWith(
                '/api/crm/submit',
                expect.objectContaining({
                    crmType: 'salesforce',
                    tenantId: 'tenant-1',
                    data: { email: 'test@example.com' },
                }),
            );
        });

        it('handles CRM submission failure', async () => {
            mockedAxios.post.mockRejectedValueOnce(new Error('CRM error'));
            await expect(wrapper.vm.submitToCRM({})).rejects.toThrow('Failed to submit to CRM');
        });
    });

    describe('Integration Tests - Drag and Drop and CRM', () => {
        /**
         * @description Tests integration of drag-and-drop field addition with real-time sync and CRM submission
         */
        it('integrates drag-drop with socket broadcast', async () => {
            const dragEvent = new DragEvent('dragstart', { bubbles: true });
            (dragEvent as any).dataTransfer = { setData: vi.fn() } as any;
            const fieldTypeItem = wrapper.findAll('.field-type-item')[0];
            await fieldTypeItem.trigger('dragstart', dragEvent);

            const dropEvent = new DragEvent('drop', { bubbles: true });
            dropEvent.preventDefault = vi.fn();
            (dropEvent as any).dataTransfer = { getData: () => JSON.stringify({ type: 'select' }) } as any;
            const dropZone = wrapper.find('.form-drop-zone');
            await dropZone.trigger('dragover', dropEvent);
            await dropZone.trigger('drop', dropEvent);

            expect(wrapper.vm.formFields).toHaveLength(1);
            expect(wrapper.vm.formFields[0].type).toBe('select');
            expect(mockSocket.emit).toHaveBeenCalledTimes(1); // broadcast change
        });

        it('updates form config via modal and broadcasts', async () => {
            const mockNewConfig: Partial<FormConfig> = { name: 'Updated Form', submitButtonText: 'Send' };
            wrapper.vm.updateFormConfig(mockNewConfig);
            expect(wrapper.vm.formConfig.name).toBe('Updated Form');
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'config_updated',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('integrates CRM modal update with form', async () => {
            const mockIntegration: CRMIntegration = {
                crmType: 'hubspot',
                connectionId: 'conn1',
                fieldMapping: [{ formFieldId: 'field1', crmFieldId: 'name' }],
                leadScore: 50,
                tags: ['lead'],
            };
            wrapper.vm.onCRMIntegrationUpdated(mockIntegration);
            expect(wrapper.vm.crmIntegration).toEqual(mockIntegration);
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'crm_updated',
                    tenantId: 'tenant-1',
                }),
            );
        });
    });

    describe('E2E Simulation Tests - Socket Events and API Calls', () => {
        it('handles remote form changes via socket', async () => {
            wrapper.vm.addField('text' as FieldType);
            wrapper.vm.setupSocketEvents(); // Ensure events are set up
            const remoteChange = { type: 'field_added', data: { field: { id: 'remote-field', type: 'email' } }, tenantId: 'tenant-1' };
            (mockSocket.on as any).mock.calls.find((call: any[]) => call[0] === 'form:change')[1](remoteChange);
            expect(wrapper.vm.formFields).toHaveLength(2);
            expect(wrapper.vm.formFields[1].type).toBe('email');
        });

        it('ignores remote changes from different tenant', async () => {
            wrapper.vm.addField('text' as FieldType);
            const remoteChange = { type: 'field_added', data: { field: { id: 'remote-field', type: 'email' } }, tenantId: 'tenant-2' };
            (mockSocket.on as any).mock.calls.find((call: any[]) => call[0] === 'form:change')[1](remoteChange);
            expect(wrapper.vm.formFields).toHaveLength(1); // No change
        });

        it('saves form and handles API response', async () => {
            mockedAxios.post.mockResolvedValueOnce({ data: { id: 'saved-form-id' } });
            await wrapper.vm.saveForm();
            expect(mockedAxios.post).toHaveBeenCalledWith('/api/forms', expect.any(Object));
            expect(wrapper.vm.formConfig.id).toBe('saved-form-id');
            expect(wrapper.emitted('save')).toBeTruthy();
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    type: 'form_saved',
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('loads existing form data', async () => {
            const mockFormData = {
                config: { name: 'Loaded Form' },
                fields: [{ id: 'loaded-field', type: 'text', label: 'Loaded' }],
                crmIntegration: { crmType: 'pipedrive' },
            };
            mockedAxios.get.mockResolvedValueOnce({ data: mockFormData });
            await wrapper.setProps({ formId: 'loaded-form' });
            await flushPromises();
            expect(wrapper.vm.formConfig.name).toBe('Loaded Form');
            expect(wrapper.vm.formFields).toHaveLength(1);
            expect(wrapper.vm.crmIntegration?.crmType).toBe('pipedrive');
        });
    });

    describe('Tenant Isolation Tests', () => {
        /**
         * @description Verifies tenant isolation in form submissions, socket broadcasts, and data loading
         */
        it('includes tenantId in all submissions and broadcasts', async () => {
            wrapper.vm.addField('text' as FieldType);
            const mockFormData = { [wrapper.vm.formFields[0].id]: 'tenant-data' };
            await wrapper.vm.handleFormSubmission(mockFormData);
            expect(mockedAxios.post).toHaveBeenNthCalledWith(
                1,
                '/api/forms/test-form-1/submissions',
                expect.objectContaining({
                    tenantId: 'tenant-1',
                }),
            );
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:submit',
                expect.objectContaining({
                    tenantId: 'tenant-1',
                }),
            );

            await wrapper.vm.saveForm();
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    tenantId: 'tenant-1',
                }),
            );
        });

        it('loads form only for current tenant', async () => {
            mockedAxios.get.mockImplementation((url: string) => {
                if (url.includes('tenant-1')) {
                    return Promise.resolve({ data: { config: { name: 'Tenant1 Form' } } });
                }
                return Promise.reject(new Error('Tenant mismatch'));
            });
            await wrapper.vm.loadForm('test-form-1');
            expect(wrapper.vm.formConfig.name).toBe('Tenant1 Form');

            await wrapper.setProps({ tenantId: 'tenant-2' });
            await expect(wrapper.vm.loadForm('test-form-1')).rejects.toThrow('Tenant mismatch');
        });

        it('broadcasts changes only for current tenant', () => {
            wrapper.vm.addField('text' as FieldType);
            expect(mockSocket.emit).toHaveBeenCalledWith(
                'form:change',
                expect.objectContaining({
                    tenantId: 'tenant-1',
                }),
            );
        });
    });

    describe('Error Cases and Edge Cases', () => {
        it('handles socket initialization failure', async () => {
            vi.mocked(io).mockImplementation(() => {
                throw new Error('Socket init failed');
            });
            wrapper = mount(FormBuilder, { props: { tenantId: 'tenant-1' } });
            await flushPromises();
            expect(wrapper.vm.hasError).toBe(true);
            expect(wrapper.emitted('error')).toBeTruthy();
        });

        it('handles form save API failure', async () => {
            mockedAxios.post.mockRejectedValueOnce(new Error('Save failed'));
            await wrapper.vm.saveForm();
            expect(wrapper.vm.hasError).toBe(true);
            expect(wrapper.emitted('error')).toBeTruthy();
        });

        it('handles invalid drag-drop data', async () => {
            const dropEvent = new DragEvent('drop', { bubbles: true });
            (dropEvent as any).dataTransfer = { getData: () => 'invalid json' } as any;
            const dropZone = wrapper.find('.form-drop-zone');
            await dropZone.trigger('drop', dropEvent);
            expect(wrapper.vm.formFields).toHaveLength(0); // No field added
        });

        it('resets builder on error', () => {
            wrapper.vm.hasError = true;
            wrapper.vm.errorMessage = 'Test error';
            wrapper.vm.resetBuilder();
            expect(wrapper.vm.hasError).toBe(false);
            expect(wrapper.vm.errorMessage).toBe('');
            expect(wrapper.vm.selectedField).toBe(null);
        });
    });
});














