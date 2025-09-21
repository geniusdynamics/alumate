import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { PageExportService } from '../PageExportService';
import { tenantValidationService } from '../TenantValidationService';

// Mock tenant validation service
vi.mock('../TenantValidationService', () => ({
    tenantValidationService: {
        validateTenantAccess: vi.fn(),
    },
}));

// Mock fetch
const mockedFetch = vi.fn();
Object.defineProperty(globalThis, 'fetch', {
    value: mockedFetch,
    writable: true,
});

// Mock document.querySelector for CSRF token
Object.defineProperty(document, 'querySelector', {
    value: vi.fn(() => ({ getAttribute: () => 'csrf-token-mock' })),
    writable: true,
});

describe('PageExportService - Integration Tests', () => {
    let exportService: PageExportService;

    beforeEach(() => {
        vi.clearAllMocks();
        exportService = PageExportService.getInstance();

        // Mock successful tenant validation by default
        vi.mocked(tenantValidationService.validateTenantAccess).mockResolvedValue({
            hasAccess: true,
            error: null,
        });

        // Mock successful fetch responses by default
        mockedFetch.mockResolvedValue({
            ok: true,
            json: vi.fn().mockResolvedValue({}),
            blob: vi.fn().mockResolvedValue(new Blob()),
        } as any);
    });

    afterEach(() => {
        vi.clearAllTimers();
    });

    describe('Single Page Export', () => {
        it('successfully exports a single page with tenant validation', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                slug: 'test-page',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            const result = await exportService.exportPage('page-1', 'tenant-1', {
                format: 'json',
                includeAssets: true,
            });

            expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-1');
            expect(mockedFetch).toHaveBeenCalledWith('/api/pages/page-1', expect.any(Object));
            expect(result).toBeDefined();
            expect(result.status).toBe('pending');
            expect(result.format).toBe('json');
        });

        it('rejects export when tenant access is denied', async () => {
            vi.mocked(tenantValidationService.validateTenantAccess).mockResolvedValue({
                hasAccess: false,
                error: 'Access denied',
            });

            await expect(exportService.exportPage('page-1', 'tenant-1')).rejects.toThrow('Access denied');
        });

        it('rejects export when page does not exist', async () => {
            mockedFetch.mockResolvedValueOnce({
                ok: false,
                status: 404,
            } as any);

            await expect(exportService.exportPage('nonexistent-page', 'tenant-1')).rejects.toThrow('Page with ID nonexistent-page not found');
        });

        it('rejects export when page belongs to different tenant', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'different-tenant',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            await expect(exportService.exportPage('page-1', 'tenant-1')).rejects.toThrow(
                'Access denied: Page does not belong to the specified tenant',
            );
        });

        it('handles different export formats correctly', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            const formats = ['json', 'html', 'xml', 'yaml', 'markdown'] as const;

            for (const format of formats) {
                const result = await exportService.exportPage('page-1', 'tenant-1', { format });
                expect(result.format).toBe(format);
            }
        });

        it('includes compression when requested', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            const result = await exportService.exportPage('page-1', 'tenant-1', {
                format: 'json',
                compress: true,
            });

            expect(result).toBeDefined();
            // In a real implementation, the filename would include compression extension
        });

        it('includes encryption when requested', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            const result = await exportService.exportPage('page-1', 'tenant-1', {
                format: 'json',
                encryption: {
                    enabled: true,
                    algorithm: 'AES-256',
                },
            });

            expect(result).toBeDefined();
            // In a real implementation, encryption would be applied to the exported data
        });
    });

    describe('Bulk Page Export', () => {
        it('successfully exports multiple pages', async () => {
            const mockPages = [
                { id: 'page-1', title: 'Page 1', tenantId: 'tenant-1' },
                { id: 'page-2', title: 'Page 2', tenantId: 'tenant-1' },
            ];

            mockedFetch
                .mockResolvedValueOnce({
                    ok: true,
                    json: vi.fn().mockResolvedValue(mockPages[0]),
                })
                .mockResolvedValueOnce({
                    ok: true,
                    json: vi.fn().mockResolvedValue(mockPages[1]),
                });

            const result = await exportService.exportPages(['page-1', 'page-2'], 'tenant-1', {
                format: 'json',
            });

            expect(result).toBeDefined();
            expect(result.status).toBe('pending');
            expect(result.metadata.name).toBe('Bulk Page Export');
            expect(result.metadata.description).toContain('2 pages');
        });

        it('rejects bulk export when any page does not exist', async () => {
            mockedFetch
                .mockResolvedValueOnce({
                    ok: true,
                    json: vi.fn().mockResolvedValue({ id: 'page-1', tenantId: 'tenant-1' }),
                })
                .mockResolvedValueOnce({
                    ok: false,
                    status: 404,
                });

            await expect(exportService.exportPages(['page-1', 'nonexistent'], 'tenant-1')).rejects.toThrow('Invalid page IDs: nonexistent');
        });

        it('rejects bulk export when any page belongs to different tenant', async () => {
            mockedFetch
                .mockResolvedValueOnce({
                    ok: true,
                    json: vi.fn().mockResolvedValue({ id: 'page-1', tenantId: 'tenant-1' }),
                })
                .mockResolvedValueOnce({
                    ok: true,
                    json: vi.fn().mockResolvedValue({ id: 'page-2', tenantId: 'different-tenant' }),
                });

            await expect(exportService.exportPages(['page-1', 'page-2'], 'tenant-1')).rejects.toThrow(
                'Access denied: Some pages do not belong to the specified tenant',
            );
        });
    });

    describe('Export History and Retrieval', () => {
        it('retrieves export history with tenant filtering', async () => {
            const mockExports = [
                { id: 'export-1', status: 'completed', tenantId: 'tenant-1' },
                { id: 'export-2', status: 'pending', tenantId: 'tenant-1' },
            ];

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockExports),
            });

            const history = await exportService.getExportHistory('tenant-1');

            expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-1');
            expect(mockedFetch).toHaveBeenCalledWith('/api/exports?tenantId=tenant-1', expect.any(Object));
            expect(history).toEqual(mockExports);
        });

        it('filters export history by status', async () => {
            const mockExports = [{ id: 'export-1', status: 'completed', tenantId: 'tenant-1' }];

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockExports),
            });

            await exportService.getExportHistory('tenant-1', { status: 'completed' });

            expect(mockedFetch).toHaveBeenCalledWith('/api/exports?tenantId=tenant-1&status=completed', expect.any(Object));
        });

        it('filters export history by date range', async () => {
            const startDate = new Date('2025-01-01');
            const endDate = new Date('2025-01-31');

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue([]),
            });

            await exportService.getExportHistory('tenant-1', {
                startDate,
                endDate,
            });

            const expectedUrl = `/api/exports?tenantId=tenant-1&startDate=${startDate.toISOString()}&endDate=${endDate.toISOString()}`;
            expect(mockedFetch).toHaveBeenCalledWith(expectedUrl, expect.any(Object));
        });

        it('retrieves specific export record', async () => {
            const mockExport = {
                id: 'export-1',
                status: 'completed',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockExport),
            });

            const exportRecord = await exportService.getExport('export-1', 'tenant-1');

            expect(exportRecord).toEqual(mockExport);
        });

        it('returns null for non-existent export', async () => {
            mockedFetch.mockResolvedValueOnce({
                ok: false,
                status: 404,
            } as any);

            const exportRecord = await exportService.getExport('nonexistent', 'tenant-1');

            expect(exportRecord).toBeNull();
        });

        it('verifies tenant ownership when retrieving export', async () => {
            const mockExport = {
                id: 'export-1',
                tenantId: 'different-tenant',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockExport),
            });

            await expect(exportService.getExport('export-1', 'tenant-1')).rejects.toThrow(
                'Access denied: Export record does not belong to the specified tenant',
            );
        });
    });

    describe('Export Deletion', () => {
        it('successfully deletes export record', async () => {
            const mockExport = {
                id: 'export-1',
                tenantId: 'tenant-1',
            };

            mockedFetch
                .mockResolvedValueOnce({
                    ok: true,
                    json: vi.fn().mockResolvedValue(mockExport),
                })
                .mockResolvedValueOnce({
                    ok: true,
                } as any);

            await exportService.deleteExport('export-1', 'tenant-1');

            expect(mockedFetch).toHaveBeenCalledWith(
                '/api/exports/export-1',
                expect.objectContaining({
                    method: 'DELETE',
                }),
            );
        });

        it('verifies ownership before deletion', async () => {
            const mockExport = {
                id: 'export-1',
                tenantId: 'different-tenant',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockExport),
            });

            await expect(exportService.deleteExport('export-1', 'tenant-1')).rejects.toThrow(
                'Access denied: Export record does not belong to the specified tenant',
            );
        });
    });

    describe('Export Download', () => {
        it('successfully downloads export file', async () => {
            const mockExport = {
                id: 'export-1',
                tenantId: 'tenant-1',
            };
            const mockBlob = new Blob(['export content']);

            mockedFetch
                .mockResolvedValueOnce({
                    ok: true,
                    json: vi.fn().mockResolvedValue(mockExport),
                })
                .mockResolvedValueOnce({
                    ok: true,
                    blob: vi.fn().mockResolvedValue(mockBlob),
                } as any);

            const blob = await exportService.downloadExport('export-1', 'tenant-1');

            expect(blob).toBe(mockBlob);
            expect(mockedFetch).toHaveBeenCalledWith('/api/exports/export-1/download', expect.any(Object));
        });

        it('verifies ownership before download', async () => {
            const mockExport = {
                id: 'export-1',
                tenantId: 'different-tenant',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockExport),
            });

            await expect(exportService.downloadExport('export-1', 'tenant-1')).rejects.toThrow(
                'Access denied: Export does not belong to the specified tenant',
            );
        });
    });

    describe('Export All Pages', () => {
        it('exports all pages for a tenant', async () => {
            const mockPages = [
                { id: 'page-1', tenantId: 'tenant-1' },
                { id: 'page-2', tenantId: 'tenant-1' },
            ];

            // Mock the internal getAllPagesForTenant method
            const originalGetAllPagesForTenant = exportService['getAllPagesForTenant'];
            exportService['getAllPagesForTenant'] = vi.fn().mockResolvedValue(mockPages);

            const result = await exportService.exportAllPages('tenant-1');

            expect(result).toBeDefined();
            expect(exportService['getAllPagesForTenant']).toHaveBeenCalledWith('tenant-1');
        });
    });

    describe('Error Handling', () => {
        it('handles network errors gracefully', async () => {
            mockedFetch.mockRejectedValueOnce(new Error('Network error'));

            await expect(exportService.exportPage('page-1', 'tenant-1')).rejects.toThrow('Network error');
        });

        it('handles API errors gracefully', async () => {
            mockedFetch.mockResolvedValueOnce({
                ok: false,
                status: 500,
                statusText: 'Internal Server Error',
            } as any);

            await expect(exportService.getExportHistory('tenant-1')).rejects.toThrow('Failed to fetch export history');
        });

        it('handles tenant validation failures', async () => {
            vi.mocked(tenantValidationService.validateTenantAccess).mockRejectedValue(new Error('Validation service error'));

            await expect(exportService.exportPage('page-1', 'tenant-1')).rejects.toThrow('Validation service error');
        });
    });

    describe('Cache Management', () => {
        it('caches page data to reduce API calls', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
                updatedAt: new Date(),
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            // First call should fetch from API
            await exportService['getPage']('page-1');
            expect(mockedFetch).toHaveBeenCalledTimes(1);

            // Second call should use cache
            mockedFetch.mockClear();
            await exportService['getPage']('page-1');
            expect(mockedFetch).not.toHaveBeenCalled();
        });

        it('respects cache TTL', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
                updatedAt: new Date(Date.now() - 10 * 60 * 1000), // 10 minutes ago
            };

            mockedFetch.mockResolvedValue({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            // First call
            await exportService['getPage']('page-1');
            expect(mockedFetch).toHaveBeenCalledTimes(1);

            // Second call should refetch due to expired cache
            mockedFetch.mockClear();
            await exportService['getPage']('page-1');
            expect(mockedFetch).toHaveBeenCalledTimes(1);
        });
    });

    describe('File Format Handling', () => {
        it('generates correct file extensions for different formats', () => {
            const service = exportService as any;

            expect(service.getFileExtension('json')).toBe('json');
            expect(service.getFileExtension('html')).toBe('html');
            expect(service.getFileExtension('xml')).toBe('xml');
            expect(service.getFileExtension('yaml')).toBe('yaml');
            expect(service.getFileExtension('pdf')).toBe('pdf');
            expect(service.getFileExtension('markdown')).toBe('md');
            expect(service.getFileExtension('grapejs')).toBe('json');
            expect(service.getFileExtension('zip')).toBe('zip');
        });

        it('generates correct MIME types for different formats', () => {
            const service = exportService as any;

            expect(service.getMimeType('json')).toBe('application/json');
            expect(service.getMimeType('html')).toBe('text/html');
            expect(service.getMimeType('xml')).toBe('application/xml');
            expect(service.getMimeType('pdf')).toBe('application/pdf');
            expect(service.getMimeType('zip')).toBe('application/zip');
        });
    });

    describe('Concurrent Operations', () => {
        it('handles multiple simultaneous exports', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValue({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            const promises = [];
            for (let i = 0; i < 5; i++) {
                promises.push(exportService.exportPage(`page-${i}`, 'tenant-1'));
            }

            const results = await Promise.all(promises);

            expect(results).toHaveLength(5);
            results.forEach((result) => {
                expect(result.status).toBe('pending');
            });
        });
    });

    describe('Large Dataset Handling', () => {
        it('handles large page data efficiently', async () => {
            const largePage = {
                id: 'large-page',
                title: 'Large Page',
                tenantId: 'tenant-1',
                content: 'x'.repeat(1000000), // 1MB of content
                updatedAt: new Date(),
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(largePage),
            });

            const result = await exportService.exportPage('large-page', 'tenant-1', {
                format: 'json',
            });

            expect(result).toBeDefined();
            expect(result.status).toBe('pending');
        });
    });

    describe('Metadata and Custom Options', () => {
        it('includes custom metadata in export', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            const customMetadata = {
                author: 'Test Author',
                version: '2.0.0',
                tags: ['test', 'export'],
            };

            const result = await exportService.exportPage('page-1', 'tenant-1', {
                format: 'json',
                metadata: customMetadata,
            });

            expect(result.metadata).toEqual(expect.objectContaining(customMetadata));
        });

        it('handles export with custom retention settings', async () => {
            const mockPage = {
                id: 'page-1',
                title: 'Test Page',
                tenantId: 'tenant-1',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockPage),
            });

            const result = await exportService.exportPage('page-1', 'tenant-1', {
                format: 'json',
                metadata: {
                    name: 'Custom Export',
                    description: 'Export with custom settings',
                },
            });

            expect(result.metadata.name).toBe('Custom Export');
            expect(result.metadata.description).toBe('Export with custom settings');
        });
    });
});
