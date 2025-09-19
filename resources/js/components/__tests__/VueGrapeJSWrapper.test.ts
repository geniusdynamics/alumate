import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import VueGrapeJSWrapper from '../VueGrapeJSWrapper.vue'
import type { GrapeJSConfig, Component, HeroComponentConfig, CTAButton } from '@/types/components'
import { io, type Socket } from 'socket.io-client'

// Mock grapesjs
vi.mock('grapesjs', () => ({
  init: vi.fn()
}))
const grapesjs = { init: vi.fn() } as any

// Mock componentLibraryBridge
const mockComponentLibraryBridge = {
  initialize: vi.fn(),
  getRegisteredComponents: vi.fn(),
  getGrapeJSCategories: vi.fn(),
  convertToGrapeJSBlock: vi.fn(),
  searchComponents: vi.fn(),
  trackComponentUsage: vi.fn()
}
vi.mock('@/services/ComponentLibraryBridge', () => ({
  componentLibraryBridge: mockComponentLibraryBridge
}))

// Mock socket.io-client
const mockSocket = {
  emit: vi.fn(),
  on: vi.fn(),
  disconnect: vi.fn(),
  connect: vi.fn(() => {}),
  connected: true
}
vi.mocked(io).mockReturnValue(mockSocket as any)

// Mock VersionControlService for Phase 2
const mockVersionControlService = {
  serializePage: vi.fn(),
  saveVersion: vi.fn(),
  restoreVersion: vi.fn(),
  computeDiff: vi.fn()
}
vi.mock('@/services/VersionControlService', () => ({
  VersionControlService: mockVersionControlService
}))

// Mock brandConfigService
const mockBrandConfigService = {
  getBrandConfig: vi.fn()
}
vi.mock('@/services/BrandConfigService', () => ({
  brandConfigService: mockBrandConfigService
}))

// Mock fetch
const mockedFetch = vi.fn()
Object.defineProperty(globalThis, 'fetch', {
  value: mockedFetch,
  writable: true
})

// Mock document.querySelector
const mockCSRFToken = 'csrf-token-mock'
Object.defineProperty(document, 'querySelector', {
  value: vi.fn(() => ({ getAttribute: vi.fn(() => mockCSRFToken) })),
  writable: true
})

describe('VueGrapeJSWrapper.vue', () => {
  let wrapper: any
  const mockEditor: any = {
    BlockManager: {
      add: vi.fn(),
      addCategory: vi.fn(),
      get: vi.fn(),
      getAll: vi.fn(),
      render: vi.fn()
    },
    StyleManager: {
      sectors: vi.fn()
    },
    setComponents: vi.fn(),
    setStyle: vi.fn(),
    setDevice: vi.fn(),
    runCommand: vi.fn(),
    getHtml: vi.fn().mockReturnValue('<div>html</div>'),
    getCss: vi.fn().mockReturnValue('body {}'),
    getComponents: { toJSON: vi.fn().mockReturnValue([]) },
    getStyle: { toJSON: vi.fn().mockReturnValue([]) },
    on: vi.fn(),
    destroy: vi.fn()
  }
  const mockGrapesJSInit = vi.mocked(grapesjs.init)
  mockGrapesJSInit.mockResolvedValue(mockEditor)

  beforeEach(async () => {
    vi.clearAllMocks()
    wrapper = mount(VueGrapeJSWrapper, {
      props: {
        pageId: 'test-page-1',
        tenantId: 'tenant-1',
        height: '600px'
      },
      global: {
        stubs: {
          MonitorIcon: { template: '<span>Desktop</span>' },
          DeviceTabletIcon: { template: '<span>Tablet</span>' },
          DevicePhoneMobileIcon: { template: '<span>Mobile</span>' }
        }
      }
    })
    await flushPromises()
  })

  afterEach(() => {
    if (wrapper) wrapper.unmount()
  })

  describe('Unit Tests - Mounting and Basic Functionality', () => {
    it('mounts without errors', () => {
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.vm.isLoading).toBe(false)
    })

    it('renders toolbar with device switcher', () => {
      const deviceButtons = wrapper.findAll('.device-btn')
      expect(deviceButtons).toHaveLength(3)
      expect(deviceButtons[0].text()).toContain('Desktop')
    })

    it('handles props correctly', async () => {
      await wrapper.setProps({ height: '800px', disabled: true })
      expect(wrapper.vm.editorHeight).toBe('800px')
      expect(wrapper.attributes('disabled')).toBeUndefined() // Vue component doesn't directly apply disabled
    })

    it('emits ready event on mount', async () => {
      await flushPromises()
      expect(wrapper.emitted('ready')).toBeTruthy()
      expect((wrapper.emitted('ready') as any[][])[0][0]).toBe(mockEditor)
    })

    it('toggles panels correctly', async () => {
      const componentToggle = wrapper.find('[aria-label*="component panel"]')
      await componentToggle.trigger('click')
      expect(wrapper.vm.showComponentPanel).toBe(false)
    })

    it('switches device modes', async () => {
      const tabletButton = wrapper.findAll('.device-btn')[1]
      await tabletButton.trigger('click')
      expect(wrapper.vm.deviceMode).toBe('tablet')
      expect(mockEditor.setDevice).toHaveBeenCalledWith('tablet')
    })

    it('shows publish dialog on publish button click', async () => {
      const publishBtn = wrapper.find('.publish-btn')
      await publishBtn.trigger('click')
      expect(wrapper.vm.showPublishDialog).toBe(true)
    })

    it('handles save button click', async () => {
      vi.spyOn(wrapper.vm, 'saveCurrentPage')
      const saveBtn = wrapper.find('.save-btn')
      await saveBtn.trigger('click')
      expect(wrapper.vm.saveCurrentPage).toHaveBeenCalled()
    })

    it('handles error state', async () => {
      // Simulate error by making init fail
      mockGrapesJSInit.mockRejectedValueOnce(new Error('Init failed'))
      wrapper = mount(VueGrapeJSWrapper)
      await flushPromises()
      expect(wrapper.vm.isError).toBe(true)
      expect(wrapper.vm.errorMessage).toContain('Failed to initialize editor')
      expect(wrapper.emitted('error')).toBeTruthy()
    })
  })

  describe('Unit Tests - Methods', () => {
    it('initializeEditor initializes GrapeJS with correct config', async () => {
      const config: Partial<GrapeJSConfig> = { plugins: [] }
      await wrapper.setProps({ config })
      await wrapper.vm.initializeEditor()
      expect(mockGrapesJSInit).toHaveBeenCalledWith(expect.objectContaining({
        container: '#grapesjs-editor',
        height: '600px',
        storageManager: expect.objectContaining({
          type: 'remote',
          autosave: true
        })
      }))
    })

    it('setupEditorEvents sets up event listeners', () => {
      wrapper.vm.setupEditorEvents()
      expect(mockEditor.on).toHaveBeenCalledTimes(5) // component:selected, component:update, storage events, device:change
    })

    it('loadSystemData initializes bridge and loads data', async () => {
      vi.mocked(mockComponentLibraryBridge.getGrapeJSCategories).mockReturnValue([{ id: 'test', label: 'Test' }])
      vi.mocked(mockComponentLibraryBridge.getRegisteredComponents).mockReturnValue([{ id: 'test-comp' }])
      await wrapper.vm.loadSystemData()
      expect(mockComponentLibraryBridge.initialize).toHaveBeenCalled()
      expect(mockComponentLibraryBridge.getGrapeJSCategories).toHaveBeenCalled()
    })

    it('registerComponentsWithEditor registers blocks', async () => {
      vi.mocked(mockComponentLibraryBridge.getRegisteredComponents).mockReturnValue([
        { blockDefinition: { id: 'test-block', attributes: { 'data-component-id': 'test' } } }
      ])
      await wrapper.vm.registerComponentsWithEditor()
      expect(mockEditor.BlockManager.add).toHaveBeenCalled()
      expect(mockComponentLibraryBridge.trackComponentUsage).toHaveBeenCalledWith('test', 'editor_registration')
    })

    it('saveCurrentPage saves data and emits save', async () => {
      mockedFetch.mockResolvedValueOnce({ ok: true } as Response)
      await wrapper.vm.saveCurrentPage()
      expect(mockedFetch).toHaveBeenCalledWith('/api/pages/test-page-1', expect.objectContaining({
        method: 'PUT',
        headers: expect.objectContaining({ 'X-CSRF-TOKEN': mockCSRFToken })
      }))
      expect(wrapper.emitted('save')).toBeTruthy()
    })

    it('publishCurrentPage publishes and emits publish', async () => {
      mockedFetch.mockResolvedValueOnce({ ok: true } as Response)
      await wrapper.vm.publishCurrentPage()
      expect(mockedFetch).toHaveBeenCalledWith('/api/pages/test-page-1/publish', expect.objectContaining({
        method: 'POST'
      }))
      expect(wrapper.emitted('publish')).toBeTruthy()
      expect(wrapper.vm.showPublishDialog).toBe(false)
    })

    it('resetEditor clears canvas and resets state', () => {
      wrapper.vm.resetEditor()
      expect(mockEditor.runCommand).toHaveBeenCalledWith('core:canvas-clear')
      expect(wrapper.vm.selectedComponent).toBe(null)
      expect(wrapper.vm.deviceMode).toBe('desktop')
      expect(wrapper.vm.isError).toBe(false)
    })

    it('getCurrentEditorData returns editor data', () => {
      const data = wrapper.vm.getCurrentEditorData()
      expect(data).toEqual({
        html: '<div>html</div>',
        css: 'body {}',
        components: [],
        styles: []
      })
    })
  })

  describe('Phase 2 Features - Advanced Styling', () => {
    /**
     * @description Tests the advanced styling features including style manager sectors and custom CSS editor
     */
    it('configures style manager with custom sectors', async () => {
      await wrapper.vm.initializeEditor()
      const config = (mockGrapesJSInit as any).mock.calls[0][0]
      expect(config.styleManager).toBeDefined()
      expect(config.styleManager.sectors).toHaveLength(8)
      expect(config.styleManager.sectors[0].name).toBe('General')
      expect(config.styleManager.sectors[1].name).toBe('Typography')
      expect(config.styleManager.sectors[6].name).toBe('Responsive')
      expect(config.styleManager.sectors[7].name).toBe('Accessibility')
    })

    it('switches style tabs and renders CSS editor', async () => {
      await wrapper.find('.style-tab').trigger('click') // visual tab
      expect(wrapper.vm.activeStyleTab).toBe('visual')
      await wrapper.findAll('.style-tab')[1].trigger('click') // css tab
      expect(wrapper.vm.activeStyleTab).toBe('css')
      expect(wrapper.find('.css-editor').exists()).toBe(true)
    })

    it('validates CSS and shows errors', async () => {
      wrapper.vm.customCSS = '{ invalid css'
      wrapper.vm.validateCSS()
      expect(wrapper.vm.cssValidationErrors).toHaveLength(1)
      expect(wrapper.vm.cssValidationErrors[0].message).toContain('Missing closing brace')

      wrapper.vm.customCSS = 'div { color: red' // missing ;
      wrapper.vm.validateCSS()
      expect(wrapper.vm.cssValidationErrors).toHaveLength(1)
      expect(wrapper.vm.cssValidationErrors[0].message).toContain('Missing semicolon')
    })

    it('applies valid custom CSS and broadcasts via socket', async () => {
      wrapper.vm.customCSS = 'div { color: blue; }'
      wrapper.vm.validateCSS()
      expect(wrapper.vm.cssValidationErrors).toBe(0)
      await wrapper.vm.applyCustomCSS()
      expect(mockSocket.emit).toHaveBeenCalledWith('editor:change', expect.objectContaining({
        type: 'custom_css_update',
        css: 'div { color: blue; }',
        tenantId: 'tenant-1'
      }))
    })

    it('formats CSS correctly', () => {
      wrapper.vm.customCSS = 'div{color:red;}p{color:blue;}'
      wrapper.vm.formatCSS()
      expect(wrapper.vm.customCSS).toContain('div {\n  color: red;\n}\n\np {\n  color: blue;\n}\n')
    })

    it('handles invalid CSS application', async () => {
      wrapper.vm.customCSS = 'invalid { css'
      wrapper.vm.validateCSS()
      await wrapper.vm.applyCustomCSS()
      expect(mockSocket.emit).not.toHaveBeenCalled()
    })
  })

  describe('Phase 2 Features - Real-time Editing with Socket.io', () => {
    /**
     * @description Tests real-time synchronization of style and component changes across clients
     */
    it('initializes socket and joins tenant room', async () => {
      await wrapper.vm.initializeSocket()
      expect(mockSocket.on).toHaveBeenCalledWith('connect', expect.any(Function))
      expect(mockSocket.emit).toHaveBeenCalledWith('join:tenant', { tenantId: 'tenant-1', pageId: 'test-page-1' })
    })

    it('broadcasts style updates via socket with tenant isolation', () => {
      wrapper.vm.setupEditorEvents()
      const mockComponent = { getId: () => 'comp1', setStyle: vi.fn() }
      const mockStyle = { color: 'red' }
      ;(mockEditor.on as any).mock.calls.find((call: any[]) => call[0] === 'style:update')[1](mockComponent, mockStyle)
      expect(mockSocket.emit).toHaveBeenCalledWith('editor:change', expect.objectContaining({
        type: 'style_update',
        tenantId: 'tenant-1',
        pageId: 'test-page-1'
      }))
    })

    it('applies remote style changes from socket', async () => {
      wrapper.vm.setupSocketEvents()
      const remoteChange = {
        type: 'style_update',
        componentId: 'comp1',
        data: { color: 'blue' },
        tenantId: 'tenant-1'
      }
      ;(mockSocket.on as any).mock.calls.find((call: any[]) => call[0] === 'editor:change')[1](remoteChange)
      expect(mockEditor.getComponents().get('comp1').setStyle).toHaveBeenCalledWith({ color: 'blue' })
    })

    it('ignores remote changes from different tenant', async () => {
      wrapper.vm.setupSocketEvents()
      const remoteChange = {
        type: 'style_update',
        componentId: 'comp1',
        data: { color: 'blue' },
        tenantId: 'tenant-2' // different tenant
      }
      ;(mockSocket.on as any).mock.calls.find((call: any[]) => call[0] === 'editor:change')[1](remoteChange)
      expect(mockEditor.getComponents().get).not.toHaveBeenCalled()
    })

    it('handles socket connection error', async () => {
      vi.mocked(io).mockReturnValue({
        ...mockSocket,
        on: vi.fn((event: string, cb: (data: any) => void) => {
          if (event === 'connect_error') cb(new Error('Connection failed'))
        })
      } as any as Socket)
      await wrapper.vm.initializeSocket()
      expect(wrapper.vm.isSocketConnected).toBe(false)
      expect(wrapper.vm.socketError).toContain('Connection failed')
    })
  })

  describe('Phase 2 Features - Version Control Integration', () => {
    /**
     * @description Tests version control serialization, saving, restoring with conflict handling and Socket notifications
     */
    it('serializes page for version save', async () => {
      mockVersionControlService.serializePage.mockReturnValue({ html: '<div>', css: 'body {}' })
      const serialized = await wrapper.vm.serializePageForVersion()
      expect(mockVersionControlService.serializePage).toHaveBeenCalledWith(mockEditor, 'test-page-1', 'tenant-1')
      expect(serialized).toEqual({ html: '<div>', css: 'body {}' })
    })

    it('saves version and notifies via socket', async () => {
      mockVersionControlService.saveVersion.mockResolvedValue({ versionId: 'v1' })
      await wrapper.vm.saveVersion()
      expect(mockVersionControlService.saveVersion).toHaveBeenCalledWith(expect.objectContaining({
        pageId: 'test-page-1',
        tenantId: 'tenant-1'
      }))
      expect(mockSocket.emit).toHaveBeenCalledWith('version:saved', expect.objectContaining({
        tenantId: 'tenant-1',
        versionId: 'v1'
      }))
    })

    it('restores version and updates editor', async () => {
      const mockVersionData = { components: ['restored'], styles: ['style'] }
      mockVersionControlService.restoreVersion.mockResolvedValue(mockVersionData)
      await wrapper.vm.restoreVersion('v1')
      expect(mockVersionControlService.restoreVersion).toHaveBeenCalledWith('v1', 'tenant-1')
      expect(mockEditor.setComponents).toHaveBeenCalledWith(['restored'])
      expect(mockEditor.setStyle).toHaveBeenCalledWith(['style'])
    })

    it('computes diff between versions', async () => {
      const mockCurrent = { html: '<div>current</div>' }
      const mockPrevious = { html: '<div>previous</div>' }
      mockVersionControlService.computeDiff.mockReturnValue({ changes: ['added div'] })
      const diff = await wrapper.vm.computeVersionDiff(mockCurrent, mockPrevious)
      expect(mockVersionControlService.computeDiff).toHaveBeenCalledWith(mockCurrent, mockPrevious)
      expect(diff).toEqual({ changes: ['added div'] })
    })

    it('handles version restore conflicts', async () => {
      mockVersionControlService.restoreVersion.mockRejectedValue(new Error('Conflict detected'))
      await expect(wrapper.vm.restoreVersionWithConflict('v1')).rejects.toThrow('Conflict detected')
      expect(mockSocket.emit).toHaveBeenCalledWith('version:conflict', expect.any(Object))
    })

    it('ignores version operations from different tenant', async () => {
      mockVersionControlService.restoreVersion.mockImplementation((versionId: string, tenantId: string) => {
        if (tenantId !== 'tenant-1') throw new Error('Tenant mismatch')
        return { components: [] }
      })
      await expect(wrapper.vm.restoreVersion('v1')).rejects.toThrow('Tenant mismatch')
    })
  })

  describe('Integration Tests - Bridge and Sync', () => {
    /**
     * @description Tests integration with ComponentLibraryBridge for loading and registering components
     */
    it('integrates with ComponentLibraryBridge to load categories and components', async () => {
      const mockCategories = [{ id: 'hero', label: 'Hero' }]
      const mockComponents = [{ id: 'hero1', blockDefinition: { id: 'b-hero1' } }]
      vi.mocked(mockComponentLibraryBridge.getGrapeJSCategories).mockReturnValue(mockCategories)
      vi.mocked(mockComponentLibraryBridge.getRegisteredComponents).mockReturnValue(mockComponents)

      await wrapper.vm.loadSystemData()
      await wrapper.vm.registerComponentsWithEditor()

      expect(mockEditor.BlockManager.addCategory).toHaveBeenCalledWith('hero', expect.any(Object))
      expect(mockEditor.BlockManager.add).toHaveBeenCalledWith('b-hero1', expect.any(Object))
    })

    it('handles component selection event from editor', () => {
      const mockComponent = { get: vi.fn().mockReturnValue({ 'data-component-id': 'test-comp' }) }
      vi.mocked(mockComponentLibraryBridge.getRegisteredComponents).mockReturnValue([{ blockDefinition: { attributes: { 'data-component-id': 'test-comp' } } }])
      wrapper.vm.setupEditorEvents()
      ;(mockEditor.on as any).mock.calls[0][1](mockComponent)
      expect(wrapper.vm.selectedComponent).toEqual({ blockDefinition: { attributes: { 'data-component-id': 'test-comp' } } })
    })

    it('syncs updates via storage events', async () => {
      wrapper.vm.setupEditorEvents()
      ;(mockEditor.on as any).mock.calls.find((call: any[]) => call[0].includes('storage:start:store'))[1]()
      expect(wrapper.vm.isSaving).toBe(true)
      ;(mockEditor.on as any).mock.calls.find((call: any[]) => call[0].includes('storage:end:store'))[1]()
      expect(wrapper.vm.isSaving).toBe(false)
      expect(wrapper.emitted('save')).toBeTruthy()
    })
  })

  describe('E2E Simulation Tests - Editor Events and Flows', () => {
    it('loads page data and sets in editor', async () => {
      const mockPageData = { grapejsData: { components: ['comp1'], styles: ['style1'] } }
      mockedFetch.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue(mockPageData)
      } as any)
      await wrapper.vm.loadPageData('test-page-1')
      expect(mockedFetch).toHaveBeenCalledWith('/api/pages/test-page-1')
      expect(mockEditor.setComponents).toHaveBeenCalledWith(['comp1'])
      expect(mockEditor.setStyle).toHaveBeenCalledWith(['style1'])
      expect(wrapper.vm.currentPage).toEqual(mockPageData)
    })

    it('handles search and filter in block manager', () => {
      const mockCTAButton: Partial<CTAButton> = {
        id: '1',
        text: 'Click',
        url: '/test',
        style: 'primary',
        size: 'lg'
      }
      const mockComponent: Partial<Component> = {
        id: 'found',
        tenantId: 'tenant1',
        name: 'Test Component',
        slug: 'test-comp',
        category: 'hero',
        type: 'test',
        description: 'Test',
        config: {
          headline: 'Test Headline',
          audienceType: 'individual',
          ctaButtons: [mockCTAButton as CTAButton],
          layout: 'centered',
          textAlignment: 'center',
          backgroundType: 'color',
          backgroundColor: '#fff',
          contentPosition: 'center',
          headingLevel: 1
        } as HeroComponentConfig,
        metadata: {},
        version: '1.0.0',
        isActive: true,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }
      vi.mocked(mockComponentLibraryBridge.searchComponents).mockReturnValue([{ component: mockComponent as Component }])
      wrapper.vm.filterBlocks('search term')
      expect(mockComponentLibraryBridge.searchComponents).toHaveBeenCalledWith('search term')
      expect(mockEditor.BlockManager.remove).toHaveBeenCalled()
      expect(mockEditor.BlockManager.add).toHaveBeenCalled()
    })

    it('watches pageId changes and reloads data', async () => {
      const mockLoadPage = vi.spyOn(wrapper.vm, 'loadPageData')
      await wrapper.setProps({ pageId: 'new-page' })
      expect(mockLoadPage).toHaveBeenCalledWith('new-page')
    })

    it('handles tenant context in config', async () => {
      await wrapper.setProps({ tenantId: 'tenant-1' })
      await wrapper.vm.initializeEditor()
      expect(mockGrapesJSInit).toHaveBeenCalledWith(expect.objectContaining({
        storageManager: expect.objectContaining({
          options: expect.objectContaining({
            remote: expect.objectContaining({
              headers: expect.objectContaining({
                'X-CSRF-TOKEN': mockCSRFToken
              })
            })
          })
        })
      }))
    })

    it('error handling in loadPageData', async () => {
      mockedFetch.mockRejectedValueOnce(new Error('Load failed'))
      await expect(wrapper.vm.loadPageData('bad-page')).rejects.toThrow('Failed to load page data')
      expect(wrapper.vm.isError).toBe(true)
    })
  })

  describe('Error Cases and Edge Cases', () => {
    it('handles missing pageId gracefully', async () => {
      await wrapper.setProps({ pageId: undefined })
      await wrapper.vm.loadPageData(undefined as any)
      expect(mockEditor.setComponents).not.toHaveBeenCalled()
    })

    it('handles no editor instance', () => {
      ;(wrapper.vm as any).editor = null
      expect(() => wrapper.vm.getCurrentEditorData()).toThrow()
      expect(() => wrapper.vm.switchToDeviceMode('mobile')).not.toThrow()
    })

    it('publishes with error', async () => {
      mockedFetch.mockRejectedValueOnce(new Error('Publish failed'))
      await wrapper.vm.publishCurrentPage()
      expect(wrapper.vm.isError).toBe(true)
      expect(wrapper.emitted('error')).toBeTruthy()
    })
  })

  describe('Tenant Isolation Tests', () => {
    /**
     * @description Verifies tenant isolation in real-time updates and version control
     */
    it('broadcasts changes with tenantId for isolation', () => {
      wrapper.vm.setupEditorEvents()
      const mockComponent = { getId: () => 'comp1' }
      ;(mockEditor.on as any).mock.calls.find((call: any[]) => call[0] === 'component:update')[1](mockComponent)
      expect(mockSocket.emit).toHaveBeenCalledWith('editor:change', expect.objectContaining({
        tenantId: 'tenant-1'
      }))
    })

    it('loads brand config for specific tenant', async () => {
      mockBrandConfigService.getBrandConfig.mockResolvedValue({ colors: { primary: '#007bff' } })
      await wrapper.vm.loadBrandConfiguration('tenant-1')
      expect(mockBrandConfigService.getBrandConfig).toHaveBeenCalledWith('tenant-1')
      expect(wrapper.vm.brandColors).toEqual({ primary: '#007bff' })
    })

    it('falls back to default brand config on tenant load failure', async () => {
      mockBrandConfigService.getBrandConfig.mockRejectedValueOnce(new Error('Load failed')).mockResolvedValueOnce({ colors: { default: '#000' } })
      await wrapper.vm.loadBrandConfiguration('tenant-1')
      expect(mockBrandConfigService.getBrandConfig).toHaveBeenCalledWith('default')
      expect(wrapper.vm.brandColors).toEqual({ default: '#000' })
    })
  })
})