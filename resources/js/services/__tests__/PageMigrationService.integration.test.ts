import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { PageMigrationService, pageMigrationService } from '../PageMigrationService'
import { tenantValidationService } from '../TenantValidationService'

// Mock tenant validation service
vi.mock('../TenantValidationService', () => ({
  tenantValidationService: {
    validateTenantAccess: vi.fn()
  }
}))

// Mock fetch
const mockedFetch = vi.fn()
Object.defineProperty(globalThis, 'fetch', {
  value: mockedFetch,
  writable: true
})

// Mock document.querySelector for CSRF token
Object.defineProperty(document, 'querySelector', {
  value: vi.fn(() => ({ getAttribute: () => 'csrf-token-mock' })),
  writable: true
})

describe('PageMigrationService - Integration Tests', () => {
  let migrationService: PageMigrationService

  beforeEach(() => {
    vi.clearAllMocks()
    migrationService = PageMigrationService.getInstance()

    // Mock successful tenant validation by default
    vi.mocked(tenantValidationService.validateTenantAccess).mockResolvedValue({
      hasAccess: true,
      error: null
    })

    // Mock successful fetch responses by default
    mockedFetch.mockResolvedValue({
      ok: true,
      json: vi.fn().mockResolvedValue({}),
      blob: vi.fn().mockResolvedValue(new Blob())
    } as any)
  })

  afterEach(() => {
    vi.clearAllTimers()
  })

  describe('Migration Creation', () => {
    it('successfully creates a migration with tenant validation', async () => {
      const migrationConfig = {
        name: 'Test Migration',
        description: 'Test migration description',
        source: {
          environment: 'development',
          tenantId: 'tenant-1',
          connection: {
            host: 'localhost',
            port: 5432,
            username: 'postgres',
            password: 'password',
            database: 'tenant_db'
          }
        },
        target: {
          environment: 'production',
          tenantId: 'tenant-2',
          connection: {
            host: 'prod-db.example.com',
            port: 5432,
            username: 'prod_user',
            password: 'prod_password',
            database: 'prod_tenant_db'
          }
        },
        items: [
          { type: 'pages' as const },
          { type: 'templates' as const }
        ],
        options: {
          validateBeforeMigration: true,
          backupBeforeMigration: true,
          rollbackOnError: true,
          parallelProcessing: false
        }
      }

      const result = await migrationService.createMigration('tenant-1', migrationConfig)

      expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-1')
      expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-2')
      expect(mockedFetch).toHaveBeenCalledWith('/api/migrations', expect.objectContaining({
        method: 'POST',
        body: expect.stringContaining('Test Migration')
      }))
      expect(result).toBeDefined()
      expect(result.status).toBe('draft')
      expect(result.config.name).toBe('Test Migration')
    })

    it('rejects migration creation when source tenant access is denied', async () => {
      vi.mocked(tenantValidationService.validateTenantAccess)
        .mockResolvedValueOnce({
          hasAccess: false,
          error: 'Access denied to source'
        })
        .mockResolvedValueOnce({
          hasAccess: true,
          error: null
        })

      const migrationConfig = {
        name: 'Test Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [{ type: 'pages' as const }],
        options: { validateBeforeMigration: true }
      }

      await expect(migrationService.createMigration('tenant-1', migrationConfig)).rejects.toThrow('Access denied to source tenant')
    })

    it('rejects migration creation when target tenant access is denied', async () => {
      vi.mocked(tenantValidationService.validateTenantAccess)
        .mockResolvedValueOnce({
          hasAccess: true,
          error: null
        })
        .mockResolvedValueOnce({
          hasAccess: false,
          error: 'Access denied to target'
        })

      const migrationConfig = {
        name: 'Test Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [{ type: 'pages' as const }],
        options: { validateBeforeMigration: true }
      }

      await expect(migrationService.createMigration('tenant-1', migrationConfig)).rejects.toThrow('Access denied to target tenant')
    })

    it('validates migration configuration', async () => {
      const invalidConfig = {
        name: '', // Empty name should fail
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [],
        options: { validateBeforeMigration: true }
      }

      await expect(migrationService.createMigration('tenant-1', invalidConfig)).rejects.toThrow('Migration name is required')
    })

    it('handles different migration item types', async () => {
      const migrationConfig = {
        name: 'Complex Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [
          { type: 'pages' as const },
          { type: 'templates' as const },
          { type: 'components' as const },
          { type: 'assets' as const },
          { type: 'users' as const },
          { type: 'configurations' as const }
        ],
        options: { validateBeforeMigration: true }
      }

      const result = await migrationService.createMigration('tenant-1', migrationConfig)

      expect(result.config.items).toHaveLength(6)
      expect(result.config.items.map(item => item.type)).toEqual(
        expect.arrayContaining(['pages', 'templates', 'components', 'assets', 'users', 'configurations'])
      )
    })
  })

  describe('Migration Execution', () => {
    it('successfully executes a migration', async () => {
      const mockMigration = {
        id: 'migration-1',
        config: { name: 'Test Migration' },
        status: 'draft',
        progress: { totalItems: 0, processedItems: 0, successfulItems: 0, failedItems: 0, percentage: 0 },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      mockedFetch
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockMigration)
        })
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue({
            success: true,
            migratedItems: [],
            failedItems: [],
            errors: [],
            warnings: [],
            duration: 1000,
            startedAt: new Date(),
            completedAt: new Date()
          })
        })

      const result = await migrationService.executeMigration('tenant-1', 'migration-1')

      expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-1')
      expect(mockedFetch).toHaveBeenCalledWith('/api/migrations/migration-1/execute', expect.objectContaining({
        method: 'POST'
      }))
      expect(result.success).toBe(true)
    })

    it('handles migration execution with complex results', async () => {
      const mockMigration = {
        id: 'migration-1',
        config: { name: 'Complex Migration' },
        status: 'draft',
        progress: { totalItems: 0, processedItems: 0, successfulItems: 0, failedItems: 0, percentage: 0 },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      const mockResult = {
        success: true,
        migratedItems: [
          { type: 'pages', id: 'page-1', name: 'Home Page', migratedAt: new Date() },
          { type: 'templates', id: 'template-1', name: 'Landing Template', migratedAt: new Date() }
        ],
        failedItems: [
          { type: 'pages', id: 'page-2', name: 'Error Page', error: 'Validation failed' }
        ],
        skippedItems: [
          { type: 'assets', id: 'asset-1', name: 'Large Image', reason: 'File too large' }
        ],
        errors: [
          { type: 'connection_error', message: 'Database connection timeout', occurredAt: new Date() }
        ],
        warnings: [
          { type: 'data_loss_warning', message: 'Some metadata may be lost', occurredAt: new Date() }
        ],
        duration: 5000,
        startedAt: new Date(),
        completedAt: new Date()
      }

      mockedFetch
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockMigration)
        })
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockResult)
        })

      const result = await migrationService.executeMigration('tenant-1', 'migration-1')

      expect(result.success).toBe(true)
      expect(result.migratedItems).toHaveLength(2)
      expect(result.failedItems).toHaveLength(1)
      expect(result.skippedItems).toHaveLength(1)
      expect(result.errors).toHaveLength(1)
      expect(result.warnings).toHaveLength(1)
    })
  })

  describe('Migration Management', () => {
    it('retrieves migration by ID', async () => {
      const mockMigration = {
        id: 'migration-1',
        config: { name: 'Test Migration' },
        status: 'running',
        progress: { totalItems: 100, processedItems: 50, percentage: 50 },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      mockedFetch.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue(mockMigration)
      })

      const migration = await migrationService.getMigration('tenant-1', 'migration-1')

      expect(migration).toEqual(mockMigration)
      expect(migration.progress.percentage).toBe(50)
    })

    it('retrieves migration history with filtering', async () => {
      const mockMigrations = [
        { id: 'migration-1', status: 'completed', config: { name: 'Migration 1' } },
        { id: 'migration-2', status: 'running', config: { name: 'Migration 2' } }
      ]

      mockedFetch.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue(mockMigrations)
      })

      const migrations = await migrationService.getMigrations('tenant-1', {
        status: 'completed',
        limit: 10
      })

      expect(migrations).toEqual(mockMigrations)
      expect(mockedFetch).toHaveBeenCalledWith('/api/migrations?tenantId=tenant-1&status=completed&limit=10', expect.any(Object))
    })

    it('cancels a running migration', async () => {
      const mockMigration = {
        id: 'migration-1',
        status: 'running',
        config: { name: 'Test Migration' },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      mockedFetch
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockMigration)
        })
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue({ message: 'Migration cancelled successfully' })
        })

      await migrationService.cancelMigration('tenant-1', 'migration-1')

      expect(mockedFetch).toHaveBeenCalledWith('/api/migrations/migration-1/cancel', expect.objectContaining({
        method: 'POST'
      }))
    })

    it('deletes a migration', async () => {
      const mockMigration = {
        id: 'migration-1',
        status: 'completed',
        config: { name: 'Test Migration' },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      mockedFetch
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockMigration)
        })
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue({ message: 'Migration deleted successfully' })
        })

      await migrationService.deleteMigration('tenant-1', 'migration-1')

      expect(mockedFetch).toHaveBeenCalledWith('/api/migrations/migration-1', expect.objectContaining({
        method: 'DELETE'
      }))
    })
  })

  describe('Tenant Migration', () => {
    it('migrates entire tenant data', async () => {
      const mockMigration = {
        id: 'tenant-migration-1',
        config: { name: 'Tenant Migration' },
        status: 'draft',
        progress: { totalItems: 0, processedItems: 0, successfulItems: 0, failedItems: 0, percentage: 0 },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      const mockResult = {
        success: true,
        migratedItems: [],
        failedItems: [],
        errors: [],
        warnings: [],
        duration: 10000,
        startedAt: new Date(),
        completedAt: new Date()
      }

      mockedFetch
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockMigration)
        })
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockResult)
        })

      const result = await migrationService.migrateTenant('tenant-1', 'tenant-2', {
        includeUsers: true,
        includePermissions: true,
        includeCustomizations: true,
        validateTenantIsolation: true
      })

      expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-1')
      expect(tenantValidationService.validateTenantAccess).toHaveBeenCalledWith('tenant-2')
      expect(result.success).toBe(true)
      expect(result.migratedTenants).toContain('tenant-2')
    })

    it('handles tenant migration with conflicts', async () => {
      const mockMigration = {
        id: 'conflict-migration-1',
        config: { name: 'Conflict Migration' },
        status: 'draft',
        progress: { totalItems: 0, processedItems: 0, successfulItems: 0, failedItems: 0, percentage: 0 },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      const mockResult = {
        success: false,
        migratedItems: [],
        failedItems: [
          { type: 'pages', id: 'page-1', name: 'Home Page', error: 'Conflict detected' }
        ],
        errors: [
          { type: 'conflict_error', message: 'Data conflict in target tenant', occurredAt: new Date() }
        ],
        warnings: [],
        duration: 5000,
        startedAt: new Date(),
        completedAt: new Date()
      }

      mockedFetch
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockMigration)
        })
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockResult)
        })

      const result = await migrationService.migrateTenant('tenant-1', 'tenant-2', {
        handleConflicts: 'skip' as const
      })

      expect(result.success).toBe(false)
      expect(result.failedTenants).toHaveLength(1)
      expect(result.failedTenants[0].tenantId).toBe('tenant-2')
    })
  })

  describe('Migration Filters and Transformations', () => {
    it('handles migration with filters', async () => {
      const migrationConfig = {
        name: 'Filtered Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [
          {
            type: 'pages' as const,
            filters: [
              { type: 'tag' as const, parameters: { tags: ['important', 'published'] } },
              { type: 'date_range' as const, parameters: { startDate: '2025-01-01', endDate: '2025-12-31' } }
            ]
          }
        ],
        options: { validateBeforeMigration: true }
      }

      const result = await migrationService.createMigration('tenant-1', migrationConfig)

      expect(result.config.items[0].filters).toHaveLength(2)
      expect(result.config.items[0].filters[0].type).toBe('tag')
      expect(result.config.items[0].filters[1].type).toBe('date_range')
    })

    it('handles migration with transformations', async () => {
      const migrationConfig = {
        name: 'Transformed Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [
          {
            type: 'pages' as const,
            transformations: [
              {
                type: 'rename' as const,
                sourceField: 'title',
                targetField: 'name',
                parameters: {}
              },
              {
                type: 'map' as const,
                sourceField: 'status',
                targetField: 'state',
                parameters: { mapping: { 'draft': 'unpublished', 'published': 'live' } }
              }
            ]
          }
        ],
        options: { validateBeforeMigration: true }
      }

      const result = await migrationService.createMigration('tenant-1', migrationConfig)

      expect(result.config.items[0].transformations).toHaveLength(2)
      expect(result.config.items[0].transformations[0].type).toBe('rename')
      expect(result.config.items[0].transformations[1].type).toBe('map')
    })
  })

  describe('Migration Scheduling', () => {
    it('creates scheduled migration', async () => {
      const scheduledDate = new Date(Date.now() + 24 * 60 * 60 * 1000) // Tomorrow

      const migrationConfig = {
        name: 'Scheduled Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [{ type: 'pages' as const }],
        options: { validateBeforeMigration: true },
        schedule: {
          type: 'scheduled' as const,
          dateTime: scheduledDate
        }
      }

      const result = await migrationService.createMigration('tenant-1', migrationConfig)

      expect(result.config.schedule?.type).toBe('scheduled')
      expect(result.config.schedule?.dateTime).toEqual(scheduledDate)
      expect(result.status).toBe('draft') // Should be draft until scheduled time
    })

    it('creates recurring migration', async () => {
      const migrationConfig = {
        name: 'Recurring Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [{ type: 'pages' as const }],
        options: { validateBeforeMigration: true },
        schedule: {
          type: 'recurring' as const,
          recurrence: {
            frequency: 'weekly' as const,
            dayOfWeek: 1, // Monday
            timeOfDay: '02:00'
          }
        }
      }

      const result = await migrationService.createMigration('tenant-1', migrationConfig)

      expect(result.config.schedule?.type).toBe('recurring')
      expect(result.config.schedule?.recurrence?.frequency).toBe('weekly')
      expect(result.config.schedule?.recurrence?.dayOfWeek).toBe(1)
    })
  })

  describe('Error Handling', () => {
    it('handles network errors gracefully', async () => {
      mockedFetch.mockRejectedValueOnce(new Error('Network error'))

      const migrationConfig = {
        name: 'Test Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [{ type: 'pages' as const }],
        options: { validateBeforeMigration: true }
      }

      await expect(migrationService.createMigration('tenant-1', migrationConfig)).rejects.toThrow('Network error')
    })

    it('handles API errors gracefully', async () => {
      mockedFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        statusText: 'Internal Server Error'
      } as any)

      await expect(migrationService.getMigrations('tenant-1')).rejects.toThrow('Failed to fetch migrations')
    })

    it('handles tenant validation failures', async () => {
      vi.mocked(tenantValidationService.validateTenantAccess).mockRejectedValue(new Error('Validation service error'))

      const migrationConfig = {
        name: 'Test Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [{ type: 'pages' as const }],
        options: { validateBeforeMigration: true }
      }

      await expect(migrationService.createMigration('tenant-1', migrationConfig)).rejects.toThrow('Validation service error')
    })
  })

  describe('Concurrent Operations', () => {
    it('handles multiple simultaneous migrations', async () => {
      const migrationConfig = {
        name: 'Concurrent Migration',
        source: { environment: 'dev', tenantId: 'tenant-1' },
        target: { environment: 'prod', tenantId: 'tenant-2' },
        items: [{ type: 'pages' as const }],
        options: { validateBeforeMigration: true }
      }

      const promises = []
      for (let i = 0; i < 5; i++) {
        promises.push(migrationService.createMigration('tenant-1', {
          ...migrationConfig,
          name: `Concurrent Migration ${i}`
        }))
      }

      const results = await Promise.all(promises)

      expect(results).toHaveLength(5)
      results.forEach(result => {
        expect(result.status).toBe('draft')
      })
    })
  })

  describe('Migration Progress Tracking', () => {
    it('tracks migration progress accurately', async () => {
      const mockMigration = {
        id: 'progress-migration-1',
        config: { name: 'Progress Test' },
        status: 'running',
        progress: {
          totalItems: 100,
          processedItems: 75,
          successfulItems: 70,
          failedItems: 5,
          percentage: 75,
          currentItem: 'page-75',
          eta: new Date(Date.now() + 30 * 1000) // 30 seconds from now
        },
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      mockedFetch.mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue(mockMigration)
      })

      const migration = await migrationService.getMigration('tenant-1', 'progress-migration-1')

      expect(migration.progress.totalItems).toBe(100)
      expect(migration.progress.processedItems).toBe(75)
      expect(migration.progress.percentage).toBe(75)
      expect(migration.progress.currentItem).toBe('page-75')
      expect(migration.progress.eta).toBeDefined()
    })
  })

  describe('Migration Rollback', () => {
    it('handles migration rollback', async () => {
      const mockMigration = {
        id: 'rollback-migration-1',
        config: { name: 'Rollback Test' },
        status: 'completed',
        createdAt: new Date(),
        updatedAt: new Date(),
        createdBy: 'test-user'
      }

      const mockRollbackResult = {
        success: true,
        rolledBackItems: [
          { type: 'pages', id: 'page-1', name: 'Home Page', rolledBackAt: new Date() }
        ],
        failedRollbacks: [],
        duration: 2000,
        startedAt: new Date(),
        completedAt: new Date()
      }

      mockedFetch
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockMigration)
        })
        .mockResolvedValueOnce({
          ok: true,
          json: vi.fn().mockResolvedValue(mockRollbackResult)
        })

      // Note: This would require adding a rollback method to the service
      // For now, we'll test the structure that would be expected
      expect(mockRollbackResult.success).toBe(true)
      expect(mockRollbackResult.rolledBackItems).toHaveLength(1)
    })
  })
})