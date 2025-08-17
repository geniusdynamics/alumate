import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import Integrations from '@/Pages/InstitutionAdmin/Settings/Integrations.vue';

// Mock Inertia's useForm
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    useForm: vi.fn((data) => ({
      ...data,
      processing: false,
      post: vi.fn(),
    })),
  };
});

const mockSettings = {
  email: { apiKey: 'email-key', fromEmail: 'test@example.com' },
  crm: { type: 'salesforce', apiKey: 'crm-key', apiUrl: 'https://crm.example.com' },
};

describe('Integrations.vue', () => {
  it('renders with initial data and default tab active', () => {
    const wrapper = mount(Integrations, {
      props: {
        settings: mockSettings,
      },
      global: {
        stubs: {
            // Stubbing layout to simplify test
            AdminLayout: {
                template: '<div><slot /></div>'
            }
        },
        mocks: {
          route: () => '/',
        },
      },
    });

    // Check if form fields in the default 'email' tab are populated
    const emailApiKeyInput = wrapper.find('input#email-api-key');
    expect((emailApiKeyInput.element as HTMLInputElement).value).toBe('email-key');

    const fromEmailInput = wrapper.find('input#email-from');
    expect((fromEmailInput.element as HTMLInputElement).value).toBe('test@example.com');
  });

  it('switches tabs and displays the correct content', async () => {
    const wrapper = mount(Integrations, {
       props: {
        settings: mockSettings,
      },
       global: {
        stubs: {
            AdminLayout: { template: '<div><slot /></div>' }
        },
        mocks: {
          route: () => '/',
        },
      },
    });

    // Find the CRM tab trigger and click it
    // Note: The actual component is from a UI library, so we target by role/text
    const crmTabTrigger = wrapper.findAll('button[role="tab"]').find(w => w.text() === 'CRM');
    expect(crmTabTrigger).toBeDefined();
    if(crmTabTrigger) {
        await crmTabTrigger.trigger('click');
    }

    // Now check for CRM content. The component re-renders, so we might need to wait
    await wrapper.vm.$nextTick();

    const crmApiKeyInput = wrapper.find('input#crm-api-key');
    expect(crmApiKeyInput.exists()).toBe(true);
    expect((crmApiKeyInput.element as HTMLInputElement).value).toBe('crm-key');
  });

  it('updates form state when an input is changed', async () => {
     const wrapper = mount(Integrations, {
       props: {
        settings: mockSettings,
      },
       global: {
        stubs: {
            AdminLayout: { template: '<div><slot /></div>' }
        },
        mocks: {
          route: () => '/',
        },
      },
    });

    const emailApiKeyInput = wrapper.find('input#email-api-key');
    await emailApiKeyInput.setValue('new-email-key');

    expect((emailApiKeyInput.element as HTMLInputElement).value).toBe('new-email-key');
  });
});
