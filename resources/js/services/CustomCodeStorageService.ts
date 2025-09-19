/**
 * Custom Code Storage Service
 *
 * Provides tenant-isolated storage for custom HTML, CSS, and JavaScript code
 * with versioning, backup, and synchronization capabilities.
 */

export interface CustomCode {
  id: string
  tenantId: string
  pageId?: string
  type: 'html' | 'css' | 'javascript'
  name: string
  description?: string
  code: string
  version: number
  isActive: boolean
  isDraft: boolean
  tags: string[]
  metadata: Record<string, any>
  createdAt: Date
  updatedAt: Date
  createdBy: string
  updatedBy: string
}

export interface CustomCodeVersion {
  id: string
  customCodeId: string
  version: number
  code: string
  changes: string
  createdAt: Date
  createdBy: string
}

export interface CustomCodeBackup {
  id: string
  tenantId: string
  customCodes: CustomCode[]
  versions: CustomCodeVersion[]
  createdAt: Date
  createdBy: string
  reason?: string
}

export interface StorageOptions {
  tenantId: string
  pageId?: string
  includeVersions?: boolean
  includeInactive?: boolean
}

export interface SearchOptions {
  query?: string
  type?: 'html' | 'css' | 'javascript'
  tags?: string[]
  isActive?: boolean
  isDraft?: boolean
  limit?: number
  offset?: number
  sortBy?: 'createdAt' | 'updatedAt' | 'name'
  sortOrder?: 'asc' | 'desc'
}

export class CustomCodeStorageService {
  private static instance: CustomCodeStorageService
  private cache: Map<string, CustomCode> = new Map()
  private versionCache: Map<string, CustomCodeVersion[]> = new Map()
  private readonly CACHE_TTL = 5 * 60 * 1000 // 5 minutes

  private constructor() {}

  static getInstance(): CustomCodeStorageService {
    if (!CustomCodeStorageService.instance) {
      CustomCodeStorageService.instance = new CustomCodeStorageService()
    }
    return CustomCodeStorageService.instance
  }

  /**
   * Store custom code with tenant isolation
   */
  async storeCustomCode(customCode: Omit<CustomCode, 'id' | 'version' | 'createdAt' | 'updatedAt'>): Promise<CustomCode> {
    try {
      // Validate tenant isolation
      this.validateTenantAccess(customCode.tenantId)

      const newCode: CustomCode = {
        ...customCode,
        id: this.generateId(),
        version: 1,
        createdAt: new Date(),
        updatedAt: new Date()
      }

      // Store in backend
      const response = await this.makeRequest('/api/custom-codes', {
        method: 'POST',
        body: JSON.stringify(newCode)
      })

      if (!response.ok) {
        throw new Error('Failed to store custom code')
      }

      const storedCode = await response.json()

      // Update cache
      this.cache.set(storedCode.id, storedCode)

      console.log(`Custom code "${storedCode.name}" stored successfully for tenant ${customCode.tenantId}`)
      return storedCode

    } catch (error) {
      console.error('Failed to store custom code:', error)
      throw error
    }
  }

  /**
   * Update existing custom code
   */
  async updateCustomCode(id: string, updates: Partial<CustomCode>): Promise<CustomCode> {
    try {
      const existingCode = await this.getCustomCode(id)
      if (!existingCode) {
        throw new Error(`Custom code with ID ${id} not found`)
      }

      // Validate tenant isolation
      this.validateTenantAccess(existingCode.tenantId)

      // Create version snapshot before update
      await this.createVersionSnapshot(existingCode)

      const updatedCode: CustomCode = {
        ...existingCode,
        ...updates,
        version: existingCode.version + 1,
        updatedAt: new Date()
      }

      // Update in backend
      const response = await this.makeRequest(`/api/custom-codes/${id}`, {
        method: 'PUT',
        body: JSON.stringify(updatedCode)
      })

      if (!response.ok) {
        throw new Error('Failed to update custom code')
      }

      const result = await response.json()

      // Update cache
      this.cache.set(id, result)

      console.log(`Custom code "${result.name}" updated successfully`)
      return result

    } catch (error) {
      console.error('Failed to update custom code:', error)
      throw error
    }
  }

  /**
   * Get custom code by ID
   */
  async getCustomCode(id: string): Promise<CustomCode | null> {
    try {
      // Check cache first
      const cached = this.cache.get(id)
      if (cached && this.isCacheValid(cached.updatedAt)) {
        return cached
      }

      // Fetch from backend
      const response = await this.makeRequest(`/api/custom-codes/${id}`)

      if (!response.ok) {
        if (response.status === 404) {
          return null
        }
        throw new Error('Failed to fetch custom code')
      }

      const customCode = await response.json()

      // Update cache
      this.cache.set(id, customCode)

      return customCode

    } catch (error) {
      console.error('Failed to get custom code:', error)
      throw error
    }
  }

  /**
   * Get custom codes for a tenant
   */
  async getCustomCodes(options: StorageOptions): Promise<CustomCode[]> {
    try {
      // Validate tenant isolation
      this.validateTenantAccess(options.tenantId)

      const params = new URLSearchParams({
        tenantId: options.tenantId,
        ...(options.pageId && { pageId: options.pageId }),
        ...(options.includeVersions && { includeVersions: 'true' }),
        ...(options.includeInactive && { includeInactive: 'true' })
      })

      const response = await this.makeRequest(`/api/custom-codes?${params}`)

      if (!response.ok) {
        throw new Error('Failed to fetch custom codes')
      }

      const customCodes = await response.json()

      // Update cache
      customCodes.forEach((code: CustomCode) => {
        this.cache.set(code.id, code)
      })

      return customCodes

    } catch (error) {
      console.error('Failed to get custom codes:', error)
      throw error
    }
  }

  /**
   * Search custom codes
   */
  async searchCustomCodes(tenantId: string, searchOptions: SearchOptions): Promise<CustomCode[]> {
    try {
      // Validate tenant isolation
      this.validateTenantAccess(tenantId)

      const params = new URLSearchParams({
        tenantId,
        ...(searchOptions.query && { query: searchOptions.query }),
        ...(searchOptions.type && { type: searchOptions.type }),
        ...(searchOptions.tags && { tags: searchOptions.tags.join(',') }),
        ...(searchOptions.isActive !== undefined && { isActive: searchOptions.isActive.toString() }),
        ...(searchOptions.isDraft !== undefined && { isDraft: searchOptions.isDraft.toString() }),
        ...(searchOptions.limit && { limit: searchOptions.limit.toString() }),
        ...(searchOptions.offset && { offset: searchOptions.offset.toString() }),
        ...(searchOptions.sortBy && { sortBy: searchOptions.sortBy }),
        ...(searchOptions.sortOrder && { sortOrder: searchOptions.sortOrder })
      })

      const response = await this.makeRequest(`/api/custom-codes/search?${params}`)

      if (!response.ok) {
        throw new Error('Failed to search custom codes')
      }

      return await response.json()

    } catch (error) {
      console.error('Failed to search custom codes:', error)
      throw error
    }
  }

  /**
   * Delete custom code
   */
  async deleteCustomCode(id: string): Promise<void> {
    try {
      const existingCode = await this.getCustomCode(id)
      if (!existingCode) {
        throw new Error(`Custom code with ID ${id} not found`)
      }

      // Validate tenant isolation
      this.validateTenantAccess(existingCode.tenantId)

      // Create backup before deletion
      await this.createBackup(existingCode.tenantId, 'Custom code deletion', [existingCode])

      const response = await this.makeRequest(`/api/custom-codes/${id}`, {
        method: 'DELETE'
      })

      if (!response.ok) {
        throw new Error('Failed to delete custom code')
      }

      // Remove from cache
      this.cache.delete(id)

      console.log(`Custom code "${existingCode.name}" deleted successfully`)

    } catch (error) {
      console.error('Failed to delete custom code:', error)
      throw error
    }
  }

  /**
   * Get versions of custom code
   */
  async getCustomCodeVersions(customCodeId: string): Promise<CustomCodeVersion[]> {
    try {
      // Check cache first
      const cached = this.versionCache.get(customCodeId)
      if (cached && cached.length > 0 && this.isCacheValid(cached[0].createdAt)) {
        return cached
      }

      const response = await this.makeRequest(`/api/custom-codes/${customCodeId}/versions`)

      if (!response.ok) {
        throw new Error('Failed to fetch custom code versions')
      }

      const versions = await response.json()

      // Update cache
      this.versionCache.set(customCodeId, versions)

      return versions

    } catch (error) {
      console.error('Failed to get custom code versions:', error)
      throw error
    }
  }

  /**
   * Restore custom code to specific version
   */
  async restoreCustomCodeVersion(customCodeId: string, version: number): Promise<CustomCode> {
    try {
      const existingCode = await this.getCustomCode(customCodeId)
      if (!existingCode) {
        throw new Error(`Custom code with ID ${customCodeId} not found`)
      }

      // Validate tenant isolation
      this.validateTenantAccess(existingCode.tenantId)

      const response = await this.makeRequest(`/api/custom-codes/${customCodeId}/restore`, {
        method: 'POST',
        body: JSON.stringify({ version })
      })

      if (!response.ok) {
        throw new Error('Failed to restore custom code version')
      }

      const restoredCode = await response.json()

      // Update cache
      this.cache.set(customCodeId, restoredCode)

      console.log(`Custom code "${restoredCode.name}" restored to version ${version}`)
      return restoredCode

    } catch (error) {
      console.error('Failed to restore custom code version:', error)
      throw error
    }
  }

  /**
   * Create backup of custom codes
   */
  async createBackup(tenantId: string, reason?: string, customCodes?: CustomCode[]): Promise<CustomCodeBackup> {
    try {
      // Validate tenant isolation
      this.validateTenantAccess(tenantId)

      const codesToBackup = customCodes || await this.getCustomCodes({ tenantId })

      const backup: Omit<CustomCodeBackup, 'id' | 'createdAt'> = {
        tenantId,
        customCodes: codesToBackup,
        versions: [],
        createdBy: 'system', // Would come from auth context
        reason
      }

      // Get versions for each custom code
      for (const code of codesToBackup) {
        try {
          const versions = await this.getCustomCodeVersions(code.id)
          backup.versions.push(...versions)
        } catch (error) {
          console.warn(`Failed to get versions for custom code ${code.id}:`, error)
        }
      }

      const response = await this.makeRequest('/api/custom-codes/backups', {
        method: 'POST',
        body: JSON.stringify(backup)
      })

      if (!response.ok) {
        throw new Error('Failed to create backup')
      }

      const createdBackup = await response.json()

      console.log(`Backup created successfully for tenant ${tenantId}`)
      return createdBackup

    } catch (error) {
      console.error('Failed to create backup:', error)
      throw error
    }
  }

  /**
   * Restore from backup
   */
  async restoreFromBackup(backupId: string): Promise<void> {
    try {
      const response = await this.makeRequest(`/api/custom-codes/backups/${backupId}/restore`, {
        method: 'POST'
      })

      if (!response.ok) {
        throw new Error('Failed to restore from backup')
      }

      // Clear cache to force refresh
      this.clearCache()

      console.log(`Restored successfully from backup ${backupId}`)

    } catch (error) {
      console.error('Failed to restore from backup:', error)
      throw error
    }
  }

  /**
   * Sync custom codes across tenants (admin only)
   */
  async syncCustomCodes(sourceTenantId: string, targetTenantIds: string[]): Promise<void> {
    try {
      // This would require admin privileges
      const sourceCodes = await this.getCustomCodes({ tenantId: sourceTenantId })

      for (const targetTenantId of targetTenantIds) {
        for (const sourceCode of sourceCodes) {
          // Create copy for target tenant
          const targetCode = {
            ...sourceCode,
            tenantId: targetTenantId,
            id: this.generateId(),
            name: `${sourceCode.name} (Synced)`,
            version: 1,
            createdAt: new Date(),
            updatedAt: new Date()
          }

          await this.storeCustomCode(targetCode)
        }
      }

      console.log(`Custom codes synced from tenant ${sourceTenantId} to ${targetTenantIds.length} tenants`)

    } catch (error) {
      console.error('Failed to sync custom codes:', error)
      throw error
    }
  }

  /**
   * Export custom codes
   */
  async exportCustomCodes(tenantId: string, format: 'json' | 'zip' = 'json'): Promise<Blob> {
    try {
      // Validate tenant isolation
      this.validateTenantAccess(tenantId)

      const customCodes = await this.getCustomCodes({ tenantId, includeVersions: true })

      if (format === 'json') {
        const exportData = {
          tenantId,
          customCodes,
          exportedAt: new Date().toISOString(),
          version: '1.0'
        }

        return new Blob([JSON.stringify(exportData, null, 2)], {
          type: 'application/json'
        })
      } else {
        // For ZIP format, we'd need additional libraries
        throw new Error('ZIP export not implemented')
      }

    } catch (error) {
      console.error('Failed to export custom codes:', error)
      throw error
    }
  }

  /**
   * Import custom codes
   */
  async importCustomCodes(tenantId: string, data: any): Promise<CustomCode[]> {
    try {
      // Validate tenant isolation
      this.validateTenantAccess(tenantId)

      const importedCodes: CustomCode[] = []

      if (data.customCodes && Array.isArray(data.customCodes)) {
        for (const codeData of data.customCodes) {
          const importedCode = await this.storeCustomCode({
            ...codeData,
            tenantId,
            id: this.generateId(),
            name: `${codeData.name} (Imported)`,
            version: 1,
            createdAt: new Date(),
            updatedAt: new Date()
          })

          importedCodes.push(importedCode)
        }
      }

      console.log(`Imported ${importedCodes.length} custom codes for tenant ${tenantId}`)
      return importedCodes

    } catch (error) {
      console.error('Failed to import custom codes:', error)
      throw error
    }
  }

  /**
   * Get storage statistics
   */
  async getStorageStats(tenantId: string): Promise<{
    totalCodes: number
    activeCodes: number
    draftCodes: number
    totalVersions: number
    storageSize: number
  }> {
    try {
      // Validate tenant isolation
      this.validateTenantAccess(tenantId)

      const response = await this.makeRequest(`/api/custom-codes/stats?tenantId=${tenantId}`)

      if (!response.ok) {
        throw new Error('Failed to get storage statistics')
      }

      return await response.json()

    } catch (error) {
      console.error('Failed to get storage stats:', error)
      throw error
    }
  }

  /**
   * Validate tenant access
   */
  private validateTenantAccess(tenantId: string): void {
    // In a real implementation, this would check the current user's tenant access
    // For now, we'll just ensure tenantId is provided
    if (!tenantId) {
      throw new Error('Tenant ID is required')
    }
  }

  /**
   * Create version snapshot
   */
  private async createVersionSnapshot(customCode: CustomCode): Promise<void> {
    try {
      const version: Omit<CustomCodeVersion, 'id' | 'createdAt'> = {
        customCodeId: customCode.id,
        version: customCode.version,
        code: customCode.code,
        changes: 'Auto-generated version snapshot',
        createdBy: customCode.updatedBy || 'system'
      }

      await this.makeRequest('/api/custom-codes/versions', {
        method: 'POST',
        body: JSON.stringify(version)
      })

    } catch (error) {
      console.warn('Failed to create version snapshot:', error)
      // Don't throw - version creation failure shouldn't block the main operation
    }
  }

  /**
   * Make authenticated request with tenant context
   */
  private async makeRequest(url: string, options: RequestInit = {}): Promise<Response> {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

    const defaultHeaders = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken })
    }

    return fetch(url, {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers
      }
    })
  }

  /**
   * Generate unique ID
   */
  private generateId(): string {
    return 'custom-' + Date.now().toString(36) + Math.random().toString(36).substr(2)
  }

  /**
   * Check if cache entry is still valid
   */
  private isCacheValid(timestamp: Date): boolean {
    return (Date.now() - timestamp.getTime()) < this.CACHE_TTL
  }

  /**
   * Clear cache
   */
  private clearCache(): void {
    this.cache.clear()
    this.versionCache.clear()
  }
}

// Export singleton instance
export const customCodeStorageService = CustomCodeStorageService.getInstance()