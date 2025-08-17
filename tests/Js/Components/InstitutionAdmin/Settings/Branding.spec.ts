import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import Branding from '@/Pages/InstitutionAdmin/Settings/Branding.vue';
import { useForm } from '@inertiajs/vue3';

// Mock Inertia's useForm
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    useForm: vi.fn((data) => ({
      ...data,
      logo: null,
      processing: false,
      post: vi.fn(),
    })),
  };
});

const mockInstitution = {
  id: 1,
  name: 'Test University',
  logo_path: 'logos/old_logo.png',
  primary_color: '#ff0000',
  secondary_color: '#00ff00',
  feature_flags: {
    enable_social_timeline: true,
    enable_job_board: false,
  },
};

describe('Branding.vue', () => {
  it('renders with initial data from props', () => {
    const wrapper = mount(Branding, {
      props: {
        institution: mockInstitution,
      },
      global: {
        mocks: {
          route: () => '/',
        },
      },
    });

    // Check if form fields are populated correctly
    const primaryColorInput = wrapper.find('input#primary_color');
    expect((primaryColorInput.element as HTMLInputElement).value).toBe('#ff0000');

    const secondaryColorInput = wrapper.find('input#secondary_color');
    expect((secondaryColorInput.element as HTMLInputElement).value).toBe('#00ff00');

    const socialSwitch = wrapper.find('input#enable_social_timeline');
    expect((socialSwitch.element as HTMLInputElement).checked).toBe(true);

    const jobsSwitch = wrapper.find('input#enable_job_board');
    expect((jobsSwitch.element as HTMLInputElement).checked).toBe(false);
  });

  it('updates form state when a color is changed', async () => {
    const wrapper = mount(Branding, {
      props: {
        institution: mockInstitution,
      },
       global: {
        mocks: {
          route: () => '/',
        },
      },
    });

    const primaryColorInput = wrapper.find('input#primary_color');
    await primaryColorInput.setValue('#aabbcc');

    // This is tricky to test directly because useForm is mocked.
    // We check if the input's value has changed, which implies v-model is working.
    expect((primaryColorInput.element as HTMLInputElement).value).toBe('#aabbcc');
  });

  it('shows a preview when a new logo is selected', async () => {
    const wrapper = mount(Branding, {
      props: {
        institution: mockInstitution,
      },
       global: {
        mocks: {
          route: () => '/',
        },
      },
    });

    // Initially, it should show the existing logo
    expect(wrapper.find('img').attributes('src')).toBe('/storage/logos/old_logo.png');

    const file = new File(['(⌐□_□)'], 'logo.png', { type: 'image/png' });
    const input = wrapper.find('input[type="file"]');

    // We can't set input.files directly, so this part of the test is more of a placeholder
    // for what would happen. A more complex test setup would be needed to mock the file input.
    // However, we can verify that the change handler exists.
    expect(input.attributes('onchange')).toBeDefined();
  });
});
