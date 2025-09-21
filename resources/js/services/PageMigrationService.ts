import { tenantValidationService } from './TenantValidationService';

export interface MigrationConfig {
    name: string;
    description?: string;
    source: {
        environment: string;
        tenantId: string;
        connection?: {
            host: string;
            port: number;
            username: string;
            password: string;
            database: string;
        };
    };
    target: {
        environment: string;
        tenantId: string;
        connection?: {
            host: string;
            port: number;
            username: string;
            password: string;
            database: string;
        };
    };
    items: MigrationItem[];
    options: MigrationOptions;
    schedule?: MigrationSchedule;
}

export interface MigrationItem {
    type: 'pages' | 'templates' | 'components' | 'assets' | 'users' | 'configurations';
    filters?: MigrationFilter[];
    transformations?: MigrationTransformation[];
}

export interface MigrationFilter {
    type: 'tag' | 'date_range' | 'status' | 'size';
    parameters: Record<string, any>;
}

export interface MigrationTransformation {
    type: 'rename' | 'map' | 'convert' | 'merge';
    sourceField: string;
    targetField: string;
    parameters: Record<string, any>;
}

export interface MigrationOptions {
    validateBeforeMigration: boolean;
    backupBeforeMigration?: boolean;
    rollbackOnError?: boolean;
    parallelProcessing?: boolean;
    includeUsers?: boolean;
    includePermissions?: boolean;
    includeCustomizations?: boolean;
    validateTenantIsolation?: boolean;
    handleConflicts?: 'skip' | 'overwrite' | 'merge';
}

export interface MigrationSchedule {
    type: 'immediate' | 'scheduled' | 'recurring';
    dateTime?: Date;
    recurrence?: {
        frequency: 'daily' | 'weekly' | 'monthly';
        dayOfWeek?: number;
        timeOfDay?: string;
    };
}

export interface Migration {
    id: string;
    config: MigrationConfig;
    status: 'draft' | 'scheduled' | 'running' | 'completed' | 'failed' | 'cancelled';
    progress: MigrationProgress;
    createdAt: Date;
    updatedAt: Date;
    createdBy: string;
    startedAt?: Date;
    completedAt?: Date;
}

export interface MigrationProgress {
    totalItems: number;
    processedItems: number;
    successfulItems: number;
    failedItems: number;
    percentage: number;
    currentItem?: string;
    eta?: Date;
}

export interface MigrationResult {
    success: boolean;
    migratedItems: MigratedItem[];
    failedItems: FailedItem[];
    skippedItems?: SkippedItem[];
    errors: MigrationError[];
    warnings: MigrationWarning[];
    duration: number;
    startedAt: Date;
    completedAt: Date;
    migratedTenants?: string[];
    failedTenants?: FailedTenant[];
}

export interface MigratedItem {
    type: string;
    id: string;
    name: string;
    migratedAt: Date;
}

export interface FailedItem {
    type: string;
    id: string;
    name: string;
    error: string;
}

export interface SkippedItem {
    type: string;
    id: string;
    name: string;
    reason: string;
}

export interface MigrationError {
    type: string;
    message: string;
    occurredAt: Date;
}

export interface MigrationWarning {
    type: string;
    message: string;
    occurredAt: Date;
}

export interface FailedTenant {
    tenantId: string;
    error: string;
}

export interface MigrationQuery {
    status?: string;
    limit?: number;
    offset?: number;
    startDate?: Date;
    endDate?: Date;
}

export class PageMigrationService {
    private static instance: PageMigrationService;

    private constructor() {}

    public static getInstance(): PageMigrationService {
        if (!PageMigrationService.instance) {
            PageMigrationService.instance = new PageMigrationService();
        }
        return PageMigrationService.instance;
    }

    async createMigration(tenantId: string, config: MigrationConfig): Promise<Migration> {
        // Validate tenant access
        const sourceValidation = await tenantValidationService.validateTenantAccess(config.source.tenantId);
        if (!sourceValidation.hasAccess) {
            throw new Error(`Access denied to source tenant: ${sourceValidation.error}`);
        }

        const targetValidation = await tenantValidationService.validateTenantAccess(config.target.tenantId);
        if (!targetValidation.hasAccess) {
            throw new Error(`Access denied to target tenant: ${targetValidation.error}`);
        }

        // Validate configuration
        this.validateMigrationConfig(config);

        try {
            const response = await fetch('/api/migrations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    tenantId,
                    config,
                }),
            });

            if (!response.ok) {
                throw new Error(`Failed to create migration: ${response.statusText}`);
            }

            const migrationData = await response.json();
            return {
                id: migrationData.id || `migration-${Date.now()}`,
                config,
                status: 'draft',
                progress: {
                    totalItems: 0,
                    processedItems: 0,
                    successfulItems: 0,
                    failedItems: 0,
                    percentage: 0,
                },
                createdAt: migrationData.createdAt || new Date(),
                updatedAt: migrationData.updatedAt || new Date(),
                createdBy: migrationData.createdBy || 'test-user',
            };
        } catch (error) {
            console.error('Failed to create migration:', error);
            throw error;
        }
    }

    async executeMigration(tenantId: string, migrationId: string): Promise<MigrationResult> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migrationId}/execute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({ tenantId }),
            });

            if (!response.ok) {
                throw new Error(`Failed to execute migration: ${response.statusText}`);
            }

            const result = await response.json();
            return {
                success: result.success || true,
                migratedItems: result.migratedItems || [],
                failedItems: result.failedItems || [],
                skippedItems: result.skippedItems || [],
                errors: result.errors || [],
                warnings: result.warnings || [],
                duration: result.duration || 0,
                startedAt: result.startedAt || new Date(),
                completedAt: result.completedAt || new Date(),
                migratedTenants: result.migratedTenants,
                failedTenants: result.failedTenants,
            };
        } catch (error) {
            console.error('Failed to execute migration:', error);
            throw error;
        }
    }

    async getMigration(tenantId: string, migrationId: string): Promise<Migration | null> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migrationId}?tenantId=${tenantId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                if (response.status === 404) {
                    return null;
                }
                throw new Error(`Failed to fetch migration: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Failed to fetch migration:', error);
            throw error;
        }
    }

    async getMigrations(tenantId: string, query?: MigrationQuery): Promise<Migration[]> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const queryParams = new URLSearchParams({ tenantId });
            if (query) {
                if (query.status) queryParams.append('status', query.status);
                if (query.limit) queryParams.append('limit', query.limit.toString());
                if (query.offset) queryParams.append('offset', query.offset.toString());
                if (query.startDate) queryParams.append('startDate', query.startDate.toISOString());
                if (query.endDate) queryParams.append('endDate', query.endDate.toISOString());
            }

            const response = await fetch(`/api/migrations?${queryParams}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to fetch migrations: ${response.statusText}`);
            }

            const migrations = await response.json();
            return Array.isArray(migrations) ? migrations : [];
        } catch (error) {
            console.error('Failed to fetch migrations:', error);
            throw error;
        }
    }

    async cancelMigration(tenantId: string, migrationId: string): Promise<void> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migrationId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({ tenantId }),
            });

            if (!response.ok) {
                throw new Error(`Failed to cancel migration: ${response.statusText}`);
            }
        } catch (error) {
            console.error('Failed to cancel migration:', error);
            throw error;
        }
    }

    async deleteMigration(tenantId: string, migrationId: string): Promise<void> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migrationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error(`Failed to delete migration: ${response.statusText}`);
            }
        } catch (error) {
            console.error('Failed to delete migration:', error);
            throw error;
        }
    }

    async migrateTenant(sourceTenantId: string, targetTenantId: string, options?: Partial<MigrationOptions>): Promise<MigrationResult> {
        // Validate tenant access
        const sourceValidation = await tenantValidationService.validateTenantAccess(sourceTenantId);
        if (!sourceValidation.hasAccess) {
            throw new Error(`Access denied to source tenant: ${sourceValidation.error}`);
        }

        const targetValidation = await tenantValidationService.validateTenantAccess(targetTenantId);
        if (!targetValidation.hasAccess) {
            throw new Error(`Access denied to target tenant: ${targetValidation.error}`);
        }

        try {
            const response = await fetch('/api/migrations/tenant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    sourceTenantId,
                    targetTenantId,
                    options,
                }),
            });

            if (!response.ok) {
                throw new Error(`Failed to migrate tenant: ${response.statusText}`);
            }

            const result = await response.json();
            return {
                success: result.success || true,
                migratedItems: result.migratedItems || [],
                failedItems: result.failedItems || [],
                skippedItems: result.skippedItems || [],
                errors: result.errors || [],
                warnings: result.warnings || [],
                duration: result.duration || 0,
                startedAt: result.startedAt || new Date(),
                completedAt: result.completedAt || new Date(),
                migratedTenants: result.migratedTenants || [targetTenantId],
                failedTenants: result.failedTenants || [],
            };
        } catch (error) {
            console.error('Failed to migrate tenant:', error);
            throw error;
        }
    }

    // Public method for validation (used by tests)
    validateMigrationConfig(config: MigrationConfig): void {
        if (!config.name || config.name.trim() === '') {
            throw new Error('Migration name is required');
        }

        if (!config.source.tenantId || !config.target.tenantId) {
            throw new Error('Source and target tenant IDs are required');
        }

        if (!config.items || config.items.length === 0) {
            throw new Error('At least one migration item is required');
        }
    }

    async updateMigrationStatus(tenantId: string, migrationId: string, status: string): Promise<void> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migrationId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({ status, tenantId }),
            });

            if (!response.ok) {
                throw new Error(`Failed to update migration status: ${response.statusText}`);
            }
        } catch (error) {
            console.error('Failed to update migration status:', error);
            throw error;
        }
    }

    async processMigrationJob(tenantId: string, migrationId: string, jobData: any): Promise<MigrationResult> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migrationId}/process`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({ tenantId, jobData }),
            });

            if (!response.ok) {
                throw new Error(`Failed to process migration job: ${response.statusText}`);
            }

            const result = await response.json();
            return {
                success: result.success || true,
                migratedItems: result.migratedItems || [],
                failedItems: result.failedItems || [],
                skippedItems: result.skippedItems || [],
                errors: result.errors || [],
                warnings: result.warnings || [],
                duration: result.duration || 0,
                startedAt: result.startedAt || new Date(),
                completedAt: result.completedAt || new Date(),
                migratedTenants: result.migratedTenants,
                failedTenants: result.failedTenants,
            };
        } catch (error) {
            console.error('Failed to process migration job:', error);
            throw error;
        }
    }

    async saveMigration(tenantId: string, migration: Migration): Promise<Migration> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migration.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({ tenantId, migration }),
            });

            if (!response.ok) {
                throw new Error(`Failed to save migration: ${response.statusText}`);
            }

            const savedMigration = await response.json();
            return {
                ...migration,
                ...savedMigration,
                updatedAt: new Date(),
            };
        } catch (error) {
            console.error('Failed to save migration:', error);
            throw error;
        }
    }

    async cancelMigrationJobs(tenantId: string, migrationId: string): Promise<void> {
        // Validate tenant access
        const validation = await tenantValidationService.validateTenantAccess(tenantId);
        if (!validation.hasAccess) {
            throw new Error(`Access denied: ${validation.error}`);
        }

        try {
            const response = await fetch(`/api/migrations/${migrationId}/jobs/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({ tenantId }),
            });

            if (!response.ok) {
                throw new Error(`Failed to cancel migration jobs: ${response.statusText}`);
            }
        } catch (error) {
            console.error('Failed to cancel migration jobs:', error);
            throw error;
        }
    }
}

export const pageMigrationService = PageMigrationService.getInstance();
