/**
 * Tenant Validation Service
 *
 * Provides centralized tenant validation and access control for multi-tenant applications.
 * Ensures proper tenant isolation and security across all services.
 */

export interface TenantAccessResult {
    hasAccess: boolean;
    tenantId: string | null;
    error?: string;
}

export interface TenantValidationOptions {
    allowSuperAdmin?: boolean;
    allowSystem?: boolean;
    requireActiveTenant?: boolean;
}

export class TenantValidationService {
    private static instance: TenantValidationService;
    private tenantCache: Map<string, { isActive: boolean; lastChecked: Date }> = new Map();
    private readonly CACHE_TTL = 5 * 60 * 1000; // 5 minutes

    private constructor() {}

    static getInstance(): TenantValidationService {
        if (!TenantValidationService.instance) {
            TenantValidationService.instance = new TenantValidationService();
        }
        return TenantValidationService.instance;
    }

    /**
     * Validate tenant access for a specific tenant
     */
    async validateTenantAccess(tenantId: string, options: TenantValidationOptions = {}): Promise<TenantAccessResult> {
        try {
            // Check if tenantId is provided
            if (!tenantId || tenantId.trim() === '') {
                return {
                    hasAccess: false,
                    tenantId: null,
                    error: 'Tenant ID is required',
                };
            }

            // Get current user context
            const currentUser = this.getCurrentUser();
            if (!currentUser) {
                return {
                    hasAccess: false,
                    tenantId: null,
                    error: 'User authentication required',
                };
            }

            // Check if user is super admin (if allowed)
            if (options.allowSuperAdmin && this.isSuperAdmin(currentUser)) {
                return {
                    hasAccess: true,
                    tenantId,
                };
            }

            // Check if user is system (if allowed)
            if (options.allowSystem && this.isSystemUser(currentUser)) {
                return {
                    hasAccess: true,
                    tenantId,
                };
            }

            // Check if user has access to the tenant
            const hasTenantAccess = await this.checkUserTenantAccess(currentUser, tenantId);
            if (!hasTenantAccess) {
                return {
                    hasAccess: false,
                    tenantId: null,
                    error: 'Access denied: User does not have permission to access this tenant',
                };
            }

            // Check if tenant is active (if required)
            if (options.requireActiveTenant) {
                const isActive = await this.checkTenantActiveStatus(tenantId);
                if (!isActive) {
                    return {
                        hasAccess: false,
                        tenantId: null,
                        error: 'Access denied: Tenant is not active',
                    };
                }
            }

            return {
                hasAccess: true,
                tenantId,
            };
        } catch (error) {
            console.error('Tenant validation error:', error);
            return {
                hasAccess: false,
                tenantId: null,
                error: 'Tenant validation failed due to system error',
            };
        }
    }

    /**
     * Validate access to multiple tenants
     */
    async validateMultipleTenantAccess(tenantIds: string[], options: TenantValidationOptions = {}): Promise<TenantAccessResult> {
        try {
            if (!tenantIds || tenantIds.length === 0) {
                return {
                    hasAccess: false,
                    tenantId: null,
                    error: 'At least one tenant ID is required',
                };
            }

            // Check each tenant access
            for (const tenantId of tenantIds) {
                const result = await this.validateTenantAccess(tenantId, options);
                if (!result.hasAccess) {
                    return result;
                }
            }

            return {
                hasAccess: true,
                tenantId: tenantIds[0], // Return first tenant ID for consistency
            };
        } catch (error) {
            console.error('Multiple tenant validation error:', error);
            return {
                hasAccess: false,
                tenantId: null,
                error: 'Multiple tenant validation failed due to system error',
            };
        }
    }

    /**
     * Get current tenant from context
     */
    getCurrentTenantId(): string | null {
        try {
            // Try to get from various sources
            const fromAuth = this.getTenantFromAuth();
            if (fromAuth) return fromAuth;

            const fromDomain = this.getTenantFromDomain();
            if (fromDomain) return fromDomain;

            const fromStorage = this.getTenantFromStorage();
            if (fromStorage) return fromStorage;

            return null;
        } catch (error) {
            console.error('Error getting current tenant:', error);
            return null;
        }
    }

    /**
     * Check if user has access to a specific tenant
     */
    private async checkUserTenantAccess(user: any, tenantId: string): Promise<boolean> {
        try {
            // In a real implementation, this would check against a user-tenant mapping
            // For now, we'll simulate based on user roles and tenant membership

            // Check if user has tenant membership
            const userTenants = this.getUserTenants(user);
            if (!userTenants.includes(tenantId)) {
                return false;
            }

            return true;
        } catch (error) {
            console.error('Error checking user tenant access:', error);
            return false;
        }
    }

    /**
     * Check if tenant is active
     */
    private async checkTenantActiveStatus(tenantId: string): Promise<boolean> {
        try {
            // Check cache first
            const cached = this.tenantCache.get(tenantId);
            if (cached && this.isCacheValid(cached.lastChecked)) {
                return cached.isActive;
            }

            // In a real implementation, this would check tenant status from database
            // For now, we'll assume all tenants are active
            const isActive = true;

            // Update cache
            this.tenantCache.set(tenantId, {
                isActive,
                lastChecked: new Date(),
            });

            return isActive;
        } catch (error) {
            console.error('Error checking tenant status:', error);
            return false;
        }
    }

    /**
     * Get current user from authentication context
     */
    private getCurrentUser(): any {
        try {
            // In a real implementation, this would get user from auth store/context
            // For now, we'll return a mock user
            return {
                id: 'current-user-id',
                roles: ['user'],
                tenants: ['current-tenant-id'],
            };
        } catch (error) {
            console.error('Error getting current user:', error);
            return null;
        }
    }

    /**
     * Check if user is super admin
     */
    private isSuperAdmin(user: any): boolean {
        return user && user.roles && user.roles.includes('super_admin');
    }

    /**
     * Check if user is system user
     */
    private isSystemUser(user: any): boolean {
        return user && user.roles && user.roles.includes('system');
    }

    /**
     * Get user's tenant memberships
     */
    private getUserTenants(user: any): string[] {
        if (!user || !user.tenants) {
            return [];
        }
        return Array.isArray(user.tenants) ? user.tenants : [user.tenants];
    }

    /**
     * Get tenant from authentication context
     */
    private getTenantFromAuth(): string | null {
        try {
            // In a real implementation, this would get tenant from auth token/context
            return null;
        } catch (error) {
            return null;
        }
    }

    /**
     * Get tenant from domain
     */
    private getTenantFromDomain(): string | null {
        try {
            // In a real implementation, this would extract tenant from subdomain
            const hostname = window.location.hostname;
            const parts = hostname.split('.');

            // Assume tenant subdomain pattern: tenant.domain.com
            if (parts.length >= 3 && parts[0] !== 'www') {
                return parts[0];
            }

            return null;
        } catch (error) {
            return null;
        }
    }

    /**
     * Get tenant from local storage
     */
    private getTenantFromStorage(): string | null {
        try {
            return localStorage.getItem('current_tenant_id');
        } catch (error) {
            return null;
        }
    }

    /**
     * Check if cache entry is valid
     */
    private isCacheValid(timestamp: Date): boolean {
        return Date.now() - timestamp.getTime() < this.CACHE_TTL;
    }

    /**
     * Clear tenant cache
     */
    clearCache(): void {
        this.tenantCache.clear();
    }

    /**
     * Validate tenant exists
     */
    async validateTenantExists(tenantId: string): Promise<boolean> {
        try {
            // In a real implementation, this would check if tenant exists in database
            // For now, we'll assume tenant exists if ID is provided
            return Boolean(tenantId && tenantId.trim() !== '');
        } catch (error) {
            console.error('Error validating tenant existence:', error);
            return false;
        }
    }
}

// Export singleton instance
export const tenantValidationService = TenantValidationService.getInstance();
