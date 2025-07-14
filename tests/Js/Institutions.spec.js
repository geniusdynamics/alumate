import { mount } from '@vue/test-utils';
import Index from '@/Pages/Institutions/Index.vue';
import Create from '@/Pages/Institutions/Create.vue';
import Edit from '@/Pages/Institutions/Edit.vue';
import CreateInstitutionForm from '@/Pages/Institutions/Partials/CreateInstitutionForm.vue';
import UpdateInstitutionForm from '@/Pages/Institutions/Partials/UpdateInstitutionForm.vue';

describe('Institutions components', () => {
    it('renders Index component', () => {
        const wrapper = mount(Index);
        expect(wrapper.html()).toContain('Institutions');
    });

    it('renders Create component', () => {
        const wrapper = mount(Create);
        expect(wrapper.html()).toContain('Create Institution');
    });

    it('renders Edit component', () => {
        const wrapper = mount(Edit, {
            props: {
                institution: {
                    id: 'test',
                    name: 'Test Institution',
                },
            },
        });
        expect(wrapper.html()).toContain('Edit Institution');
    });

    it('renders CreateInstitutionForm component', () => {
        const wrapper = mount(CreateInstitutionForm);
        expect(wrapper.html()).toContain('Create Institution');
    });

    it('renders UpdateInstitutionForm component', () => {
        const wrapper = mount(UpdateInstitutionForm, {
            props: {
                institution: {
                    id: 'test',
                    name: 'Test Institution',
                },
            },
        });
        expect(wrapper.html()).toContain('Update Institution');
    });
});
