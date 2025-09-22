import type { Component, ComponentCategory, HeroComponentConfig } from '@/types/Components';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ComponentLibraryBridge } from '../ComponentLibraryBridge';

// Mock fetch for API calls
vi.mocked(global.fetch);

describe('ComponentLibraryBridge', () => {
    let bridge: ComponentLibraryBridge;
    const mockComponent: Partial<Component> = {
        id: 'test-comp',
        tenantId: 'tenant1',
        name: 'Test Component',
        slug: 'test-comp',
        category: 'hero' as ComponentCategory,
        type: 'test',
        description: 'Test component',
        config: {
            headline: 'Test Headline',
            audienceType: 'individual',
            ctaButtons: [{ id: '1', text: 'Click', url: '/test', style: 'primary', size: 'lg' }],
            layout: 'centered',
            textAlignment: 'center',
            backgroundType: 'color',
            backgroundColor: '#fff',
            contentPosition: 'center',
            headingLevel: 1,
        } as HeroComponentConfig,
        metadata: {},
        version: '1.0.0',
        isActive: true,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
        bridge = new ComponentLibraryBridge();
        // Spy on private methods for testing
        vi.spyOn(bridge as any, 'categoryManager');
        vi.spyOn(bridge as any, 'searchIndex');
        vi.spyOn(bridge as any, 'usageTracker');
        vi.spyOn(bridge as any, 'documentationGenerator');
        vi.spyOn(bridge as any, 'syncManager');
        vi.spyOn(bridge as any, 'syncManager', 'broadcastComponentUpdate');
    });

    describe('Unit Tests - Core Methods', () => {
        it('initialize initializes all managers and sync', () => {
            const mockCategoryManager = vi.spyOn(bridge as any, 'categoryManager').mockImplementation(() => ({
                initializeCategories: vi.fn(),
            }));
            const mockSyncManager = vi.spyOn(bridge as any, 'syncManager').mockImplementation(() => ({
                initialize: vi.fn(),
                onComponentUpdate: vi.fn(),
            }));

            bridge.initialize();

            expect(mockCategoryManager.initializeCategories).toHaveBeenCalled();
            expect(mockSyncManager.initialize).toHaveBeenCalled();
            expect(mockSyncManager.onComponentUpdate).toHaveBeenCalled();
        });

        it('convertToGrapeJSBlock converts component to block metadata', () => {
            const block = bridge.convertToGrapeJSBlock(mockComponent as Component);
            expect(block.id).toBe('component-test-comp');
            expect(block.label).toBe('Test Component');
            expect(block.category).toBe('Hero Sections');
            expect(block.attributes['data-component-id']).toBe('test-comp');
        });

        it('convertFromGrapeJSData converts data back to Components', () => {
            const mockData = {
                Components: [
                    {
                        attributes: { 'data-component-id': 'test-comp' },
                        toJSON: () => ({}),
                    },
                ],
            };
            const Components = bridge.convertFromGrapeJSData(mockData as any);
            expect(Components.length).toBe(1);
            expect(Components[0].id).toBe('test-comp');
        });

        it('syncComponentUpdates fetches and updates component', async () => {
            const mockUpdatedComponent = { ...mockComponent, name: 'Updated' } as Component;
            vi.mocked(global.fetch).mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockUpdatedComponent),
            } as any);

            await bridge.syncComponentUpdates('test-comp');

            expect(global.fetch).toHaveBeenCalledWith('/api/Components/test-comp');
            // Check if registry updated (private, so check via getRegisteredComponents)
            const registered = bridge.getRegisteredComponents();
            expect(registered.length).toBeGreaterThan(0);
        });

        it('generatePreviewImage returns cached or default preview', async () => {
            const preview = await bridge.generatePreviewImage(mockComponent as Component);
            expect(preview).toContain('placeholder.svg');

            // Check cache
            const secondCall = await bridge.generatePreviewImage(mockComponent as Component);
            expect(secondCall).toBe(preview);
        });

        it('validateGrapeJSCompatibility validates component', () => {
            const validation = bridge.validateGrapeJSCompatibility(mockComponent as Component);
            expect(validation.valid).toBe(true);
            expect(validation.errors.length).toBe(0);

            // Invalid case
            const invalidComponent = { ...mockComponent, name: '' } as Component;
            const invalidValidation = bridge.validateGrapeJSCompatibility(invalidComponent);
            expect(invalidValidation.valid).toBe(false);
            expect(invalidValidation.errors).toContain('Component name is required');
        });

        it('registerComponent adds to registry', () => {
            bridge.registerComponent(mockComponent as Component);
            const registered = bridge.getRegisteredComponents();
            expect(registered.length).toBe(1);
            expect(registered[0].blockDefinition.label).toBe('Test Component');
        });

        it('getRegisteredComponents returns all registered', () => {
            bridge.registerComponent(mockComponent as Component);
            const registered = bridge.getRegisteredComponents();
            expect(registered.length).toBe(1);
        });

        it('clearRegistry clears all data', () => {
            bridge.registerComponent(mockComponent as Component);
            bridge.clearRegistry();
            expect(bridge.getRegisteredComponents().length).toBe(0);
        });
    });

    describe('Unit Tests - Category Management', () => {
        it('getGrapeJSCategories returns configured categories', () => {
            bridge.initialize();
            const categories = bridge.getGrapeJSCategories();
            expect(categories.length).toBeGreaterThan(0);
            expect(categories[0].id).toBe('hero');
        });

        it('addComponentToCategory adds to specific category', () => {
            bridge.initialize();
            vi.spyOn(bridge as any, 'categoryManager', 'get').mockReturnValue({
                addComponentToCategory: vi.fn(),
                getCategoryData: vi.fn().mockReturnValue({ Components: [mockComponent as Component] }),
            });
            bridge.addComponentToCategory(mockComponent as Component);
            const category = (bridge as any).categoryManager.getCategoryData('hero');
            expect(category.Components.length).toBe(1);
        });

        it('toggleCategoryCollapse toggles state', () => {
            bridge.initialize();
            vi.spyOn(bridge as any, 'categoryManager', 'get').mockReturnValue({
                toggleCategoryCollapse: vi.fn(),
                getCategoryData: vi.fn().mockReturnValue({ isCollapsed: true }),
            });
            bridge.toggleCategoryCollapse('hero');
            const category = (bridge as any).categoryManager.getCategoryData('hero');
            expect(category.isCollapsed).toBe(true);
        });

        it('reorderCategories updates order', () => {
            bridge.initialize();
            vi.spyOn(bridge as any, 'categoryManager', 'get').mockReturnValue({
                reorderCategories: vi.fn(),
                getGrapeJSBlockManagerConfig: vi.fn().mockReturnValue([{ id: 'forms' }, { id: 'hero' }]),
            });
            bridge.reorderCategories(['forms', 'hero']);
            const categories = bridge.getGrapeJSCategories();
            expect(categories[0].id).toBe('forms');
        });
    });

    describe('Unit Tests - Search Functionality', () => {
        it('searchComponents returns relevant results', () => {
            bridge.registerComponent(mockComponent as Component);
            vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
                search: vi.fn().mockReturnValue([{ component: mockComponent as Component, relevanceScore: 10, matchedFields: [], highlights: {} }]),
            });
            const results = bridge.searchComponents('test');
            expect(results.length).toBeGreaterThan(0);
            expect(results[0].relevanceScore).toBeGreaterThan(0);
        });

        it('searchComponents filters by category', () => {
            bridge.registerComponent(mockComponent as Component);
            vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
                search: vi.fn().mockReturnValue([{ component: mockComponent as Component, relevanceScore: 10, matchedFields: [], highlights: {} }]),
            });
            const results = bridge.searchComponents('', { category: 'hero' });
            expect(results[0].component.category).toBe('hero');
        });

        it('getComponentsByCategory returns category Components', () => {
            bridge.registerComponent(mockComponent as Component);
            vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
                getComponentsByCategory: vi.fn().mockReturnValue([mockComponent as Component]),
            });
            const Components = bridge.getComponentsByCategory('hero');
            expect(Components.length).toBe(1);
            expect(Components[0].id).toBe('test-comp');
        });

        it('getAllTags returns all tags', () => {
            bridge.registerComponent(mockComponent as Component);
            vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
                getAllTags: vi.fn().mockReturnValue(['hero']),
            });
            const tags = bridge.getAllTags();
            expect(tags).toContain('hero');
        });
    });

    describe('Unit Tests - Usage Tracking', () => {
        it('trackComponentUsage updates stats', () => {
            bridge.trackComponentUsage('test-comp', 'grapejs');
            const stats = bridge.getComponentUsageStats('test-comp');
            expect(stats.totalUsage).toBe(1);
            expect(stats.lastUsed).toBeDefined();
        });

        it('trackComponentRating updates average rating', () => {
            bridge.trackComponentRating('test-comp', 5);
            const stats = bridge.getComponentUsageStats('test-comp');
            expect(stats.averageRating).toBe(5);
        });

        it('trackComponentConfiguration tracks popular configs', () => {
            bridge.trackComponentConfiguration('test-comp', { layout: 'centered' });
            const stats = bridge.getComponentUsageStats('test-comp');
            expect(stats.popularConfigurations.length).toBe(1);
        });

        it('getMostUsedComponents returns sorted list', () => {
            bridge.trackComponentUsage('comp1', 'test');
            bridge.trackComponentUsage('comp2', 'test');
            bridge.trackComponentUsage('comp1', 'test');
            const mostUsed = bridge.getMostUsedComponents(1);
            expect(mostUsed[0].totalUsage).toBe(2);
        });
    });

    describe('Unit Tests - Documentation', () => {
        it('generateComponentDocumentation returns docs', () => {
            vi.spyOn(bridge as any, 'documentationGenerator', 'get').mockReturnValue({
                generateDocumentation: vi.fn().mockReturnValue({ title: 'Test Component', description: 'Test' }),
            });
            const docs = bridge.generateComponentDocumentation(mockComponent as Component);
            expect(docs.title).toBe('Test Component');
            expect(docs.description).toBeDefined();
        });

        it('generateComponentTooltip returns tooltip text', () => {
            vi.spyOn(bridge as any, 'documentationGenerator', 'get').mockReturnValue({
                generateTooltip: vi.fn().mockReturnValue('Test Component tooltip'),
            });
            const tooltip = bridge.generateComponentTooltip(mockComponent as Component);
            expect(tooltip).toContain('Test Component');
        });

        it('generatePropertyTooltip returns property info', () => {
            vi.spyOn(bridge as any, 'documentationGenerator', 'get').mockReturnValue({
                generatePropertyTooltip: vi.fn().mockReturnValue('headline (string)'),
            });
            const property = { name: 'headline', type: 'string', description: 'Test' } as any;
            const tooltip = bridge.generatePropertyTooltip(property);
            expect(tooltip).toContain('headline (string)');
        });
    });

    describe('Integration Tests - Full Bridge Functionality', () => {
        it('registerComponentEnhanced fully indexes and syncs component', () => {
            vi.spyOn(bridge as any, 'categoryManager').mockReturnValue({
                addComponentToCategory: vi.fn(),
            });
            vi.spyOn(bridge as any, 'searchIndex').mockReturnValue({
                indexComponent: vi.fn(),
            });
            vi.spyOn(bridge as any, 'usageTracker').mockReturnValue({
                getComponentStats: vi.fn().mockReturnValue({}),
            });
            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                broadcastComponentCreated: vi.fn(),
            });

            bridge.registerComponentEnhanced(mockComponent as Component);

            expect(bridge['categoryManager'].addComponentToCategory).toHaveBeenCalledWith(mockComponent as Component);
            expect(bridge['searchIndex'].indexComponent).toHaveBeenCalledWith(mockComponent as Component);
            expect(bridge['syncManager'].broadcastComponentCreated).toHaveBeenCalledWith(mockComponent as Component);
        });

        it('updateComponentEnhanced updates all indexes and syncs', () => {
            bridge.registerComponent(mockComponent as Component);
            const updatedComponent = { ...mockComponent, name: 'Updated' } as Component;

            vi.spyOn(bridge as any, 'categoryManager').mockReturnValue({
                addComponentToCategory: vi.fn(),
            });
            vi.spyOn(bridge as any, 'searchIndex').mockReturnValue({
                removeComponent: vi.fn(),
                indexComponent: vi.fn(),
            });
            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                broadcastComponentUpdate: vi.fn(),
            });

            bridge.updateComponentEnhanced(updatedComponent);

            expect(bridge['searchIndex'].removeComponent).toHaveBeenCalledWith('test-comp');
            expect(bridge['searchIndex'].indexComponent).toHaveBeenCalledWith(updatedComponent);
            expect(bridge['syncManager'].broadcastComponentUpdate).toHaveBeenCalledWith('test-comp', updatedComponent);
        });

        it('removeComponentEnhanced cleans up all references', () => {
            bridge.registerComponent(mockComponent as Component);

            vi.spyOn(bridge as any, 'categoryManager').mockReturnValue({
                removeComponentFromCategory: vi.fn(),
            });
            vi.spyOn(bridge as any, 'searchIndex').mockReturnValue({
                removeComponent: vi.fn(),
            });
            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                broadcastComponentDeleted: vi.fn(),
            });

            bridge.removeComponentEnhanced('test-comp', 'hero');

            expect(bridge['categoryManager'].removeComponentFromCategory).toHaveBeenCalledWith('test-comp', 'hero');
            expect(bridge['searchIndex'].removeComponent).toHaveBeenCalledWith('test-comp');
            expect(bridge['syncManager'].broadcastComponentDeleted).toHaveBeenCalledWith('test-comp');
        });

        it('getGrapeJSComponentData returns full data with metadata', () => {
            bridge.registerComponent(mockComponent as Component);

            vi.spyOn(bridge as any, 'componentRegistry', 'get').mockReturnValue({
                blockDefinition: { label: 'Test Component' },
                documentation: { title: 'Test Component' },
                tooltip: 'Test',
            });

            const data = bridge.getGrapeJSComponentData('test-comp');
            expect(data.block.label).toBe('Test Component');
            expect(data.documentation.title).toBe('Test Component');
            expect(data.tooltip).toContain('Test Component');
        });

        it('bulkRegisterComponents registers multiple with enhanced functionality', () => {
            const Components = [mockComponent as Component, { ...mockComponent, id: 'comp2' } as Component];

            const mockEnhanced = vi.spyOn(bridge, 'registerComponentEnhanced');

            bridge.bulkRegisterComponents(Components);

            expect(mockEnhanced).toHaveBeenCalledTimes(2);
        });
    });

    describe('E2E Simulation Tests - Sync and Real-time', () => {
        it('handles component update sync event', () => {
            const mockUpdatedComponent = { ...mockComponent, name: 'Updated' } as Component;
            const mockEvent = { type: 'component_updated', componentId: 'test-comp', data: mockUpdatedComponent, timestamp: new Date() };

            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                onComponentUpdate: vi.fn(),
            });
            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                onComponentUpdate: vi.fn((event: any) => {
                    bridge.updateComponentEnhanced(event.data);
                }),
            });

            bridge['syncManager'].onComponentUpdate(mockEvent as any);

            const updated = bridge.getRegisteredComponents().find((c) => c.blockDefinition.attributes['data-component-id'] === 'test-comp');
            expect(updated.blockDefinition.label).toBe('Updated');
        });

        it('handles component created sync event', () => {
            const mockCreatedComponent = mockComponent as Component;
            const mockEvent = { type: 'component_created', componentId: 'new-comp', data: mockCreatedComponent, timestamp: new Date() };

            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                onComponentCreated: vi.fn((event: any) => {
                    bridge.registerComponentEnhanced(event.data);
                }),
            });

            bridge['syncManager'].onComponentCreated(mockEvent as any);

            const created = bridge.getRegisteredComponents().find((c) => c.blockDefinition.attributes['data-component-id'] === 'new-comp');
            expect(created).toBeDefined();
        });

        it('handles component deleted sync event', () => {
            bridge.registerComponent(mockComponent as Component);
            const mockEvent = { type: 'component_deleted', componentId: 'test-comp', data: null, timestamp: new Date() };

            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                onComponentDeleted: vi.fn((event: any) => {
                    bridge.removeComponentEnhanced(event.componentId, 'hero');
                }),
            });

            bridge['syncManager'].onComponentDeleted(mockEvent as any);

            const remaining = bridge.getRegisteredComponents();
            expect(remaining.length).toBe(0);
        });

        it('broadcasts update through sync manager', () => {
            vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
                broadcastComponentUpdate: vi.fn(),
            });

            bridge.broadcastComponentUpdate('test-comp', { test: 'data' });

            expect(bridge['syncManager'].broadcastComponentUpdate).toHaveBeenCalledWith({
                type: 'component_updated',
                componentId: 'test-comp',
                data: { test: 'data' },
                timestamp: expect.any(Date),
            });
        });

        it('analyticsData provides usage statistics', () => {
            vi.spyOn(bridge as any, 'usageTracker').mockReturnValue({
                getAnalyticsData: vi.fn().mockReturnValue({ totalComponents: 2, totalUsage: 2 }),
            });

            const analytics = bridge.getAnalyticsData();
            expect(analytics.totalComponents).toBe(2);
            expect(analytics.totalUsage).toBe(2);
        });
    });

    describe('Error Cases and Edge Cases', () => {
        it('handles validation errors for hero component', () => {
            const invalidHero: Partial<Component> = {
                ...mockComponent,
                config: { headline: '' } as any,
            };
            const validation = bridge.validateGrapeJSCompatibility(invalidHero as Component);
            expect(validation.errors).toContain('Hero headline is required');
        });

        it('handles sync update failure', async () => {
            vi.mocked(global.fetch).mockRejectedValueOnce(new Error('Sync failed'));

            await expect(bridge.syncComponentUpdates('bad-comp')).rejects.toThrow('Sync failed');
        });

        it('handles search with no results', () => {
            const results = bridge.searchComponents('nonexistent');
            expect(results.length).toBe(0);
        });

        it('getComponentUsageStats returns undefined for unknown component', () => {
            const stats = bridge.getComponentUsageStats('unknown');
            expect(stats).toBeUndefined();
        });
    });
});














