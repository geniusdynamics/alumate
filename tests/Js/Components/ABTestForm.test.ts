import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import ABTestForm from '../../../resources/js/components/Analytics/ABTestForm.vue';
import type { ABTestData } from '../../../resources/js/types/analytics';

describe('ABTestForm', () => {
    const mockTest: ABTestData = {
        id: '1',
        name: 'Test Campaign',
        description: 'Testing button colors',
        status: 'active',
        variants: [
            { id: 'v1', name: 'Blue Button', weight: 50, description: 'Blue variant' },
            { id: 'v2', name: 'Green Button', weight: 50, description: 'Green variant' }
        ],
        audience_criteria: ['desktop_users', 'mobile_users'],
        goal_event: 'button_click'
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders create form correctly', () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        expect(wrapper.text()).toContain('Create New A/B Test');
        expect(wrapper.find('input[name="name"]').exists()).toBe(true);
        expect(wrapper.find('textarea').exists()).toBe(true);
        expect(wrapper.text()).toContain('Variants');
        expect(wrapper.text()).toContain('Audience Criteria');
        expect(wrapper.text()).toContain('Goal Event');
    });

    it('renders edit form with pre-filled data', () => {
        const wrapper = mount(ABTestForm, {
            props: {
                test: mockTest,
                isEdit: true
            }
        });

        expect(wrapper.text()).toContain('Edit A/B Test');
        expect(wrapper.find('input[id="name"]').element.value).toBe('Test Campaign');
        expect(wrapper.find('textarea').element.value).toBe('Testing button colors');
        expect(wrapper.find('input[id="goal-event"]').element.value).toBe('button_click');
    });

    it('validates required fields', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Clear name field
        const nameInput = wrapper.find('input[id="name"]');
        await nameInput.setValue('');

        // Clear goal event
        const goalInput = wrapper.find('input[id="goal-event"]');
        await goalInput.setValue('');

        // Try to submit
        const form = wrapper.find('form');
        await form.trigger('submit.prevent');

        // Check for validation errors
        expect(wrapper.text()).toContain('Test name is required');
        expect(wrapper.text()).toContain('Goal event is required');
    });

    it('validates variant weights total 100%', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Set invalid weights
        const weightInputs = wrapper.findAll('input[type="number"]');
        await weightInputs[0].setValue(60);
        await weightInputs[1].setValue(60);

        const form = wrapper.find('form');
        await form.trigger('submit.prevent');

        expect(wrapper.text()).toContain('Variant weights must total 100%');
    });

    it('adds and removes variants', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Initially should have 2 variants
        let variantSections = wrapper.findAll('.p-4.border');
        expect(variantSections.length).toBe(2);

        // Add variant
        const addButton = wrapper.find('button[aria-label="Add variant"]');
        await addButton.trigger('click');

        variantSections = wrapper.findAll('.p-4.border');
        expect(variantSections.length).toBe(3);

        // Remove variant
        const removeButtons = wrapper.findAll('button[aria-label="Remove variant"]');
        await removeButtons[2].trigger('click'); // Remove the third variant

        variantSections = wrapper.findAll('.p-4.border');
        expect(variantSections.length).toBe(2);
    });

    it('prevents removing variants when only 2 remain', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Try to remove when only 2 variants exist
        const removeButtons = wrapper.findAll('button[aria-label="Remove variant"]');
        expect(removeButtons.length).toBe(0); // Should be disabled/hidden
    });

    it('redistributes weights when adding/removing variants', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Add a third variant
        const addButton = wrapper.find('button[aria-label="Add variant"]');
        await addButton.trigger('click');

        // Check weights are redistributed (should be 33, 33, 34 or similar)
        const weightInputs = wrapper.findAll('input[type="number"]');
        const weights = weightInputs.map(input => parseInt(input.element.value));
        expect(weights.reduce((sum, weight) => sum + weight, 0)).toBe(100);
    });

    it('handles audience criteria', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Add audience criterion
        const criterionInput = wrapper.find('input[placeholder="Add audience criterion"]');
        await criterionInput.setValue('new_audience');
        await criterionInput.trigger('keydown.enter');

        expect(wrapper.text()).toContain('new_audience');

        // Remove criterion
        const removeButtons = wrapper.findAll('button[aria-label="Remove criterion"]');
        await removeButtons[0].trigger('click');

        expect(wrapper.text()).not.toContain('new_audience');
    });

    it('prevents duplicate audience criteria', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        const criterionInput = wrapper.find('input[placeholder="Add audience criterion"]');

        // Add same criterion twice
        await criterionInput.setValue('duplicate');
        await criterionInput.trigger('keydown.enter');
        await criterionInput.setValue('duplicate');
        await criterionInput.trigger('keydown.enter');

        const criteriaTags = wrapper.findAll('.inline-flex.items-center');
        expect(criteriaTags.length).toBe(1); // Should only have one
    });

    it('emits submit event with correct data', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Fill form
        const nameInput = wrapper.find('input[id="name"]');
        await nameInput.setValue('New Test');

        const descTextarea = wrapper.find('textarea');
        await descTextarea.setValue('Test description');

        const goalInput = wrapper.find('input[id="goal-event"]');
        await goalInput.setValue('click_event');

        // Submit form
        const form = wrapper.find('form');
        await form.trigger('submit.prevent');

        expect(wrapper.emitted('submit')).toBeTruthy();
        const submittedData = wrapper.emitted('submit')[0][0] as ABTestData;
        expect(submittedData.name).toBe('New Test');
        expect(submittedData.description).toBe('Test description');
        expect(submittedData.goal_event).toBe('click_event');
    });

    it('emits cancel event', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        const cancelButton = wrapper.find('button[aria-label="Cancel"]');
        await cancelButton.trigger('click');

        expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('shows loading state during submission', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Fill required fields
        const nameInput = wrapper.find('input[id="name"]');
        await nameInput.setValue('Test');

        const goalInput = wrapper.find('input[id="goal-event"]');
        await goalInput.setValue('event');

        // Mock submission delay
        const form = wrapper.find('form');
        const submitPromise = form.trigger('submit.prevent');

        expect(wrapper.text()).toContain('Saving...');

        await submitPromise;
    });

    it('handles form validation errors from server', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Simulate server validation errors
        wrapper.vm.errors = {
            name: ['Name already exists'],
            variants: ['Invalid variant configuration']
        };

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Name already exists');
        expect(wrapper.text()).toContain('Invalid variant configuration');
    });

    it('validates variant names are not empty', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Clear variant name
        const nameInputs = wrapper.findAll('input[placeholder*="Variant"]');
        await nameInputs[0].setValue('');

        const form = wrapper.find('form');
        await form.trigger('submit.prevent');

        expect(wrapper.text()).toContain('Variant name is required');
    });

    it('validates weight ranges', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                isEdit: false
            }
        });

        // Set invalid weight
        const weightInputs = wrapper.findAll('input[type="number"]');
        await weightInputs[0].setValue(150); // Over 100

        const form = wrapper.find('form');
        await form.trigger('submit.prevent');

        expect(wrapper.text()).toContain('Weight must be between 1 and 100');
    });

    it('updates form when props change', async () => {
        const wrapper = mount(ABTestForm, {
            props: {
                test: mockTest,
                isEdit: true
            }
        });

        expect(wrapper.find('input[id="name"]').element.value).toBe('Test Campaign');

        // Update props
        const newTest = { ...mockTest, name: 'Updated Name' };
        await wrapper.setProps({ test: newTest });

        expect(wrapper.find('input[id="name"]').element.value).toBe('Updated Name');
    });
});