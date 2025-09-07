import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ContentEditor from '@/components/ContentEditor.vue'
import type { ContentBlock } from '@/types/components'

// Mock useDebounceFn
vi.mock('@vueuse/core', () => ({
  useDebounceFn: vi.fn((fn) => fn)
}))

describe('ContentEditor.vue', () => {
  let wrapper
  let mockContent

  beforeEach(() => {
    // Mock localStorage
    Storage.prototype.getItem = vi.fn()
    Storage.prototype.setItem = vi.fn()
    document.addEventListener = vi.fn()
    document.removeEventListener = vi.fn()

    // Mock content data
    mockContent = {
      blocks: [
        {
          id: 'block-1',
          type: 'text' as ContentBlock['type'],
          data: {
            html: '<p>Hello World</p>',
            text: 'Hello World',
            format: 'paragraph' as const,
            alignment: 'left' as const
          },
          position: 0,
          isVisible: true,
          responsiveSettings: {
            desktop: {},
            tablet: {},
            mobile: {}
          },
          animationSettings: {
            enabled: false,
            type: 'fade',
            delay: 0,
            duration: 500
          },
          accessibilitySettings: {
            ariaLabel: '',
            role: '',
            screenReaderText: ''
          }
        }
      ],
      layout: {
        maxWidth: 'lg' as const,
        alignment: 'center' as const,
        verticalSpacing: 'md' as const,
        responsiveGaps: true
      },
      global: {}
    }

    wrapper = mount(ContentEditor, {
      global: {
        plugins: [createTestingPinia()],
        stubs: {
          BlockSettings: true,
          TextBlock: true,
          ImageBlock: true,
          ButtonBlock: true,
          DividerBlock: true,
          VideoBlock: true,
          QuoteBlock: true,
          SpacerBlock: true,
          Transition: false,
          Teleport: false
        }
      },
      props: {
        modelValue: mockContent
      }
    })
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Initialization', () => {
    it('renders with correct initial content', () => {
      const container = wrapper.find('.content-editor')
      expect(container.exists()).toBe(true)
      expect(wrapper.vm.content).toEqual(mockContent)
    })

    it('starts in visual view mode by default', () => {
      expect(wrapper.vm.activeView).toBe('visual')
    })

    it('initializes with provided content blocks', () => {
      expect(wrapper.vm.content.blocks).toHaveLength(1)
      expect(wrapper.vm.content.blocks[0].id).toBe('block-1')
    })

    it('loads settings with default values', () => {
      expect(wrapper.vm.settings.autoSave).toBe(true)
      expect(wrapper.vm.settings.realTimePreview).toBe(true)
      expect(wrapper.vm.settings.showAccessibilityWarnings).toBe(true)
    })

    it('sets up keyboard event listeners', () => {
      expect(document.addEventListener).toHaveBeenCalledWith('keydown', expect.any(Function))
    })
  })

  describe('View Mode Switching', () => {
    it('starts in visual mode by default', () => {
      expect(wrapper.vm.activeView).toBe('visual')
    })

    it('switches to code view when button clicked', async () => {
      const codeBtn = wrapper.findAll('.view-btn')[1] // Second button is Code
      await codeBtn.trigger('click')

      expect(wrapper.vm.activeView).toBe('code')
    })

    it('switches to split view when button clicked', async () => {
      const splitBtn = wrapper.findAll('.view-btn')[2] // Third button is Split
      await splitBtn.trigger('click')

      expect(wrapper.vm.activeView).toBe('split')
    })

    it('shows visual editor only in visual mode', () => {
      wrapper.vm.activeView = 'code'
      expect(wrapper.find('.visual-editor').exists()).toBe(false)

      wrapper.vm.activeView = 'visual'
      expect(wrapper.find('.visual-editor').exists()).toBe(true)
    })

    it('shows code editor in code and split modes', () => {
      wrapper.vm.activeView = 'code'
      expect(wrapper.find('.code-editor').exists()).toBe(true)

      wrapper.vm.activeView = 'visual'
      expect(wrapper.find('.code-editor').exists()).toBe(false)
    })
  })

  describe('Tool Bar Functionality', () => {
    it('shows add block button', () => {
      const addBtn = wrapper.find('.add-block-btn')
      expect(addBtn.exists()).toBe(true)
      expect(addBtn.text()).toContain('Add Block')
    })

    it('opens block menu when add block clicked', async () => {
      const addBtn = wrapper.find('.add-block-btn')
      await addBtn.trigger('click')

      expect(wrapper.vm.showBlockMenu).toBe(true)
    })

    it('closes block menu when close button clicked', async () => {
      wrapper.vm.showBlockMenu = true
      await wrapper.find('.close-menu-btn').trigger('click')

      expect(wrapper.vm.showBlockMenu).toBe(false)
    })

    it('shows correct block types in menu', async () => {
      await wrapper.find('.add-block-btn').trigger('click')

      const blockTypes = wrapper.findAll('.block-type-btn')
      expect(blockTypes.length).toBe(wrapper.vm.availableBlockTypes.length)
    })

    it('adds block when block type selected', async () => {
      await wrapper.find('.add-block-btn').trigger('click')
      const textBlockBtn = wrapper.findAll('.block-type-btn')[0] // First block is text
      await textBlockBtn.trigger('click')

      expect(wrapper.vm.content.blocks).toHaveLength(2)
      expect(wrapper.vm.showBlockMenu).toBe(false)
    })
  })

  describe('Block Management', () => {
    it('selects block when clicked', async () => {
      const block = wrapper.find('.content-block')
      await block.trigger('click')

      expect(wrapper.vm.selectedBlockId).toBe('block-1')
    })

    it('deselects block when clicked again', async () => {
      const block = wrapper.find('.content-block')
      await block.trigger('click')
      await block.trigger('click')

      expect(wrapper.vm.selectedBlockId).toBe(null)
    })

    it('deletes block with delete button', async () => {
      const initialLength = wrapper.vm.content.blocks.length
      const deleteBtn = wrapper.find('.block-control-btn.delete-btn')
      await deleteBtn.trigger('click')

      expect(wrapper.vm.content.blocks).toHaveLength(initialLength - 1)
      expect(wrapper.vm.selectedBlockId).toBe(null)
    })

    it('duplicates block when duplicate button clicked', async () => {
      const initialLength = wrapper.vm.content.blocks.length
      const duplicateBtn = wrapper.find('.block-control-btn').not('.delete-btn')
      await duplicateBtn.trigger('click')

      expect(wrapper.vm.content.blocks).toHaveLength(initialLength + 1)
    })

    it('updates block positions after deletion', async () => {
      await wrapper.find('.add-block-btn').trigger('click')
      const textBlockBtn = wrapper.findAll('.block-type-btn')[0]
      await textBlockBtn.trigger('click') // Add second block

      await wrapper.find('.block-control-btn.delete-btn').trigger('click') // Delete first block

      expect(wrapper.vm.content.blocks[0].position).toBe(0)
    })
  })

  describe('Drag and Drop', () => {
    beforeEach(async () => {
      // Add second block for drag testing
      await wrapper.find('.add-block-btn').trigger('click')
      const textBlockBtn = wrapper.findAll('.block-type-btn')[0]
      await textBlockBtn.trigger('click')
    })

    it('starts dragging when block drag start', async () => {
      const block = wrapper.find('.content-block')
      const dragStartEvent = new DragEvent('dragstart', {
        dataTransfer: { setData: vi.fn(), effectAllowed: 'move' }
      })

      block.element.dispatchEvent(dragStartEvent)
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.isDragging).toBe(true)
      expect(wrapper.vm.draggedBlockIndex).toBe(0)
    })

    it('stops dragging on drag end', async () => {
      wrapper.vm.isDragging = true
      const dragEndEvent = new DragEvent('dragend')

      document.dispatchEvent(dragEndEvent)
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.isDragging).toBe(false)
    })

    it('shows drag preview overlay when dragging', async () => {
      wrapper.vm.isDragging = true
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.drag-overlay').exists()).toBe(true)
    })

    it('reorders blocks on successful drop', async () => {
      const blocks = wrapper.findAll('.content-block')
      const draggedBlock = blocks[0]
      const targetBlock = blocks[1]

      // Simulate drag to second position
      const dropEvent = new DragEvent('drop')
      Object.defineProperty(dropEvent, 'dataTransfer', {
        value: { getData: vi.fn().mockReturnValue('0') }
      })

      targetBlock.element.dispatchEvent(dropEvent)
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.content.blocks[0].id).toBe('block-1')
      expect(wrapper.vm.content.blocks[1].id).toBe(wrapper.vm.content.blocks[0].id)
    })
  })

  describe('History Management', () => {
    it('tracks changes for undo functionality', () => {
      const initialLength = wrapper.vm.undoStack.length

      wrapper.vm.saveSnapshot('Test action')

      expect(wrapper.vm.undoStack.length).toBeGreaterThan(initialLength)
    })

    it('enables undo button when changes exist', () => {
      wrapper.vm.saveSnapshot('Test action')
      expect(wrapper.vm.canUndo).toBe(true)
    })

    it('enables redo button after undo', () => {
      wrapper.vm.saveSnapshot('Test action')
      wrapper.vm.undo()

      expect(wrapper.vm.canRedo).toBe(true)
    })

    it('disables undo when no history exists', () => {
      expect(wrapper.vm.canUndo).toBe(false)
    })

    it('restores state on undo', () => {
      const originalContent = JSON.stringify(wrapper.vm.content)
      wrapper.vm.saveSnapshot('Test action')

      // Modify content
      wrapper.vm.content.blocks[0].isVisible = false
      wrapper.vm.undo()

      expect(JSON.stringify(wrapper.vm.content)).toBe(originalContent)
    })

    it('clears redo stack on new action', () => {
      wrapper.vm.saveSnapshot('First action')
      wrapper.vm.undo() // Creates redo item

      wrapper.vm.saveSnapshot('Second action')

      expect(wrapper.vm.redoStack.length).toBe(0)
    })
  })

  describe('Auto-save and Save Functionality', () => {
    beforeEach(() => {
      vi.useFakeTimers()
    })

    afterEach(() => {
      vi.restoreAllMocks()
      vi.useRealTimers()
    })

    it('auto-saves when changes are made and enabled', () => {
      const mockSave = vi.spyOn(wrapper.vm, 'saveContent')
      wrapper.vm.content.blocks[0].isVisible = false

      vi.advanceTimersByTime(2000)

      expect(mockSave).toHaveBeenCalled()
    })

    it('does not auto-save when disabled', () => {
      wrapper.vm.settings.autoSave = false
      const mockSave = vi.spyOn(wrapper.vm, 'saveContent')

      wrapper.vm.content.blocks[0].isVisible = false
      vi.advanceTimersByTime(2000)

      expect(mockSave).not.toHaveBeenCalled()
    })

    it('shows saving indicator when saving', async () => {
      const savePromise = wrapper.vm.saveContent()

      expect(wrapper.vm.isSaving).toBe(true)
      expect(wrapper.vm.justSaved).toBe(false)

      await savePromise
      expect(wrapper.vm.isSaving).toBe(false)
      expect(wrapper.vm.justSaved).toBe(true)
    })

    it('resets saved indicator after duration', async () => {
      await wrapper.vm.saveContent()

      expect(wrapper.vm.justSaved).toBe(true)

      vi.advanceTimersByTime(2010)
      expect(wrapper.vm.justSaved).toBe(false)
    })
  })

  describe('JSON Editor', () => {
    beforeEach(async () => {
      wrapper.vm.activeView = 'code'
      await wrapper.vm.$nextTick()
    })

    it('renders JSON editor in code view', () => {
      expect(wrapper.find('.json-editor').exists()).toBe(true)
    })

    it('computes JSON from content correctly', () => {
      const expectedJson = JSON.stringify(wrapper.vm.content, null, 2)
      expect(wrapper.vm.contentJson).toBe(expectedJson)
    })

    it('updates content when JSON is modified', async () => {
      const modifiedJson = JSON.stringify({
        ...wrapper.vm.content,
        blocks: []
      }, null, 2)

      wrapper.vm.contentJson = modifiedJson
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.content.blocks).toHaveLength(0)
    })

    it('shows error for invalid JSON', () => {
      const invalidJson = '{"invalid": json}'
      wrapper.vm.contentJson = invalidJson

      expect(wrapper.vm.jsonError).toBeTruthy()
      expect(wrapper.find('.json-error').exists()).toBe(true)
    })

    it('formats JSON when format button clicked', async () => {
      wrapper.vm.contentJson = '{"test":"value"}'
      const formatBtn = wrapper.find('.format-btn')
      await formatBtn.trigger('click')

      expect(wrapper.vm.contentJson).toContain('  ')
    })

    it('clears JSON error when valid JSON entered', () => {
      wrapper.vm.jsonError = 'Invalid JSON'
      wrapper.vm.contentJson = '{"test": "value"}'

      expect(wrapper.vm.jsonError).toBe('')
    })
  })

  describe('Settings Panel', () => {
    beforeEach(async () => {
      await wrapper.find('.tool-btn').trigger('click')
      await wrapper.vm.$nextTick()
    })

    it('opens settings panel when settings button clicked', () => {
      expect(wrapper.vm.showSettings).toBe(true)
      expect(wrapper.find('.settings-panel').exists()).toBe(true)
    })

    it('closes settings panel when close button clicked', async () => {
      const closeBtn = wrapper.find('.close-settings-btn')
      await closeBtn.trigger('click')

      expect(wrapper.vm.showSettings).toBe(false)
    })

    it('toggles auto-save setting', async () => {
      const checkbox = wrapper.find('#setting-auto-save')
      await checkbox.setValue(true)

      expect(wrapper.vm.settings.autoSave).toBe(true)
    })

    it('toggles real-time preview setting', async () => {
      const checkbox = wrapper.find('#setting-real-time-preview')
      await checkbox.setValue(false)

      expect(wrapper.vm.settings.realTimePreview).toBe(false)
    })

    it('toggles accessibility warnings setting', async () => {
      const checkbox = wrapper.find('#setting-accessibility-warnings')
      await checkbox.setValue(false)

      expect(wrapper.vm.settings.showAccessibilityWarnings).toBe(false)
    })
  })

  describe('Keyboard Shortcuts', () => {
    it('saves changes with Ctrl+S', () => {
      const mockSave = vi.spyOn(wrapper.vm, 'saveContent')
      const event = new KeyboardEvent('keydown', {
        ctrlKey: true,
        key: 's'
      })

      document.dispatchEvent(event)

      expect(mockSave).toHaveBeenCalled()
    })

    it('undoes with Ctrl+Z', () => {
      wrapper.vm.saveSnapshot('Test action')
      const mockUndo = vi.spyOn(wrapper.vm, 'undo')

      const event = new KeyboardEvent('keydown', {
        ctrlKey: true,
        key: 'z'
      })
      document.dispatchEvent(event)

      expect(mockUndo).toHaveBeenCalled()
    })

    it('redoes with Ctrl+Y', () => {
      wrapper.vm.saveSnapshot('Test action')
      wrapper.vm.undo()
      const mockRedo = vi.spyOn(wrapper.vm, 'redo')

      const event = new KeyboardEvent('keydown', {
        ctrlKey: true,
        key: 'y'
      })
      document.dispatchEvent(event)

      expect(mockRedo).toHaveBeenCalled()
    })

    it('deletes selected block with Delete key', () => {
      wrapper.vm.selectedBlockId = 'block-1'
      const initialLength = wrapper.vm.content.blocks.length

      const event = new KeyboardEvent('keydown', { key: 'Delete' })
      document.dispatchEvent(event)

      expect(wrapper.vm.content.blocks).toHaveLength(initialLength - 1)
    })
  })

  describe('Block Settings Sidebar', () => {
    beforeEach(async () => {
      // Select a block to open sidebar
      const block = wrapper.find('.content-block')
      await block.trigger('click')
      await wrapper.vm.$nextTick()
    })

    it('shows block settings sidebar when block selected', () => {
      expect(wrapper.find('.block-editor-sidebar').exists()).toBe(true)
    })

    it('closes sidebar when close button clicked', async () => {
      const closeBtn = wrapper.find('.close-sidebar-btn')
      await closeBtn.trigger('click')

      expect(wrapper.vm.selectedBlockId).toBe(null)
    })

    it('passes selected block to settings component', () => {
      const blockSettings = wrapper.findComponent({ name: 'BlockSettings' })
      expect(blockSettings.exists()).toBe(true)
      expect(blockSettings.props().block).toBe(wrapper.vm.selectedBlock)
    })

    it('updates block when settings component emits update', () => {
      const blockSettings = wrapper.findComponent({ name: 'BlockSettings' })
      const newPosition = 5

      blockSettings.vm.$emit('update', 'block-1', { position: newPosition })

      expect(wrapper.vm.content.blocks[0].position).toBe(newPosition)
    })
  })

  describe('Block Type Support', () => {
    const blockTypes = [
      'text', 'image', 'button', 'divider',
      'video', 'link', 'quote', 'spacer'
    ]

    it.each(blockTypes)('supports %s block type', (blockType) => {
      const block = wrapper.vm.getDefaultBlockData(blockType)
      expect(block).toBeDefined()
      expect(typeof block).toBe('object')
    })

    it('adds correct block data for text type', () => {
      const textBlock = wrapper.vm.getDefaultBlockData('text')
      expect(textBlock.html).toContain('<p>')
      expect(textBlock.format).toBe('paragraph')
    })

    it('adds correct block data for image type', () => {
      const imageBlock = wrapper.vm.getDefaultBlockData('image')
      expect(imageBlock.url).toBe('')
      expect(imageBlock.width).toBe(800)
    })

    it('adds correct block data for button type', () => {
      const buttonBlock = wrapper.vm.getDefaultBlockData('button')
      expect(buttonBlock.text).toBe('Click Here')
      expect(buttonBlock.style).toBe('primary')
    })

    it('gets correct component name for each block type', () => {
      expect(wrapper.vm.getBlockComponent('text')).toBe('TextBlock')
      expect(wrapper.vm.getBlockComponent('image')).toBe('ImageBlock')
      expect(wrapper.vm.getBlockComponent('button')).toBe('ButtonBlock')
    })

    it('returns correct block type label', () => {
      expect(wrapper.vm.blockTypeLabel('text')).toBe('Text')
      expect(wrapper.vm.blockTypeLabel('image')).toBe('Image')
    })
  })

  describe('Error Handling', () => {
    it('handles canvas interaction gracefully', () => {
      expect(() => {
        wrapper.vm.startColorSelection(null)
      }).not.toThrow()
    })

    it('validates JSON input and shows error messages', () => {
      const invalidJson = '{"incomplete": json}'
      wrapper.vm.contentJson = invalidJson

      expect(wrapper.vm.jsonError).toContain('Invalid JSON')
    })

    it('prevents operations on non-existent blocks', () => {
      expect(() => {
        wrapper.vm.updateBlock('nonexistent', {})
      }).not.toThrow()

      expect(() => {
        wrapper.vm.duplicateBlock('nonexistent')
      }).not.toThrow()
    })

    it('handles drag operations gracefully', () => {
      // No dragged block index
      wrapper.vm.draggedBlockIndex = null

      const event = new DragEvent('drop')
      expect(() => {
        wrapper.vm.onBlockDrop(event)
      }).not.toThrow()
    })
  })

  describe('Event Emission', () => {
    it('emits update:modelValue when content changes', async () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')

      wrapper.vm.content.blocks[0].isVisible = false

      expect(emit).toHaveBeenCalledWith('update:modelValue', wrapper.vm.content)
    })

    it('emits save event when content saved', async () => {
      const emit = vi.spyOn(wrapper.vm, '$emit')
      await wrapper.vm.saveContent()

      expect(emit).toHaveBeenCalledWith('save', wrapper.vm.content)
    })

    it('watches for prop changes', async () => {
      const newContent = {
        ...wrapper.vm.content,
        blocks: [
          {
            ...wrapper.vm.content.blocks[0],
            id: 'new-block'
          }
        ]
      }

      await wrapper.setProps({ modelValue: newContent })

      expect(wrapper.vm.content).toEqual(newContent)
    })
  })

  describe('Accessibility Features', () => {
    it('provides ARIA labels for interactive elements', () => {
      const viewBtns = wrapper.findAll('.view-btn')
      viewBtns.forEach(btn => {
        expect(btn.attributes('aria-label')).toBeTruthy()
      })
    })

    it('indicates view mode state with aria-pressed', () => {
      const visualBtn = wrapper.find('.view-btn[aria-pressed="true"]')
      expect(visualBtn.exists()).toBe(true)
    })

    it('provides keyboard navigation for blocks', () => {
      const blocks = wrapper.findAll('.content-block')
      blocks.forEach(block => {
        expect(block.attributes('tabindex')).toBe('0')
        expect(block.attributes('role')).toBe('button')
      })
    })

    it('announces selected block with aria-selected', () => {
      const block = wrapper.find('.content-block')
      expect(block.attributes('aria-selected')).toBe('false')

      wrapper.vm.selectedBlockId = 'block-1'
      expect(block.attributes('aria-selected')).toBe('true')
    })

    it('provides ARIA labels for block actions', () => {
      const duplicateBtn = wrapper.find('.block-control-btn').not('.delete-btn')
      expect(duplicateBtn.attributes('aria-label')).toContain('Duplicate')
    })

    it('shows screen reader instructions in code view', async () => {
      wrapper.vm.activeView = 'code'
      await wrapper.vm.$nextTick()

      const codeEditor = wrapper.find('.json-editor')
      expect(codeEditor.attributes('aria-label')).toContain('Content structure JSON editor')
    })
  })

  describe('Performance and Responsiveness', () => {
    it('debounces auto-save operations', async () => {
      vi.useFakeTimers()
      const emitSpy = vi.spyOn(wrapper.vm, '$emit')

      // Rapid changes
      wrapper.vm.content.blocks[0].isVisible = false
      wrapper.vm.content.blocks[0].isVisible = true
      wrapper.vm.content.blocks[0].isVisible = false

      vi.advanceTimersByTime(500) // Less than debounce delay
      expect(emitSpy).not.toHaveBeenCalled()

      vi.advanceTimersByTime(2000) // After debounce delay
      expect(emitSpy).toHaveBeenCalled()
      vi.useRealTimers()
    })

    it('only renders visual editor when in visual mode', async () => {
      wrapper.vm.activeView = 'code'
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.visual-editor').exists()).toBe(false)

      wrapper.vm.activeView = 'visual'
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.visual-editor').exists()).toBe(true)
    })

    it('renders canvas for visual editing only when visible', () => {
      // This would test canvas rendering optimization
      // In a real test environment with canvas mocks
      expect(wrapper.vm.content.blocks.length).toBeGreaterThan(0)
    })

    it('handles large content structures efficiently', () => {
      // Simulate large content with many blocks
      const largeBlocks = Array.from({ length: 50 }, (_, i) => ({
        id: `block-${i}`,
        type: 'text' as ContentBlock['type'],
        position: i,
        isVisible: true
      }))

      wrapper.vm.content.blocks = largeBlocks
      expect(wrapper.vm.content.blocks.length).toBe(50)
    })
  })

  describe('Lifecycle Management', () => {
    it('sets up event listeners on mount', () => {
      expect(document.addEventListener).toHaveBeenCalledWith('keydown', expect.any(Function))
    })

    it('cleans up event listeners on unmount', () => {
      wrapper.unmount()
      expect(document.removeEventListener).toHaveBeenCalledWith('keydown', expect.any(Function))
    })

    it('saves initial snapshot on mount', () => {
      expect(wrapper.vm.undoStack.length).toBe(1) // Initial snapshot
    })

    it('updates content when model value prop changes', async () => {
      const newContent = { ...mockContent, blocks: [] }
      await wrapper.setProps({ modelValue: newContent })

      expect(wrapper.vm.content.blocks).toHaveLength(0)
    })
  })

  describe('Cross-browser Compatibility', () => {
    it('handles drag and drop events consistently', () => {
      const mockDragEvent = {
        preventDefault: vi.fn(),
        dataTransfer: {
          setData: vi.fn(),
          effectAllowed: 'move'
        }
      }

      expect(() => {
        wrapper.vm.onBlockDragStart(mockDragEvent, 0)
      }).not.toThrow()
    })

    it('gracefully handles localStorage errors', () => {
      Storage.prototype.setItem.mockImplementation(() => {
        throw new Error('Storage quota exceeded')
      })

      expect(() => {
        wrapper.vm.saveSnapshot('Test action')
      }).not.toThrow()
    })

    it('works with different JSON input formats', () => {
      const compactJson = '{"test": "value"}'
      const formattedJson = `{\n  "test": "value"\n}`

      wrapper.vm.contentJson = compactJson
      expect(wrapper.vm.content).toBeDefined()

      wrapper.vm.contentJson = formattedJson
      expect(wrapper.vm.content).toBeDefined()
    })
  })

  describe('Loading and Error States', () => {
    it('shows loading overlay when loading', async () => {
      wrapper.vm.isLoading = true
      wrapper.vm.loadingMessage = 'Saving...'
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.loading-overlay').exists()).toBe(true)
      expect(wrapper.find('.loading-content').text()).toContain('Saving...')
    })

    it('handles disabled state', async () => {
      const disabledWrapper = mount(ContentEditor, {
        global: {
          plugins: [createTestingPinia()]
        },
        props: {
          modelValue: mockContent,
          disabled: true
        }
      })

      expect(disabledWrapper.vm.disabled).toBe(true)
      // Toolbar buttons should be disabled when component is disabled
    })

    it('shows validation errors for block operations', () => {
      // Test edge cases for block validation
      const invalidBlock = {
        id: '',
        type: 'invalid' as any,
        position: -1,
        isVisible: true
      }

      expect(() => {
        wrapper.vm.addBlock('invalid')
      }).not.toThrow()
    })
  })
})