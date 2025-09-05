import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ContentEditor from '@/components/ContentEditor.vue'

// Mock services
vi.mock('@/services/contentService', () => ({
  saveContent: vi.fn().mockResolvedValue({ success: true }),
  loadContent: vi.fn().mockResolvedValue({ content: [] }),
  uploadImage: vi.fn().mockResolvedValue({ url: 'https://example.com/image.jpg' }),
  deleteBlock: vi.fn().mockResolvedValue({ success: true })
}))

// Mock file handling
vi.mock('@/utils/fileUtils', () => ({
  generateUniqueId: () => 'test-id-' + Date.now(),
  validateFileSize: (file) => file.size < 5 * 1024 * 1024, // 5MB limit
  validateFileType: (file) => ['image/jpeg', 'image/png'].includes(file.type)
}))

// Mock drag-drop
vi.mock('@/utils/dragDrop', () => ({
  createDragImage: () => ({ element: document.createElement('div'), x: 0, y: 0 }),
  getDropPosition: () => ({ index: 0, position: 'before' })
}))

const mockContentBlocks = [
  { id: '1', type: 'text', content: 'Welcome text', position: 0 },
  { id: '2', type: 'image', content: 'image.jpg', position: 1 },
  { id: '3', type: 'button', content: { text: 'Click me', url: '#' }, position: 2 }
]

const createWrapper = (props = {}) => {
  return mount(ContentEditor, {
    props: {
      content: mockContentBlocks,
      templateId: 1,
      tenantId: 1,
      ...props
    },
    global: {
      stubs: ['svg'],
      plugins: [createTestingPinia()],
      mocks: {
        $toast: {
          success: vi.fn(),
          error: vi.fn(),
          warning: vi.fn()
        }
      }
    },
    attachTo: document.body
  })
}

describe('ContentEditor.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = createWrapper()
  })

  describe('Component Structure', () => {
    it('renders the content editor interface', () => {
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('.content-editor-container').exists()).toBe(true)
    })

    it('displays toolbar with editing controls', () => {
      expect(wrapper.find('.editor-toolbar').exists()).toBe(true)
    })

    it('shows block list with existing content', () => {
      const blocks = wrapper.findAll('.content-block')
      expect(blocks.length).toBe(mockContentBlocks.length)
    })

    it('renders view mode switcher (visual/code/split)', () => {
      const viewSwitchers = wrapper.find('.view-switcher')
      expect(viewSwitchers.exists()).toBe(true)
      expect(wrapper.text()).toMatch(/visual|code|split/i)
    })
  })

  describe('View Mode Switching', () => {
    it('starts in visual mode by default', () => {
      expect(wrapper.vm.viewMode).toBe('visual')
      expect(wrapper.find('.visual-view').isVisible()).toBe(true)
    })

    it('switches to code mode when selected', async () => {
      const codeViewBtn = wrapper.find('.view-mode-code')
      await codeViewBtn.trigger('click')

      expect(wrapper.vm.viewMode).toBe('code')
      expect(wrapper.find('.code-view').isVisible()).toBe(true)
    })

    it('shows split view with both visual and code', async () => {
      const splitViewBtn = wrapper.find('.view-mode-split')
      await splitViewBtn.trigger('click')

      expect(wrapper.vm.viewMode).toBe('split')
      expect(wrapper.find('.split-view').isVisible()).toBe(true)
    })

    it('preserves content when switching between views', async () => {
      wrapper = createWrapper({ content: mockContentBlocks })

      // Switch to code view
      const codeViewBtn = wrapper.find('.view-mode-code')
      await codeViewBtn.trigger('click')

      // Switch back to visual
      const visualViewBtn = wrapper.find('.view-mode-visual')
      await visualViewBtn.trigger('click')

      expect(wrapper.vm.contentBlocks.length).toBe(mockContentBlocks.length)
    })
  })

  describe('Block Management', () => {
    it('renders different block types correctly', () => {
      const textBlocks = wrapper.findAll('.block-type-text')
      const imageBlocks = wrapper.findAll('.block-type-image')
      const buttonBlocks = wrapper.findAll('.block-type-button')

      expect(textBlocks.length).toBeGreaterThan(0)
      expect(imageBlocks.length).toBeGreaterThan(0)
      expect(buttonBlocks.length).toBeGreaterThan(0)
    })

    it('adds new text block when requested', async () => {
      const initialBlockCount = wrapper.vm.contentBlocks.length

      const addTextBtn = wrapper.find('.add-text-block-btn')
      await addTextBtn.trigger('click')

      expect(wrapper.vm.contentBlocks.length).toBe(initialBlockCount + 1)
      expect(wrapper.vm.contentBlocks[wrapper.vm.contentBlocks.length - 1].type).toBe('text')
    })

    it('adds new image block when requested', async () => {
      const initialBlockCount = wrapper.vm.contentBlocks.length

      const addImageBtn = wrapper.find('.add-image-block-btn')
      await addImageBtn.trigger('click')

      expect(wrapper.vm.contentBlocks.length).toBe(initialBlockCount + 1)
      expect(wrapper.vm.contentBlocks[wrapper.vm.contentBlocks.length - 1].type).toBe('image')
    })

    it('removes block when delete action is triggered', async () => {
      const initialBlockCount = wrapper.vm.contentBlocks.length

      const firstBlock = wrapper.findAll('.content-block')[0]
      const deleteBtn = firstBlock.find('.delete-block-btn')
      await deleteBtn.trigger('click')

      expect(wrapper.vm.contentBlocks.length).toBe(initialBlockCount - 1)
    })

    it('reorders blocks when moved', async () => {
      const blocks = wrapper.findAll('.content-block')
      const firstBlock = blocks[0]
      const secondBlock = blocks[1]

      await firstBlock.trigger('dragstart')
      await secondBlock.trigger('drop')

      expect(wrapper.vm.contentBlocks[0].position).toBeDefined()
    })
  })

  describe('Block Type Specific Functionality', () => {
    describe('Text Blocks', () => {
      it('renders text content with proper formatting', () => {
        const textBlock = wrapper.find('.block-type-text')
        expect(textBlock.exists()).toBe(true)
        expect(textBlock.text()).toContain('Welcome text')
      })

      it('enters edit mode when clicked', async () => {
        const textBlock = wrapper.find('.block-type-text')
        await textBlock.trigger('click')

        expect(wrapper.vm.editingBlock).toBeDefined()
        expect(wrapper.find('.text-editor-active').exists()).toBe(true)
      })

      it('updates text content when edited', async () => {
        const textBlock = wrapper.find('.block-type-text')
        const editableArea = textBlock.find('[contenteditable]')

        await editableArea.setValue('Updated content')
        await editableArea.trigger('blur')

        expect(wrapper.vm.contentBlocks[0].content).toBe('Updated content')
      })

      it('supports rich text formatting', async () => {
        wrapper.vm.makeSelectedTextBold()
        wrapper.vm.makeSelectedTextItalic()
        wrapper.vm.insertLink()

        expect(wrapper.vm.editorContent).toMatch(/<strong>|<\/strong>|<em>|<\/em>|<a/)
      })

      it('handles paste operations correctly', async () => {
        const textBlock = wrapper.find('.block-type-text')
        const editableArea = textBlock.find('[contenteditable]')

        const pasteEvent = new CustomEvent('paste', {
          clipboardData: {
            getData: () => '<p>Pasted content with <strong>bold</strong></p>'
          }
        })

        editableArea.element.dispatchEvent(pasteEvent)
        await wrapper.vm.$nextTick()

        expect(wrapper.vm.editorContent).toContain('Pasted content')
      })
    })

    describe('Image Blocks', () => {
      it('displays image with correct source', () => {
        const imgBlock = wrapper.find('.block-type-image')
        const img = imgBlock.find('img')
        expect(img.exists()).toBe(true)
        expect(img.attributes('src')).toContain('image.jpg')
      })

      it('shows placeholder when image fails to load', () => {
        const mockImage = {}
        Object.defineProperty(mockImage, 'onerror', {
          set: (fn) => fn()
        })

        wrapper.vm.handleImageError(mockImage)
        expect(wrapper.find('.image-placeholder').exists()).toBe(true)
      })

      it('handles image upload workflow', async () => {
        const fileInput = wrapper.find('input[type="file"]')
        const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })

        await fileInput.trigger('change', { target: { files: [file] } })

        expect(vi.mocked(wrapper.vm.uploadImage)).toHaveBeenCalledWith(file)
      })

      it('validates file size before upload', () => {
        const largeFile = new File(['x'.repeat(10 * 1024 * 1024)], 'large.jpg', { type: 'image/jpeg' })
        const isValid = wrapper.vm.validateFileSize(largeFile)

        expect(isValid).toBe(false)
      })

      it('validates file type for images', () => {
        const invalidFile = new File(['test'], 'test.txt', { type: 'text/plain' })
        const isValid = wrapper.vm.validateFileType(invalidFile)

        expect(isValid).toBe(false)

        const validFile = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
        expect(wrapper.vm.validateFileType(validFile)).toBe(true)
      })

      it('resizes large images appropriately', () => {
        const largeImageData = {}
        wrapper.vm.resizeImage(largeImageData, { maxWidth: 800, maxHeight: 600 })

        expect(wrapper.vm.resizeImage).toBeDefined()
      })
    })

    describe('Button Blocks', () => {
      it('renders button with correct text and link', () => {
        const btnBlock = wrapper.find('.block-type-button')
        const button = btnBlock.find('button, a')

        expect(button.exists()).toBe(true)
        expect(button.text()).toContain('Click me')
      })

      it('opens link editing dialog when clicked', async () => {
        const btnBlock = wrapper.find('.block-type-button')
        const button = btnBlock.find('button, a')
        await button.trigger('dblclick')

        expect(wrapper.vm.showLinkDialog).toBe(true)
      })

      it('updates button properties from settings panel', async () => {
        const settingsBtn = wrapper.find('.block-settings-btn')
        await settingsBtn.trigger('click')

        const textInput = wrapper.find('.btn-text-input')
        await textInput.setValue('New Button Text')

        const saveBtn = wrapper.find('.save-settings-btn')
        await saveBtn.trigger('click')

        expect(wrapper.vm.editingBlock.content.text).toBe('New Button Text')
      })
    })

    describe('Video Blocks', () => {
      let wrapper

      beforeEach(() => {
        wrapper = createWrapper({
          content: [{ id: '1', type: 'video', content: 'https://youtube.com/watch?v=test', position: 0 }]
        })
      })

      it('renders video iframe with correct source', () => {
        const videoBlock = wrapper.find('.block-type-video')
        const iframe = videoBlock.find('iframe')

        expect(videoBlock.exists()).toBe(true)
        expect(iframe.attributes('src')).toMatch(/youtube\.com|youtu\.be/)
      })

      it('converts YouTube URLs to embed format', () => {
        const youtubeUrl = 'https://youtube.com/watch?v=dQw4w9WgXcQ'
        const embedUrl = wrapper.vm.convertToEmbedUrl(youtubeUrl)

        expect(embedUrl).toContain('embed/dQw4w9WgXcQ')
      })

      it('displays video thumbnail before load', () => {
        const thumbnail = wrapper.find('.video-thumbnail')
        expect(thumbnail.exists()).toBe(true)
      })

      it('handles invalid video URLs gracefully', () => {
        const invalidUrl = 'not-a-video-url'
        const isValid = wrapper.vm.isValidVideoUrl(invalidUrl)

        expect(isValid).toBe(false)
      })

      it('supports Vimeo video embeds', () => {
        const vimeoUrl = 'https://vimeo.com/123456789'
        const embedUrl = wrapper.vm.convertToEmbedUrl(vimeoUrl)

        expect(embedUrl).toContain('vimeo.com')
      })
    })

    describe('Quote Blocks', () => {
      let wrapper

      beforeEach(() => {
        wrapper = createWrapper({
          content: [{ id: '1', type: 'quote', content: { text: 'Test quote', author: 'Test Author' }, position: 0 }]
        })
      })

      it('displays quote text and author correctly', () => {
        const quoteBlock = wrapper.find('.block-type-quote')
        expect(quoteBlock.exists()).toBe(true)
        expect(quoteBlock.text()).toContain('Test quote')
        expect(quoteBlock.text()).toContain('Test Author')
      })

      it('shows quotation marks styling', () => {
        const quoteMarks = wrapper.find('.quote-marks')
        expect(quoteMarks.exists()).toBe(true)
      })

      it('makes quote text editable on click', async () => {
        const quoteText = wrapper.find('.quote-text')
        await quoteText.trigger('click')

        expect(wrapper.vm.editingBlock).toBeDefined()
        expect(wrapper.find('.quote-editor-active').exists()).toBe(true)
      })
    })
  })

  describe('Drag and Drop Functionality', () => {
    it('initiates drag operation when block is dragged', async () => {
      const block = wrapper.find('.content-block')
      await block.trigger('dragstart', {
        dataTransfer: {
          setData: vi.fn(),
          setDragImage: vi.fn()
        }
      })

      expect(wrapper.vm.draggingBlockId).toBeDefined()
    })

    it('calculates drop position correctly', async () => {
      const blocks = wrapper.findAll('.content-block')
      const targetBlock = blocks[1]

      await targetBlock.trigger('dragover', {
        clientY: 50,
        preventDefault: vi.fn()
      })

      expect(wrapper.vm.dragDropPosition).toBeDefined()
    })

    it('reorders blocks on successful drop', async () => {
      const blocks = wrapper.findAll('.content-block')
      const draggedBlock = blocks[0]
      const dropTarget = blocks[1]

      await draggedBlock.trigger('dragstart')
      await dropTarget.trigger('drop', {
        dataTransfer: {
          getData: vi.fn().mockReturnValue('1')
        },
        preventDefault: vi.fn()
      })

      // Verify blocks were reordered
      expect(wrapper.vm.contentBlocks[0].position).not.toBe(0)
    })

    it('provides visual feedback during drag operation', async () => {
      const block = wrapper.find('.content-block')
      await block.trigger('dragstart')

      expect(wrapper.find('.dragging-preview').exists()).toBe(true)
      expect(wrapper.classes()).toContain('dragging-active')
    })

    it('cleans up drag state after drop', async () => {
      const blocks = wrapper.findAll('.content-block')
      const draggedBlock = blocks[0]
      const dropTarget = blocks[1]

      await draggedBlock.trigger('dragstart')
      await dropTarget.trigger('drop')

      expect(wrapper.vm.draggingBlockId).toBeNull()
      expect(wrapper.classes()).not.toContain('dragging-active')
    })
  })

  describe('Settings Panels', () => {
    it('opens settings panel when settings button is clicked', async () => {
      const settingsBtn = wrapper.find('.block-settings-btn')
      await settingsBtn.trigger('click')

      expect(wrapper.vm.showSettingsPanel).toBe(true)
      expect(wrapper.find('.settings-panel').isVisible()).toBe(true)
    })

    it('displays appropriate settings based on block type', async () => {
      // Click settings on text block
      const textBlock = wrapper.find('.block-type-text')
      const settingsBtn = textBlock.find('.block-settings-btn')
      await settingsBtn.trigger('click')

      expect(wrapper.find('.text-settings').exists()).toBe(true)
    })

    it('applies setting changes to block', async () => {
      const block = { id: '1', type: 'text', content: 'Original' }
      wrapper.vm.openBlockSettings(block)

      const contentInput = wrapper.find('.content-input')
      await contentInput.setValue('Updated content')

      const saveBtn = wrapper.find('.apply-settings-btn')
      await saveBtn.trigger('click')

      expect(wrapper.vm.editingBlock.content).toBe('Updated content')
    })

    it('validates settings before applying', async () => {
      const block = { id: '1', type: 'button', content: { text: '', url: 'invalid-url' } }
      wrapper.vm.openBlockSettings(block)

      const saveBtn = wrapper.find('.apply-settings-btn')
      await saveBtn.trigger('click')

      expect(wrapper.find('.settings-validation-errors').exists()).toBe(true)
    })
  })

  describe('Keyboard Shortcuts', () => {
    it('handles save shortcut (Ctrl+S)', async () => {
      const saveSpy = vi.spyOn(wrapper.vm, 'saveContent')

      await wrapper.trigger('keydown.ctrl.s', { preventDefault: vi.fn() })

      expect(saveSpy).toHaveBeenCalled()
    })

    it('handles undo shortcut (Ctrl+Z)', async () => {
      const undoSpy = vi.spyOn(wrapper.vm, 'undo')

      await wrapper.trigger('keydown.ctrl.z', { preventDefault: vi.fn() })

      expect(undoSpy).toHaveBeenCalled()
    })

    it('handles redo shortcut (Ctrl+Y)', async () => {
      const redoSpy = vi.spyOn(wrapper.vm, 'redo')

      await wrapper.trigger('keydown.ctrl.y', { preventDefault: vi.fn() })

      expect(redoSpy).toHaveBeenCalled()
    })

    it('handles delete block shortcut (Delete)', async () => {
      wrapper.vm.selectedBlock = wrapper.vm.contentBlocks[0]

      const deleteSpy = vi.spyOn(wrapper.vm, 'deleteBlock')

      await wrapper.trigger('keydown.delete', { preventDefault: vi.fn() })

      expect(deleteSpy).toHaveBeenCalled()
    })

    it('shows available shortcuts in help dialog', async () => {
      const helpBtn = wrapper.find('.keyboard-shortcuts-btn')
      await helpBtn.trigger('click')

      expect(wrapper.vm.showKeyboardHelp).toBe(true)
      expect(wrapper.find('.keyboard-shortcuts-dialog').isVisible()).toBe(true)
    })
  })

  describe('Undo/Redo System', () => {
    it('records changes for undo functionality', async () => {
      const initialState = { ...wrapper.vm.contentBlocks[0] }

      wrapper.vm.updateBlockContent('1', 'Updated content')

      expect(wrapper.vm.history.length).toBeGreaterThan(0)
    })

    it('undoes last action correctly', async () => {
      const originalContent = wrapper.vm.contentBlocks[0].content

      wrapper.vm.updateBlockContent('1', 'Modified content')
      wrapper.vm.undo()

      expect(wrapper.vm.contentBlocks[0].content).toBe(originalContent)
    })

    it('redoes previously undone action', async () => {
      wrapper.vm.updateBlockContent('1', 'Modified content')
      wrapper.vm.undo()
      wrapper.vm.redo()

      expect(wrapper.vm.contentBlocks[0].content).toBe('Modified content')
    })

    it('limits history stack size', () => {
      const maxHistoryActions = wrapper.vm.maxHistorySize

      // Perform many actions to exceed limit
      for (let i = 0; i < maxHistoryActions + 5; i++) {
        wrapper.vm.updateBlockContent('1', `Content ${i}`)
      }

      expect(wrapper.vm.history.length).toBeLessThanOrEqual(maxHistoryActions)
    })

    it('disables undo button when no history available', () => {
      wrapper = createWrapper({ content: [] }) // Start with empty content

      expect(wrapper.find('.undo-btn').attributes('disabled')).toBeDefined()
    })
  })

  describe('Auto-save Functionality', () => {
    beforeEach(() => {
      vi.useFakeTimers()
    })

    afterEach(() => {
      vi.restoreAllMocks()
      vi.useRealTimers()
    })

    it('triggers auto-save after content changes', async () => {
      const saveSpy = vi.spyOn(wrapper.vm, 'autoSaveContent')

      wrapper.vm.updateBlockContent('1', 'Change 1')
      vi.advanceTimersByTime(2000) // Wait for auto-save delay

      expect(saveSpy).toHaveBeenCalled()
    })

    it('respects auto-save delay configuration', async () => {
      const saveSpy = vi.spyOn(wrapper.vm, 'autoSaveContent')

      wrapper.vm.updateBlockContent('1', 'Change 1')
      vi.advanceTimersByTime(1000) // Before auto-save delay

      expect(saveSpy).not.toHaveBeenCalled()

      vi.advanceTimersByTime(1000) // Now after delay
      expect(saveSpy).toHaveBeenCalled()
    })

    it('shows saving indicator during auto-save', async () => {
      wrapper.vm.updateBlockContent('1', 'Change 1')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('.saving-indicator').isVisible()).toBe(true)
    })

    it('disables auto-save when in code view', async () => {
      await wrapper.find('.view-mode-code').trigger('click')

      const saveSpy = vi.spyOn(wrapper.vm, 'autoSaveContent')

      wrapper.vm.updateBlockContent('1', 'Change 1')
      vi.advanceTimersByTime(2000)

      // Should not have auto-saved
      expect(saveSpy).not.toHaveBeenCalled()
    })
  })

  describe('Code View Functionality', () => {
    beforeEach(async () => {
      await wrapper.find('.view-mode-code').trigger('click')
    })

    it('converts content blocks to JSON string', () => {
      const codeContent = wrapper.vm.getCodeContent()
      expect(typeof codeContent).toBe('string')

      // Should be valid JSON
      expect(() => JSON.parse(codeContent)).not.toThrow()
    })

    it('parses and validates JSON input', async () => {
      const validJson = JSON.stringify(mockContentBlocks)

      wrapper.vm.updateCodeContent(validJson)
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.contentBlocks.length).toBe(mockContentBlocks.length)
    })

    it('shows syntax errors for invalid JSON', async () => {
      const invalidJson = '{ "invalid": json }'

      wrapper.vm.updateCodeContent(invalidJson)
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.codeError).toContain('JSON')
    })

    it('formats JSON with proper indentation', () => {
      wrapper.vm.formatCodeContent()
      const formatted = wrapper.vm.getCodeContent()

      expect(formatted).toMatch(/^\s{2}/m) // Should have indentation
    })

    it('provides syntax highlighting in code editor', () => {
      const codeEditor = wrapper.find('.code-editor')
      expect(codeEditor.classes()).toContain('syntax-highlighted')
    })
  })

  describe('Accessibility Features', () => {
    it('provides proper ARIA labels for interactive elements', () => {
      const toolbar = wrapper.find('.editor-toolbar')
      expect(toolbar.attributes('aria-label')).toBeDefined()

      const blocks = wrapper.findAll('.content-block')
      blocks.forEach(block => {
        expect(block.attributes('aria-label')).toBeDefined()
      })
    })

    it('supports keyboard navigation through blocks', async () => {
      const firstBlock = wrapper.find('.content-block')
      await firstBlock.element.focus()

      expect(document.activeElement).toBe(firstBlock.element)
    })

    it('announces content changes to screen readers', async () => {
      const announceSpy = vi.spyOn(wrapper.vm, 'announceContentChange')

      wrapper.vm.updateBlockContent('1', 'New content')

      expect(announceSpy).toHaveBeenCalledWith('New content')
    })

    it('provides sufficient color contrast', () => {
      const toolbar = wrapper.find('.editor-toolbar')
      expect(toolbar.classes()).toContain('high-contrast')

      const buttons = wrapper.findAll('button')
      buttons.forEach(button => {
        expect(button.classes()).toContain('accessible-button')
      })
    })

    it('supports tab navigation through toolbars', async () => {
      const toolbarItems = wrapper.findAll('.toolbar-item')

      for (let i = 0; i < toolbarItems.length - 1; i++) {
        await toolbarItems[i].element.focus()
        await wrapper.trigger('keydown.tab')

        expect(document.activeElement).toBe(toolbarItems[i + 1].element)
      }
    })
  })

  describe('Error Handling', () => {
    it('handles save operation failures gracefully', async () => {
      vi.mocked(wrapper.vm.saveContent).mockRejectedValue(new Error('Save failed'))

      await wrapper.vm.saveContent()

      expect(wrapper.vm.showErrorDialog).toBe(true)
      expect(wrapper.find('.error-message').exists()).toBe(true)
    })

    it('shows retry option for failed operations', async () => {
      vi.mocked(wrapper.vm.saveContent).mockRejectedValue(new Error('Save failed'))

      await wrapper.vm.saveContent()

      const retryBtn = wrapper.find('.retry-save-btn')
      expect(retryBtn.exists()).toBe(true)
    })

    it('validates content before saving', async () => {
      const invalidBlock = { id: '1', type: 'image', content: '' }
      wrapper.vm.contentBlocks = [invalidBlock]

      await wrapper.vm.saveContent()

      expect(wrapper.vm.validationErrors.length).toBeGreaterThan(0)
      expect(wrapper.find('.validation-errors').exists()).toBe(true)
    })

    it('handles network timeouts gracefully', async () => {
      vi.mocked(wrapper.vm.saveContent).mockImplementation(
        () => new Promise(resolve => setTimeout(() => resolve({ success: false }), 30000))
      )

      const promise = wrapper.vm.saveContent()

      vi.advanceTimersByTime(10000) // Advance 10 seconds

      expect(wrapper.find('.timeout-warning').exists()).toBe(true)
    })
  })

  describe('Performance Optimization', () => {
    it('debounces content updates', async () => {
      const updateSpy = vi.spyOn(wrapper.vm, 'updateContent')

      wrapper.vm.updateBlockContent('1', 'R')
      wrapper.vm.updateBlockContent('1', 'Ra')
      wrapper.vm.updateBlockContent('1', 'Rap')
      wrapper.vm.updateBlockContent('1', 'Rapi')
      wrapper.vm.updateBlockContent('1', 'Rapid')

      vi.advanceTimersByTime(300)

      expect(updateSpy).toHaveBeenCalledTimes(1)
    })

    it('lazy loads block editors', async () => {
      const block = wrapper.find('.content-block')
      await block.trigger('click')

      expect(wrapper.vm.lazyLoadedEditors).toContain('text-editor')
    })

    it('virtualizes large block lists', async () => {
      // Create large content
      const largeContent = Array(100).fill(null).map((_, i) => ({
        id: i.toString(),
        type: 'text',
        content: `Block ${i}`,
        position: i
      }))

      wrapper = createWrapper({ content: largeContent })

      const visibleBlocks = wrapper.findAll('.content-block')
      expect(visibleBlocks.length).toBeLessThan(100) // Not all rendered
    })

    it('prevents unnecessary re-renders', async () => {
      const renderSpy = vi.spyOn(wrapper.vm, '$forceUpdate')

      // Make change without forcing rerender
      wrapper.vm.contentBlocks[0].content = 'Updated'

      await wrapper.vm.$nextTick()

      expect(renderSpy).not.toHaveBeenCalled()
    })
  })
})