/**
 * GrapeJS Custom Code Plugin
 *
 * Integrates custom HTML, CSS, and JavaScript code capabilities
 * directly into the GrapeJS page builder with isolation and security.
 */

import grapesjs from 'grapesjs'
import { customCodeStorageService, type CustomCode } from './CustomCodeStorageService'
import { customCodeValidationService } from './CustomCodeValidationService'
import { codeIsolationService } from './CodeIsolationService'

export interface CustomCodePluginOptions {
  tenantId: string
  pageId?: string
  enableSyntaxHighlighting: boolean
  enableAutoValidation: boolean
  enableIsolation: boolean
  allowedCodeTypes: ('html' | 'css' | 'javascript')[]
}

export default function customCodePlugin(editor: grapesjs.Editor, options: CustomCodePluginOptions) {
  const { tenantId, pageId, enableSyntaxHighlighting = true, enableAutoValidation = true, enableIsolation = true } = options
  
  // Add custom code components
  addCustomCodeComponents(editor, options)
  
  // Add custom code blocks
  addCustomCodeBlocks(editor, options)
  
  // Add custom code commands
  addCustomCodeCommands(editor, options)
  
  // Add custom code panels
  addCustomCodePanels(editor, options)
  
  // Initialize custom code manager
  initializeCustomCodeManager(editor, options)
}

/**
 * Add custom code components to GrapeJS
 */
function addCustomCodeComponents(editor: grapesjs.Editor, options: CustomCodePluginOptions) {
  const { Components } = editor
  
  // HTML Code Component
  Components.addType('custom-html', {
    model: {
      defaults: {
        tagName: 'div',
        attributes: { class: 'custom-html-component' },
        traits: [
          {
            type: 'select',
            name: 'customCode',
            label: 'HTML Code',
            options: []
          },
          {
            type: 'checkbox',
            name: 'isolated',
            label: 'Isolated Execution',
            value: true
          }
        ],
        script: function() {
          const customCodeId = this.getAttribute('data-custom-code-id')
          if (customCodeId && window.customCodeManager) {
            window.customCodeManager.executeHTML(customCodeId, this)
          }
        }
      },
      
      init() {
        this.loadCustomCodes()
        this.on('change:attributes:data-custom-code-id', this.updateContent)
      },
      
      async loadCustomCodes() {
        try {
          const codes = await customCodeStorageService.getCustomCodes({
            tenantId: options.tenantId,
            pageId: options.pageId
          })
          
          const htmlCodes = codes.filter(code => code.type === 'html' && code.isActive)
          const codeOptions = htmlCodes.map(code => ({
            id: code.id,
            name: code.name
          }))
          
          this.set('traits', [
            ...this.get('traits'),
            {
              type: 'select',
              name: 'customCode',
              label: 'HTML Code',
              options: codeOptions
            }
          ])
        } catch (error) {
          console.error('Failed to load custom HTML codes:', error)
        }
      },
      
      async updateContent() {
        const customCodeId = this.getAttributes()['data-custom-code-id']
        if (!customCodeId) return
        
        try {
          const code = await customCodeStorageService.getCustomCode(customCodeId)
          if (code && code.type === 'html') {
            // Validate code
            if (options.enableAutoValidation) {
              const validation = await customCodeValidationService.validateCode(code.code, 'html')
              if (!validation.isValid) {
                console.warn('Custom HTML code has validation issues:', validation.errors)
              }
            }
            
            // Execute with isolation if enabled
            if (options.enableIsolation) {
              const environment = codeIsolationService.createEnvironment()
              const result = await codeIsolationService.executeHTML(code.code, environment)
              
              if (result.success) {
                this.components(result.result as string)
              } else {
                console.error('Failed to execute HTML code:', result.error)
                this.components(`<div class="error">Error: ${result.error}</div>`)
              }
              
              environment.cleanup()
            } else {
              this.components(code.code)
            }
          }
        } catch (error) {
          console.error('Failed to update custom HTML content:', error)
        }
      }
    },
    
    view: {
      init() {
        this.listenTo(this.model, 'change:attributes:data-custom-code-id', this.updateContent)
      },
      
      updateContent() {
        this.model.updateContent()
      }
    }
  })
  
  // CSS Code Component
  Components.addType('custom-css', {
    model: {
      defaults: {
        tagName: 'style',
        attributes: { class: 'custom-css-component' },
        traits: [
          {
            type: 'select',
            name: 'customCode',
            label: 'CSS Code',
            options: []
          },
          {
            type: 'text',
            name: 'scope',
            label: 'CSS Scope',
            placeholder: '.my-scope'
          }
        ]
      },
      
      init() {
        this.loadCustomCodes()
        this.on('change:attributes:data-custom-code-id', this.updateContent)
      },
      
      async loadCustomCodes() {
        try {
          const codes = await customCodeStorageService.getCustomCodes({
            tenantId: options.tenantId,
            pageId: options.pageId
          })
          
          const cssCodes = codes.filter(code => code.type === 'css' && code.isActive)
          const codeOptions = cssCodes.map(code => ({
            id: code.id,
            name: code.name
          }))
          
          this.set('traits', [
            ...this.get('traits'),
            {
              type: 'select',
              name: 'customCode',
              label: 'CSS Code',
              options: codeOptions
            }
          ])
        } catch (error) {
          console.error('Failed to load custom CSS codes:', error)
        }
      },
      
      async updateContent() {
        const customCodeId = this.getAttributes()['data-custom-code-id']
        if (!customCodeId) return
        
        try {
          const code = await customCodeStorageService.getCustomCode(customCodeId)
          if (code && code.type === 'css') {
            // Validate code
            if (options.enableAutoValidation) {
              const validation = await customCodeValidationService.validateCode(code.code, 'css')
              if (!validation.isValid) {
                console.warn('Custom CSS code has validation issues:', validation.errors)
              }
            }
            
            // Execute with isolation if enabled
            if (options.enableIsolation) {
              const environment = codeIsolationService.createEnvironment()
              const scope = this.getAttributes()['data-scope'] || ''
              const result = await codeIsolationService.executeCSS(code.code, environment, scope)
              
              if (result.success) {
                this.components((result.result as HTMLStyleElement).textContent || '')
              } else {
                console.error('Failed to execute CSS code:', result.error)
              }
              
              environment.cleanup()
            } else {
              this.components(code.code)
            }
          }
        } catch (error) {
          console.error('Failed to update custom CSS content:', error)
        }
      }
    }
  })
  
  // JavaScript Code Component
  Components.addType('custom-javascript', {
    model: {
      defaults: {
        tagName: 'script',
        attributes: { class: 'custom-js-component' },
        traits: [
          {
            type: 'select',
            name: 'customCode',
            label: 'JavaScript Code',
            options: []
          },
          {
            type: 'checkbox',
            name: 'isolated',
            label: 'Isolated Execution',
            value: true
          },
          {
            type: 'checkbox',
            name: 'autoExecute',
            label: 'Auto Execute',
            value: false
          }
        ]
      },
      
      init() {
        this.loadCustomCodes()
        this.on('change:attributes:data-custom-code-id', this.updateContent)
      },
      
      async loadCustomCodes() {
        try {
          const codes = await customCodeStorageService.getCustomCodes({
            tenantId: options.tenantId,
            pageId: options.pageId
          })
          
          const jsCodes = codes.filter(code => code.type === 'javascript' && code.isActive)
          const codeOptions = jsCodes.map(code => ({
            id: code.id,
            name: code.name
          }))
          
          this.set('traits', [
            ...this.get('traits'),
            {
              type: 'select',
              name: 'customCode',
              label: 'JavaScript Code',
              options: codeOptions
            }
          ])
        } catch (error) {
          console.error('Failed to load custom JavaScript codes:', error)
        }
      },
      
      async updateContent() {
        const customCodeId = this.getAttributes()['data-custom-code-id']
        if (!customCodeId) return
        
        try {
          const code = await customCodeStorageService.getCustomCode(customCodeId)
          if (code && code.type === 'javascript') {
            // Validate code
            if (options.enableAutoValidation) {
              const validation = await customCodeValidationService.validateCode(code.code, 'javascript')
              if (!validation.isValid) {
                console.warn('Custom JavaScript code has validation issues:', validation.errors)
              }
            }
            
            this.components(code.code)
            
            // Auto-execute if enabled
            const autoExecute = this.getAttributes()['data-auto-execute'] === 'true'
            if (autoExecute) {
              this.executeJavaScript(code)
            }
          }
        } catch (error) {
          console.error('Failed to update custom JavaScript content:', error)
        }
      },
      
      async executeJavaScript(code: CustomCode) {
        if (options.enableIsolation) {
          const environment = codeIsolationService.createEnvironment()
          const result = await codeIsolationService.executeJavaScript(code.code, environment)
          
          if (!result.success) {
            console.error('Failed to execute JavaScript code:', result.error)
          }
          
          environment.cleanup()
        } else {
          try {
            eval(code.code)
          } catch (error) {
            console.error('Failed to execute JavaScript code:', error)
          }
        }
      }
    }
  })
}

/**
 * Add custom code blocks to GrapeJS
 */
function addCustomCodeBlocks(editor: grapesjs.Editor, options: CustomCodePluginOptions) {
  const { BlockManager } = editor
  
  // HTML Code Block
  if (options.allowedCodeTypes.includes('html')) {
    BlockManager.add('custom-html', {
      label: 'Custom HTML',
      category: 'Custom Code',
      media: '<svg viewBox="0 0 24 24"><path d="M12,17.56L16.07,16.43L16.62,10.33H9.38L9.2,8.3H16.8L17,6.31H7L7.56,12.32H14.45L14.22,14.9L12,15.5L9.78,14.9L9.64,13.24H7.64L7.93,16.43L12,17.56M4.07,3H19.93L18.5,19.2L12,21L5.5,19.2L4.07,3Z"/></svg>',
      content: {
        type: 'custom-html',
        attributes: { 'data-custom-code-id': '' }
      }
    })
  }
  
  // CSS Code Block
  if (options.allowedCodeTypes.includes('css')) {
    BlockManager.add('custom-css', {
      label: 'Custom CSS',
      category: 'Custom Code',
      media: '<svg viewBox="0 0 24 24"><path d="M5,3L4.35,6.34H17.94L17.5,8.5H3.92L3.26,11.83H16.85L16.09,15.64L10.61,17.45L5.86,15.64L6.19,14H2.85L2.06,18L9.91,21L18.96,18L20.16,11.97L20.4,10.76L21.94,3H5Z"/></svg>',
      content: {
        type: 'custom-css',
        attributes: { 'data-custom-code-id': '' }
      }
    })
  }
  
  // JavaScript Code Block
  if (options.allowedCodeTypes.includes('javascript')) {
    BlockManager.add('custom-javascript', {
      label: 'Custom JavaScript',
      category: 'Custom Code',
      media: '<svg viewBox="0 0 24 24"><path d="M3,3H21V21H3V3M7.73,18.04C8.13,18.89 8.92,19.59 10.27,19.59C11.77,19.59 12.8,18.79 12.8,17.04V11.26H11.1V17C11.1,17.86 10.75,18.08 10.2,18.08C9.62,18.08 9.38,17.68 9.11,17.21L7.73,18.04M13.71,17.86C14.21,18.84 15.22,19.59 16.8,19.59C18.4,19.59 19.6,18.76 19.6,17.23C19.6,15.82 18.79,15.19 17.35,14.57L16.93,14.39C16.2,14.08 15.89,13.87 15.89,13.37C15.89,12.96 16.2,12.64 16.7,12.64C17.18,12.64 17.5,12.85 17.79,13.37L19.1,12.5C18.55,11.54 17.77,11.17 16.7,11.17C15.19,11.17 14.22,12.13 14.22,13.4C14.22,14.78 15.03,15.43 16.25,15.95L16.67,16.13C17.45,16.47 17.91,16.68 17.91,17.26C17.91,17.74 17.46,18.09 16.76,18.09C15.93,18.09 15.45,17.66 15.09,17.06L13.71,17.86Z"/></svg>',
      content: {
        type: 'custom-javascript',
        attributes: { 'data-custom-code-id': '' }
      }
    })
  }
}

/**
 * Add custom code commands to GrapeJS
 */
function addCustomCodeCommands(editor: grapesjs.Editor, options: CustomCodePluginOptions) {
  const { Commands } = editor
  
  // Open Custom Code Panel Command
  Commands.add('open-custom-code', {
    run(editor) {
      const panelManager = editor.Panels
      const panel = panelManager.getPanel('views-container')
      
      if (panel) {
        panel.set('appendContent', `
          <div id="custom-code-panel" class="gjs-custom-code-panel">
            <!-- Custom code panel will be mounted here by Vue -->
          </div>
        `)
      }
    }
  })
  
  // Validate All Custom Code Command
  Commands.add('validate-custom-code', {
    async run(editor) {
      const components = editor.getComponents()
      const customCodeComponents = components.filter((comp: any) => 
        comp.get('type')?.startsWith('custom-')
      )
      
      const validationResults = []
      
      for (const component of customCodeComponents) {
        const customCodeId = component.getAttributes()['data-custom-code-id']
        if (customCodeId) {
          try {
            const code = await customCodeStorageService.getCustomCode(customCodeId)
            if (code) {
              const validation = await customCodeValidationService.validateCode(code.code, code.type)
              validationResults.push({
                component,
                code,
                validation
              })
            }
          } catch (error) {
            console.error('Failed to validate custom code:', error)
          }
        }
      }
      
      // Display validation results
      console.log('Custom Code Validation Results:', validationResults)
      
      // You could emit an event here to update the UI
      editor.trigger('custom-code:validation-complete', validationResults)
    }
  })
  
  // Execute All JavaScript Command
  Commands.add('execute-custom-javascript', {
    async run(editor) {
      const components = editor.getComponents()
      const jsComponents = components.filter((comp: any) => 
        comp.get('type') === 'custom-javascript'
      )
      
      for (const component of jsComponents) {
        const customCodeId = component.getAttributes()['data-custom-code-id']
        if (customCodeId) {
          try {
            const code = await customCodeStorageService.getCustomCode(customCodeId)
            if (code && code.type === 'javascript') {
              await component.executeJavaScript(code)
            }
          } catch (error) {
            console.error('Failed to execute JavaScript code:', error)
          }
        }
      }
    }
  })
}

/**
 * Add custom code panels to GrapeJS
 */
function addCustomCodePanels(editor: grapesjs.Editor, options: CustomCodePluginOptions) {
  const { Panels } = editor
  
  // Add Custom Code Panel Button
  Panels.addButton('options', {
    id: 'custom-code',
    className: 'fa fa-code',
    command: 'open-custom-code',
    attributes: { title: 'Custom Code' }
  })
  
  // Add Validation Button
  Panels.addButton('options', {
    id: 'validate-code',
    className: 'fa fa-check-circle',
    command: 'validate-custom-code',
    attributes: { title: 'Validate Custom Code' }
  })
  
  // Add Execute JavaScript Button
  if (options.allowedCodeTypes.includes('javascript')) {
    Panels.addButton('options', {
      id: 'execute-js',
      className: 'fa fa-play',
      command: 'execute-custom-javascript',
      attributes: { title: 'Execute JavaScript' }
    })
  }
}

/**
 * Initialize custom code manager
 */
function initializeCustomCodeManager(editor: grapesjs.Editor, options: CustomCodePluginOptions) {
  // Create global custom code manager
  ;(window as any).customCodeManager = {
    async executeHTML(customCodeId: string, element: HTMLElement) {
      try {
        const code = await customCodeStorageService.getCustomCode(customCodeId)
        if (code && code.type === 'html') {
          if (options.enableIsolation) {
            const environment = codeIsolationService.createEnvironment()
            const result = await codeIsolationService.executeHTML(code.code, environment, element)
            
            if (!result.success) {
              console.error('Failed to execute HTML code:', result.error)
            }
            
            // Don't cleanup immediately - let the component manage it
          } else {
            element.innerHTML = code.code
          }
        }
      } catch (error) {
        console.error('Failed to execute HTML code:', error)
      }
    },
    
    async executeCSS(customCodeId: string, scope?: string) {
      try {
        const code = await customCodeStorageService.getCustomCode(customCodeId)
        if (code && code.type === 'css') {
          if (options.enableIsolation) {
            const environment = codeIsolationService.createEnvironment()
            const result = await codeIsolationService.executeCSS(code.code, environment, scope)
            
            if (!result.success) {
              console.error('Failed to execute CSS code:', result.error)
            }
          } else {
            // Add CSS to document
            const style = document.createElement('style')
            style.textContent = code.code
            document.head.appendChild(style)
          }
        }
      } catch (error) {
        console.error('Failed to execute CSS code:', error)
      }
    },
    
    async executeJavaScript(customCodeId: string, context?: Record<string, any>) {
      try {
        const code = await customCodeStorageService.getCustomCode(customCodeId)
        if (code && code.type === 'javascript') {
          if (options.enableIsolation) {
            const environment = codeIsolationService.createEnvironment()
            const result = await codeIsolationService.executeJavaScript(code.code, environment, context)
            
            if (!result.success) {
              console.error('Failed to execute JavaScript code:', result.error)
            }
            
            environment.cleanup()
          } else {
            eval(code.code)
          }
        }
      } catch (error) {
        console.error('Failed to execute JavaScript code:', error)
      }
    }
  }
  
  // Cleanup on editor destroy
  editor.on('destroy', () => {
    codeIsolationService.cleanupAllEnvironments()
    delete (window as any).customCodeManager
  })
}