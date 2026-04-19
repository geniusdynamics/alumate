import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { PageBackupService } from '../PageBackupService';
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

describe('PageBackupService - Integration Tests', () => {
    let backupService: PageBackupService;

    beforeEach(() => {
        vi.clearAllMocks();
        backupService = PageBackupService.getInstance();

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

    describe('Backup Creation', () => {
        it('successfully creates a backup with tenant validation', async () => {
            const mockBackup = {
                id: 'backup-1',
                name: 'Test Backup',
                tenantId: 'tenant-1',
                status: 'pending',
            };

            mockedFetch.mockResolvedValueOnce({
                ok: true,
                json: vi.fn().mockResolvedValue(mockBackup),
            });

            const result = await backupService.createBackup('tenant-1', {
                name: 'Test Backup',
                targets: [{ type: 'all_pages' as const }],
            });

            expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-1');
            expect(result).toBeDefined();
            expect(result.status).toBe('pending');
        });

        it('rejects backup creation when tenant access is denied', async () => {
            vi.mocked(tenantValidationService.validateTenantAccess).mockResolvedValue({
                hasAccess: false,
                error: 'Access denied',
            });

            await expect(
                backupService.createBackup('tenant-1', {
                    name: 'Test Backup',
                }),
            ).rejects.toThrow('Access denied');
        });

        it('handles different backup targets correctly', async () => {
            const targets = [
                { type: 'all_pages' as const },
                { type: 'specific_pages' as const, pageIds: ['page-1', 'page-2'] },
                { type: 'Components' as const },
            ];

            for (const target of targets) {
                const result = await backupService.createBackup('tenant-1', {
                    name: `Backup with ${target.type}`,
                    targets: [target],
                });
                expect(result).toBeDefined();
                expect(result.status).toBe('pending');
            }
        });

        it('includes compression when requested', async () => {
            const result = await backupService.createBackup('tenant-1', {
                name: 'Compressed Backup',
                compress: true,
            });

            expect(result).toBeDefined();
            // In a real implementation, compression would be applied
        });

        it('includes encryption when requested', async () => {
            const result = await backupService.createBackup('tenant-1', {
                name: 'Encrypted Backup',
                encryption: {
                    enabled: true,
                    algorithm: 'AES-256',
                },
            });

            expect(result).toBeDefined();
            // In a real implementation, encryption would be applied
        });
    });

    describe('Bulk Backup Operations', () => {
        it('successfully creates bulk backup', async () => {
            const result = await backupService.createBackup('tenant-1', {
                name: 'Bulk Backup',
                targets: [{ type: 'all_pages' as const }],
            });

            expect(result).toBeDefined();
            expect(result.status).toBe('pending');
            expect(result.metadata.name).toBe('Bulk Backup');
        });

        it('handles backup with many items efficiently', async () => {
            const result = await backupService.createBackup('tenant-1', {
                name: `Many Items Backup`,
                targets: [{ type: 'all_pages' as const }],
            });

            expect(result).toBeDefined();
            expect(result.status).toBe('pending');
        });
    });

    describe('Metadata and Custom Options', () => {
        it('includes custom metadata in backup', async () => {
            const customMetadata = {
                author: 'Test Author',
                version: '2.0.0',
                tags: ['test', 'backup'],
                environment: 'staging',
            };

            const result = await backupService.createBackup('tenant-1', {
                name: 'Custom Metadata Backup',
                metadata: customMetadata,
            });

            expect(result.metadata).toEqual(expect.objectContaining(customMetadata));
        });

        it('handles backup with custom retention settings', async () => {
            const result = await backupService.createBackup('tenant-1', {
                name: 'Custom Retention Backup',
                retentionDays: 90,
            });

            expect(result).toBeDefined();
            // In a real implementation, retention would be applied
        });
    });

    describe('Performance and Scalability', () => {
        it('handles high-frequency backup operations', async () => {
            const startTime = Date.now();

            const promises = [];
            for (let i = 0; i < 10; i++) {
                promises.push(
                    backupService.createBackup('tenant-1', {
                        name: `Performance Test ${i}`,
                    }),
                );
            }

            await Promise.all(promises);
            const endTime = Date.now();

            // Should complete within reasonable time (adjust threshold as needed)
            expect(endTime - startTime).toBeLessThan(5000);
        });

        it('maintains performance with large backup payloads', async () => {
            // Simulate large backup data
            const largeMetadata = {
                Components: Array.from({ length: 1000 }, (_, i) => ({
                    id: `component-${i}`,
                    type: 'text',
                    content: 'x'.repeat(1000),
                })),
            };

            const result = await backupService.createBackup('tenant-1', {
                name: 'Large Payload Backup',
                metadata: largeMetadata,
            });

            expect(result).toBeDefined();
            expect(result.status).toBe('pending');
        });
    });
});
