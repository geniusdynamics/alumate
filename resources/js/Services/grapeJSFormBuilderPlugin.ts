import grapesjs from 'grapesjs';

export interface FormBuilderPluginOptions {
    formBuilderService?: any;
    crmIntegrationEnabled?: boolean;
    defaultFormConfig?: Record<string, any>;
}

export default function grapeJSFormBuilderPlugin(editor: grapesjs.Editor, options: FormBuilderPluginOptions = {}) {
    const { formBuilderService, crmIntegrationEnabled = true, defaultFormConfig = {} } = options;

    // Add form builder Components
    editor.DomComponents.addType('enhanced-form', {
        model: {
            defaults: {
                tagName: 'form',
                attributes: {
                    class: 'enhanced-form',
                    method: 'POST',
                    'data-form-builder': 'true',
                },
                traits: [
                    {
                        type: 'text',
                        name: 'form-name',
                        label: 'Form Name',
                        placeholder: 'Enter form name',
                    },
                    {
                        type: 'textarea',
                        name: 'form-description',
                        label: 'Description',
                        placeholder: 'Form description',
                    },
                    {
                        type: 'select',
                        name: 'form-method',
                        label: 'Method',
                        options: [
                            { value: 'POST', name: 'POST' },
                            { value: 'GET', name: 'GET' },
                        ],
                    },
                    {
                        type: 'text',
                        name: 'success-message',
                        label: 'Success Message',
                        placeholder: 'Thank you for your submission!',
                    },
                    {
                        type: 'text',
                        name: 'error-message',
                        label: 'Error Message',
                        placeholder: 'Please correct the errors below.',
                    },
                    {
                        type: 'text',
                        name: 'redirect-url',
                        label: 'Redirect URL',
                        placeholder: 'https://example.com/thank-you',
                    },
                    ...(crmIntegrationEnabled
                        ? [
                              {
                                  type: 'checkbox',
                                  name: 'crm-integration',
                                  label: 'Enable CRM Integration',
                              },
                              {
                                  type: 'select',
                                  name: 'crm-provider',
                                  label: 'CRM Provider',
                                  options: [
                                      { value: '', name: 'Select Provider' },
                                      { value: 'salesforce', name: 'Salesforce' },
                                      { value: 'hubspot', name: 'HubSpot' },
                                      { value: 'pipedrive', name: 'Pipedrive' },
                                  ],
                              },
                          ]
                        : []),
                ],
                Components: [
                    {
                        type: 'text',
                        content: 'Drag form fields here to build your form',
                    },
                ],
            },
        },
        view: {
            events: {
                dblclick: 'openFormBuilder',
            },

            openFormBuilder() {
                const component = this.model;
                const formConfig = this.getFormConfig(component);

                // Emit event to open form builder panel
                editor.trigger('form-builder:open', {
                    component,
                    config: formConfig,
                });
            },

            getFormConfig(component: any) {
                const traits = component.get('traits');
                const config: any = { ...defaultFormConfig };

                traits.forEach((trait: any) => {
                    const name = trait.get('name');
                    const value = trait.get('value');
                    config[name] = value;
                });

                return config;
            },
        },
    });

    // Add enhanced form field Components
    const formFieldTypes = [
        {
            type: 'enhanced-text-field',
            label: 'Text Input',
            icon: '<i class="fa fa-text-width"></i>',
            content: {
                type: 'enhanced-text-field',
                tagName: 'div',
                attributes: { class: 'form-field text-field' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Text Field',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'input',
                        type: 'input',
                        attributes: {
                            type: 'text',
                            class: 'form-control',
                            placeholder: 'Enter text',
                        },
                    },
                ],
            },
        },
        {
            type: 'enhanced-email-field',
            label: 'Email Input',
            icon: '<i class="fa fa-envelope"></i>',
            content: {
                type: 'enhanced-email-field',
                tagName: 'div',
                attributes: { class: 'form-field email-field' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Email',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'input',
                        type: 'input',
                        attributes: {
                            type: 'email',
                            class: 'form-control',
                            placeholder: 'Enter email address',
                        },
                    },
                ],
            },
        },
        {
            type: 'enhanced-phone-field',
            label: 'Phone Input',
            icon: '<i class="fa fa-phone"></i>',
            content: {
                type: 'enhanced-phone-field',
                tagName: 'div',
                attributes: { class: 'form-field phone-field' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Phone Number',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'input',
                        type: 'input',
                        attributes: {
                            type: 'tel',
                            class: 'form-control',
                            placeholder: '+1 (555) 123-4567',
                        },
                    },
                ],
            },
        },
        {
            type: 'enhanced-textarea-field',
            label: 'Text Area',
            icon: '<i class="fa fa-text-height"></i>',
            content: {
                type: 'enhanced-textarea-field',
                tagName: 'div',
                attributes: { class: 'form-field textarea-field' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Message',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'textarea',
                        type: 'textarea',
                        attributes: {
                            class: 'form-control',
                            placeholder: 'Enter your message',
                            rows: '4',
                        },
                    },
                ],
            },
        },
        {
            type: 'enhanced-select-field',
            label: 'Select Dropdown',
            icon: '<i class="fa fa-caret-square-o-down"></i>',
            content: {
                type: 'enhanced-select-field',
                tagName: 'div',
                attributes: { class: 'form-field select-field' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Select Option',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'select',
                        type: 'select',
                        attributes: { class: 'form-control' },
                        Components: [
                            { tagName: 'option', type: 'option', content: 'Option 1' },
                            { tagName: 'option', type: 'option', content: 'Option 2' },
                            { tagName: 'option', type: 'option', content: 'Option 3' },
                        ],
                    },
                ],
            },
        },
        {
            type: 'enhanced-checkbox-group',
            label: 'Checkbox Group',
            icon: '<i class="fa fa-check-square-o"></i>',
            content: {
                type: 'enhanced-checkbox-group',
                tagName: 'div',
                attributes: { class: 'form-field checkbox-group' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Select Options',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'div',
                        attributes: { class: 'checkbox-options' },
                        Components: [
                            {
                                tagName: 'label',
                                attributes: { class: 'checkbox-option' },
                                Components: [
                                    {
                                        tagName: 'input',
                                        attributes: { type: 'checkbox', value: 'option1' },
                                    },
                                    { type: 'text', content: ' Option 1' },
                                ],
                            },
                            {
                                tagName: 'label',
                                attributes: { class: 'checkbox-option' },
                                Components: [
                                    {
                                        tagName: 'input',
                                        attributes: { type: 'checkbox', value: 'option2' },
                                    },
                                    { type: 'text', content: ' Option 2' },
                                ],
                            },
                        ],
                    },
                ],
            },
        },
        {
            type: 'enhanced-radio-group',
            label: 'Radio Group',
            icon: '<i class="fa fa-dot-circle-o"></i>',
            content: {
                type: 'enhanced-radio-group',
                tagName: 'div',
                attributes: { class: 'form-field radio-group' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Choose Option',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'div',
                        attributes: { class: 'radio-options' },
                        Components: [
                            {
                                tagName: 'label',
                                attributes: { class: 'radio-option' },
                                Components: [
                                    {
                                        tagName: 'input',
                                        attributes: { type: 'radio', name: 'radio-group', value: 'option1' },
                                    },
                                    { type: 'text', content: ' Option 1' },
                                ],
                            },
                            {
                                tagName: 'label',
                                attributes: { class: 'radio-option' },
                                Components: [
                                    {
                                        tagName: 'input',
                                        attributes: { type: 'radio', name: 'radio-group', value: 'option2' },
                                    },
                                    { type: 'text', content: ' Option 2' },
                                ],
                            },
                        ],
                    },
                ],
            },
        },
        {
            type: 'enhanced-file-field',
            label: 'File Upload',
            icon: '<i class="fa fa-upload"></i>',
            content: {
                type: 'enhanced-file-field',
                tagName: 'div',
                attributes: { class: 'form-field file-field' },
                Components: [
                    {
                        tagName: 'label',
                        type: 'text',
                        content: 'Upload File',
                        attributes: { class: 'field-label' },
                    },
                    {
                        tagName: 'input',
                        type: 'input',
                        attributes: {
                            type: 'file',
                            class: 'form-control',
                        },
                    },
                ],
            },
        },
    ];

    // Register form field Components
    formFieldTypes.forEach((fieldType) => {
        editor.DomComponents.addType(fieldType.type, {
            model: {
                defaults: {
                    ...fieldType.content,
                    traits: [
                        {
                            type: 'text',
                            name: 'field-name',
                            label: 'Field Name',
                            placeholder: 'field_name',
                        },
                        {
                            type: 'text',
                            name: 'field-label',
                            label: 'Field Label',
                            placeholder: 'Field Label',
                        },
                        {
                            type: 'text',
                            name: 'placeholder',
                            label: 'Placeholder',
                            placeholder: 'Enter placeholder text',
                        },
                        {
                            type: 'checkbox',
                            name: 'required',
                            label: 'Required Field',
                        },
                        ...(crmIntegrationEnabled
                            ? [
                                  {
                                      type: 'text',
                                      name: 'crm-field',
                                      label: 'CRM Field Mapping',
                                      placeholder: 'e.g., FirstName, Email',
                                  },
                              ]
                            : []),
                    ],
                },
            },
        });
    });

    // Add form builder blocks to block manager
    editor.BlockManager.add('enhanced-form', {
        label: 'Enhanced Form',
        category: 'Forms',
        media: '<i class="fa fa-wpforms"></i>',
        content: { type: 'enhanced-form' },
    });

    // Add form field blocks
    formFieldTypes.forEach((fieldType) => {
        editor.BlockManager.add(fieldType.type, {
            label: fieldType.label,
            category: 'Form Fields',
            media: fieldType.icon,
            content: { type: fieldType.type },
        });
    });

    // Add form builder commands
    editor.Commands.add('open-form-builder', {
        run(editor, sender, options = {}) {
            const { component } = options;

            // Trigger form builder panel opening
            editor.trigger('form-builder:open', {
                component,
                config: component ? this.getFormConfig(component) : {},
            });
        },

        getFormConfig(component: any) {
            const traits = component.get('traits');
            const config: any = {};

            traits.forEach((trait: any) => {
                const name = trait.get('name');
                const value = trait.get('value');
                config[name] = value;
            });

            return config;
        },
    });

    // Add form validation command
    editor.Commands.add('validate-form', {
        run(editor, sender, options = {}) {
            const { component } = options;
            const formFields = this.getFormFields(component);
            const validationErrors: string[] = [];

            // Basic form validation
            if (!formFields.length) {
                validationErrors.push('Form must contain at least one field');
            }

            // Check for required field names
            formFields.forEach((field: any, index: number) => {
                const fieldName = field.get('attributes')['field-name'];
                if (!fieldName) {
                    validationErrors.push(`Field ${index + 1} is missing a field name`);
                }
            });

            if (validationErrors.length > 0) {
                editor.trigger('form-validation:errors', { errors: validationErrors });
            } else {
                editor.trigger('form-validation:success');
            }

            return validationErrors;
        },

        getFormFields(formComponent: any) {
            const fields: any[] = [];

            const findFields = (component: any) => {
                const type = component.get('type');
                if (type && type.startsWith('enhanced-') && type.includes('-field')) {
                    fields.push(component);
                }

                const children = component.get('Components');
                if (children) {
                    children.forEach((child: any) => findFields(child));
                }
            };

            findFields(formComponent);
            return fields;
        },
    });

    // Add CSS for form builder Components
    editor.on('load', () => {
        const css = `
      .enhanced-form {
        border: 2px dashed #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        min-height: 200px;
        background-color: #f8fafc;
      }
      
      .form-field {
        margin-bottom: 16px;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background-color: white;
      }
      
      .field-label {
        display: block;
        font-weight: 500;
        margin-bottom: 6px;
        color: #374151;
      }
      
      .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 14px;
      }
      
      .form-control:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
      }
      
      .checkbox-options,
      .radio-options {
        display: flex;
        flex-direction: column;
        gap: 8px;
      }
      
      .checkbox-option,
      .radio-option {
        display: flex;
        align-items: center;
        font-weight: normal;
        cursor: pointer;
      }
      
      .checkbox-option input,
      .radio-option input {
        margin-right: 8px;
        width: auto;
      }
    `;

        editor.addStyle(css);
    });

    // Listen for form builder events
    editor.on('form-builder:open', (data) => {
        console.log('Form builder opened:', data);
        // This would typically open a form builder panel or modal
    });

    editor.on('form-validation:errors', (data) => {
        console.warn('Form validation errors:', data.errors);
        // Show validation errors to user
    });

    editor.on('form-validation:success', () => {
        console.log('Form validation passed');
        // Show success message
    });
}
