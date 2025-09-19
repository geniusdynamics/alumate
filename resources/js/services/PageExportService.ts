/**
 * Page Export Service
 *
 * Provides comprehensive page export capabilities with multiple formats,
 * compression, encryption, and tenant isolation for the Vue.js Page Builder System.
 */

import { tenantValidationService } from './TenantValidationService'

export interface Page {
  id: string
  title: string
  slug: string
  description: string
  content: any
  configuration: any
  styles: any
  createdAt: Date
  updatedAt: Date
  tenantId: string
  [key: string]: any
}

export interface ExportOptions {
  format: ExportFormat
  includeDependencies?: boolean
  includeAssets?: boolean
  compress?: boolean
  encryption?: EncryptionOptions
  metadata?: ExportMetadata
}

export type ExportFormat =
  | 'json'
  | 'xml'
  | 'yaml'
  | 'zip'
  | 'tar'
  | 'tar.gz'
  | 'html'
  | 'pdf'
  | 'markdown'
  | 'grapejs'

export interface EncryptionOptions {
  enabled: boolean
  algorithm?: 'AES-256' | 'RSA-2048' | 'RSA-4096'
  password?: string
  publicKey?: string
  privateKey?: string
}

export interface ExportMetadata {
  name?: string
  description?: string
  version?: string
  author?: string
  createdAt?: Date
  tags?: string[]
}

export interface ExportResult {
  id: string
  fileName: string
  fileSize: number
  format: ExportFormat
  downloadUrl?: string
  createdAt: Date
  expiresAt?: Date
  status: ExportStatus
  metadata: ExportMetadata
  completedAt?: Date
  error?: string
}

export type ExportStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'expired'

export interface ExportRecord {
  id: string
  fileName: string
  fileSize: number
  format: ExportFormat
  downloadUrl?: string
  createdAt: Date
  expiresAt?: Date
  status: ExportStatus
  metadata: ExportMetadata
  initiatedBy: string
  initiatedAt: Date
  completedAt?: Date
  error?: string
}

export interface ExportHistoryOptions {
  limit?: number
  offset?: number
  status?: ExportStatus
  format?: ExportFormat
  startDate?: Date
  endDate?: Date
  initiatedBy?: string
}

export class PageExportService {
  private static instance: PageExportService
  private exportQueue: Map<string, ExportJob> = new Map()
  private cache: Map<string, Page> = new Map()
  private readonly CACHE_TTL = 5 * 60 * 1000 // 5 minutes

  private constructor() {}

  static getInstance(): PageExportService {
    if (!PageExportService.instance) {
      PageExportService.instance = new PageExportService()
    }
    return PageExportService.instance
  }

  /**
    * Export a single page
    */
  async exportPage(pageId: string, tenantId: string, options?: ExportOptions): Promise<ExportResult> {
    try {
      // Validate tenant access
      const tenantResult = await tenantValidationService.validateTenantAccess(tenantId)
      if (!tenantResult.hasAccess) {
        throw new Error(`Access denied: ${tenantResult.error}`)
      }

      // Validate page exists and belongs to tenant
      const page = await this.getPage(pageId)
      if (!page) {
        throw new Error(`Page with ID ${pageId} not found`)
      }

      if (page.tenantId !== tenantId) {
        throw new Error('Access denied: Page does not belong to the specified tenant')
      }

      // Create export job
      const jobId = this.generateId()
      const exportJob: ExportJob = {
        id: jobId,
        pageId,
        options: options || { format: 'json' },
        status: 'pending',
        createdAt: new Date(),
        initiatedBy: 'current-user' // Would come from auth context
      }

      this.exportQueue.set(jobId, exportJob)

      // Process export asynchronously
      this.processExportJob(jobId)

      // Create export record
      const exportResult: ExportResult = {
        id: jobId,
        fileName: `${page.slug || pageId}.${this.getFileExtension(options?.format || 'json')}`,
        fileSize: 0, // Will be updated after export
        format: options?.format || 'json',
        createdAt: new Date(),
        status: 'pending',
        metadata: {
          name: page.title,
          description: page.description,
          version: '1.0.0',
          author: 'current-user',
          createdAt: new Date(),
          tags: []
        }
      }

      // Save to backend
      await this.saveExportRecord(exportResult)

      console.log(`Export job ${jobId} created for page ${pageId}`)
      return exportResult

    } catch (error) {
      console.error('Failed to create export:', error)
      throw error
    }
  }

  /**
    * Export multiple pages
    */
  async exportPages(pageIds: string[], tenantId: string, options?: ExportOptions): Promise<ExportResult> {
    try {
      // Validate tenant access
      const tenantResult = await tenantValidationService.validateTenantAccess(tenantId)
      if (!tenantResult.hasAccess) {
        throw new Error(`Access denied: ${tenantResult.error}`)
      }

      // Validate all pages exist and belong to tenant
      const pages = await Promise.all(pageIds.map(id => this.getPage(id)))
      const invalidPages = pages.filter((page, index) => !page)

      if (invalidPages.length > 0) {
        throw new Error(`Invalid page IDs: ${invalidPages.map((_, index) => pageIds[index]).join(', ')}`)
      }

      // Check tenant ownership
      const unauthorizedPages = pages.filter(page => page!.tenantId !== tenantId)
      if (unauthorizedPages.length > 0) {
        throw new Error('Access denied: Some pages do not belong to the specified tenant')
      }

      // Create bulk export job
      const jobId = this.generateId()
      const exportJob: ExportJob = {
        id: jobId,
        pageIds,
        options: options || { format: 'json' },
        status: 'pending',
        createdAt: new Date(),
        initiatedBy: 'current-user'
      }

      this.exportQueue.set(jobId, exportJob)

      // Process bulk export asynchronously
      this.processBulkExportJob(jobId)

      // Create export record
      const exportResult: ExportResult = {
        id: jobId,
        fileName: `pages-bulk-export-${Date.now()}.${this.getFileExtension(options?.format || 'json')}`,
        fileSize: 0,
        format: options?.format || 'json',
        createdAt: new Date(),
        status: 'pending',
        metadata: {
          name: 'Bulk Page Export',
          description: `Export of ${pageIds.length} pages`,
          version: '1.0.0',
          author: 'current-user',
          createdAt: new Date(),
          tags: ['bulk-export']
        }
      }

      // Save to backend
      await this.saveExportRecord(exportResult)

      console.log(`Bulk export job ${jobId} created for ${pageIds.length} pages`)
      return exportResult

    } catch (error) {
      console.error('Failed to create bulk export:', error)
      throw error
    }
  }

  /**
    * Export all pages for current tenant
    */
  async exportAllPages(tenantId: string, options?: ExportOptions): Promise<ExportResult> {
    try {
      // Validate tenant access
      const tenantResult = await tenantValidationService.validateTenantAccess(tenantId)
      if (!tenantResult.hasAccess) {
        throw new Error(`Access denied: ${tenantResult.error}`)
      }

      // Get all pages for tenant
      const pages = await this.getAllPagesForTenant(tenantId)
      const pageIds = pages.map(page => page.id)

      return await this.exportPages(pageIds, tenantId, options)
    } catch (error) {
      console.error('Failed to export all pages:', error)
      throw error
    }
  }

  /**
    * Get export history
    */
  async getExportHistory(tenantId: string, options?: ExportHistoryOptions): Promise<ExportRecord[]> {
    try {
      // Validate tenant access
      const tenantResult = await tenantValidationService.validateTenantAccess(tenantId)
      if (!tenantResult.hasAccess) {
        throw new Error(`Access denied: ${tenantResult.error}`)
      }

      const params = new URLSearchParams()
      params.append('tenantId', tenantId)
      if (options?.limit) params.append('limit', options.limit.toString())
      if (options?.offset) params.append('offset', options.offset.toString())
      if (options?.status) params.append('status', options.status)
      if (options?.format) params.append('format', options.format)
      if (options?.startDate) params.append('startDate', options.startDate.toISOString())
      if (options?.endDate) params.append('endDate', options.endDate.toISOString())
      if (options?.initiatedBy) params.append('initiatedBy', options.initiatedBy)

      const response = await this.makeRequest(`/api/exports?${params}`)

      if (!response.ok) {
        throw new Error('Failed to fetch export history')
      }

      return await response.json()

    } catch (error) {
      console.error('Failed to get export history:', error)
      throw error
    }
  }

  /**
    * Get specific export record
    */
  async getExport(exportId: string, tenantId: string): Promise<ExportRecord | null> {
    try {
      // Validate tenant access
      const tenantResult = await tenantValidationService.validateTenantAccess(tenantId)
      if (!tenantResult.hasAccess) {
        throw new Error(`Access denied: ${tenantResult.error}`)
      }

      const response = await this.makeRequest(`/api/exports/${exportId}`)

      if (!response.ok) {
        if (response.status === 404) {
          return null
        }
        throw new Error('Failed to fetch export record')
      }

      const exportRecord = await response.json()

      // Verify export belongs to tenant
      if (exportRecord.tenantId !== tenantId) {
        throw new Error('Access denied: Export record does not belong to the specified tenant')
      }

      return exportRecord

    } catch (error) {
      console.error('Failed to get export:', error)
      throw error
    }
  }

  /**
    * Delete export record
    */
  async deleteExport(exportId: string, tenantId: string): Promise<void> {
    try {
      // Validate tenant access
      const tenantResult = await tenantValidationService.validateTenantAccess(tenantId)
      if (!tenantResult.hasAccess) {
        throw new Error(`Access denied: ${tenantResult.error}`)
      }

      // First get the export to verify ownership
      const exportRecord = await this.getExport(exportId, tenantId)
      if (!exportRecord) {
        throw new Error(`Export with ID ${exportId} not found`)
      }

      const response = await this.makeRequest(`/api/exports/${exportId}`, {
        method: 'DELETE'
      })

      if (!response.ok) {
        throw new Error('Failed to delete export record')
      }

      console.log(`Export ${exportId} deleted successfully`)

    } catch (error) {
      console.error('Failed to delete export:', error)
      throw error
    }
  }

  /**
    * Download export file
    */
  async downloadExport(exportId: string, tenantId: string): Promise<Blob> {
    try {
      // Validate tenant access
      const tenantResult = await tenantValidationService.validateTenantAccess(tenantId)
      if (!tenantResult.hasAccess) {
        throw new Error(`Access denied: ${tenantResult.error}`)
      }

      // Verify export belongs to tenant
      const exportRecord = await this.getExport(exportId, tenantId)
      if (!exportRecord) {
        throw new Error(`Export with ID ${exportId} not found`)
      }

      const response = await this.makeRequest(`/api/exports/${exportId}/download`)

      if (!response.ok) {
        throw new Error('Failed to download export')
      }

      return await response.blob()

    } catch (error) {
      console.error('Failed to download export:', error)
      throw error
    }
  }

  /**
   * Process single page export job
   */
  private async processExportJob(jobId: string): Promise<void> {
    const exportJob = this.exportQueue.get(jobId)
    if (!exportJob) {
      console.error(`Export job ${jobId} not found`)
      return
    }

    try {
      exportJob.status = 'processing'
      this.exportQueue.set(jobId, exportJob)

      // Get page data
      const page = await this.getPage(exportJob.pageId!)
      if (!page) {
        throw new Error(`Page not found: ${exportJob.pageId}`)
      }

      // Export page based on format
      let exportData: string | Blob
      let fileName: string

      switch (exportJob.options?.format) {
        case 'json':
          exportData = JSON.stringify(page, null, 2)
          fileName = `${page.slug || page.id}.json`
          break
        case 'xml':
          exportData = this.convertToXML(page)
          fileName = `${page.slug || page.id}.xml`
          break
        case 'yaml':
          exportData = this.convertToYAML(page)
          fileName = `${page.slug || page.id}.yaml`
          break
        case 'html':
          exportData = await this.convertToHTML(page)
          fileName = `${page.slug || page.id}.html`
          break
        case 'pdf':
          exportData = await this.convertToPDF(page)
          fileName = `${page.slug || page.id}.pdf`
          break
        case 'markdown':
          exportData = this.convertToMarkdown(page)
          fileName = `${page.slug || page.id}.md`
          break
        case 'grapejs':
          exportData = this.convertToGrapeJSFormat(page)
          fileName = `${page.slug || page.id}.grapejs.json`
          break
        default:
          exportData = JSON.stringify(page, null, 2)
          fileName = `${page.slug || page.id}.json`
      }

      // Handle compression if requested
      if (exportJob.options?.compress) {
        exportData = await this.compressData(exportData)
        fileName += '.gz'
      }

      // Handle encryption if requested
      if (exportJob.options?.encryption?.enabled) {
        exportData = await this.encryptData(exportData, exportJob.options.encryption)
        fileName += '.enc'
      }

      // Save export data
      const blob = new Blob([exportData], { type: this.getMimeType(exportJob.options?.format || 'json') })
      const fileSize = blob.size

      // Upload to storage
      const downloadUrl = await this.uploadExportData(blob, fileName)

      // Update export record
      await this.updateExportRecord(jobId, {
        status: 'completed',
        fileName,
        fileSize,
        downloadUrl,
        completedAt: new Date()
      })

      exportJob.status = 'completed'
      this.exportQueue.set(jobId, exportJob)

      console.log(`Export job ${jobId} completed successfully`)

    } catch (error) {
      console.error(`Export job ${jobId} failed:`, error)

      // Update export record with error
      await this.updateExportRecord(jobId, {
        status: 'failed',
        error: error instanceof Error ? error.message : 'Unknown error',
        completedAt: new Date()
      })

      exportJob.status = 'failed'
      this.exportQueue.set(jobId, exportJob)
    }
  }

  /**
   * Process bulk export job
   */
  private async processBulkExportJob(jobId: string): Promise<void> {
    const exportJob = this.exportQueue.get(jobId)
    if (!exportJob) {
      console.error(`Bulk export job ${jobId} not found`)
      return
    }

    try {
      exportJob.status = 'processing'
      this.exportQueue.set(jobId, exportJob)

      // Get all pages
      const pages = await Promise.all(
        exportJob.pageIds!.map(id => this.getPage(id))
      )

      // Filter out null pages
      const validPages = pages.filter((page): page is Page => page !== null)

      // Create archive
      const archive = await this.createArchive(validPages, exportJob.options)
      const fileName = `pages-export-${Date.now()}.${this.getFileExtension(exportJob.options?.format || 'zip')}`
      const fileSize = archive.size

      // Upload to storage
      const downloadUrl = await this.uploadExportData(archive, fileName)

      // Update export record
      await this.updateExportRecord(jobId, {
        status: 'completed',
        fileName,
        fileSize,
        downloadUrl,
        completedAt: new Date()
      })

      exportJob.status = 'completed'
      this.exportQueue.set(jobId, exportJob)

      console.log(`Bulk export job ${jobId} completed successfully`)

    } catch (error) {
      console.error(`Bulk export job ${jobId} failed:`, error)

      // Update export record with error
      await this.updateExportRecord(jobId, {
        status: 'failed',
        error: error instanceof Error ? error.message : 'Unknown error',
        completedAt: new Date()
      })

      exportJob.status = 'failed'
      this.exportQueue.set(jobId, exportJob)
    }
  }

  /**
   * Helper methods for data conversion
   */
  private convertToXML(data: any): string {
    // In a real implementation, this would use an XML serializer
    console.log('Converting to XML:', data)
    return JSON.stringify(data) // Placeholder
  }

  private convertToYAML(data: any): string {
    // In a real implementation, this would use a YAML serializer
    console.log('Converting to YAML:', data)
    return JSON.stringify(data) // Placeholder
  }

  private async convertToHTML(page: Page): Promise<string> {
    // In a real implementation, this would render the page to HTML
    console.log('Converting to HTML:', page)
    return `<html><body><h1>${page.title}</h1><p>${page.description}</p></body></html>`
  }

  private async convertToPDF(page: Page): Promise<Blob> {
    // In a real implementation, this would use a PDF generator
    console.log('Converting to PDF:', page)
    return new Blob(['PDF content'], { type: 'application/pdf' })
  }

  private convertToMarkdown(page: Page): string {
    // In a real implementation, this would convert the page to Markdown
    console.log('Converting to Markdown:', page)
    return `# ${page.title}\n\n${page.description}`
  }

  private convertToGrapeJSFormat(page: Page): string {
    // In a real implementation, this would convert the page to GrapeJS format
    console.log('Converting to GrapeJS format:', page)
    return JSON.stringify(page)
  }

  private async compressData(data: string | Blob): Promise<Blob> {
    // In a real implementation, this would compress the data
    console.log('Compressing data:', data)
    return new Blob([data], { type: 'application/gzip' })
  }

  private async encryptData(data: string | Blob, encryption: EncryptionOptions): Promise<Blob> {
    // In a real implementation, this would encrypt the data
    console.log('Encrypting data:', data)
    return new Blob([data], { type: 'application/octet-stream' })
  }

  private async uploadExportData(data: Blob, fileName: string): Promise<string> {
    // In a real implementation, this would upload to cloud storage
    console.log(`Uploading export data ${fileName}:`, data)
    return `https://storage.example.com/exports/${fileName}`
  }

  private async createArchive(pages: Page[], options?: ExportOptions): Promise<Blob> {
    // In a real implementation, this would create a ZIP archive
    console.log('Creating archive for pages:', pages)
    return new Blob(['archive content'], { type: 'application/zip' })
  }

  private getFileExtension(format: ExportFormat): string {
    const extensions: Record<ExportFormat, string> = {
      'json': 'json',
      'xml': 'xml',
      'yaml': 'yaml',
      'zip': 'zip',
      'tar': 'tar',
      'tar.gz': 'tar.gz',
      'html': 'html',
      'pdf': 'pdf',
      'markdown': 'md',
      'grapejs': 'json'
    }

    return extensions[format] || 'dat'
  }

  private getMimeType(format: ExportFormat): string {
    const mimeTypes: Record<ExportFormat, string> = {
      'json': 'application/json',
      'xml': 'application/xml',
      'yaml': 'application/yaml',
      'zip': 'application/zip',
      'tar': 'application/x-tar',
      'tar.gz': 'application/gzip',
      'html': 'text/html',
      'pdf': 'application/pdf',
      'markdown': 'text/markdown',
      'grapejs': 'application/json'
    }

    return mimeTypes[format] || 'application/octet-stream'
  }

  /**
   * API helper methods
   */
  private async getPage(pageId: string): Promise<Page | null> {
    try {
      // Check cache first
      const cached = this.cache.get(pageId)
      if (cached && this.isCacheValid(cached.updatedAt)) {
        return cached
      }

      // Fetch from backend
      const response = await this.makeRequest(`/api/pages/${pageId}`)
      if (!response.ok) {
        return null
      }

      const page = await response.json()

      // Update cache
      this.cache.set(pageId, page)

      return page

    } catch (error) {
      console.error(`Failed to fetch page ${pageId}:`, error)
      return null
    }
  }

  private async getAllPages(): Promise<Page[]> {
    try {
      const response = await this.makeRequest('/api/pages')
      if (!response.ok) {
        throw new Error('Failed to fetch pages')
      }

      return await response.json()

    } catch (error) {
      console.error('Failed to fetch pages:', error)
      throw error
    }
  }

  private async saveExportRecord(exportResult: ExportResult): Promise<void> {
    try {
      const response = await this.makeRequest('/api/exports', {
        method: 'POST',
        body: JSON.stringify(exportResult)
      })

      if (!response.ok) {
        throw new Error('Failed to save export record')
      }

    } catch (error) {
      console.error('Failed to save export record:', error)
      throw error
    }
  }

  private async updateExportRecord(exportId: string, updates: Partial<ExportResult>): Promise<void> {
    try {
      const response = await this.makeRequest(`/api/exports/${exportId}`, {
        method: 'PATCH',
        body: JSON.stringify(updates)
      })

      if (!response.ok) {
        throw new Error('Failed to update export record')
      }

    } catch (error) {
      console.error('Failed to update export record:', error)
      throw error
    }
  }

  /**
   * Utility methods
   */
  private validateTenantAccess(): void {
    // In a real implementation, this would check the current user's tenant access
    // For now, we'll just ensure tenant context is available
    if (!this.getCurrentTenantId()) {
      throw new Error('Tenant context is required')
    }
  }

  private getCurrentTenantId(): string | null {
    // In a real implementation, this would get the current tenant from auth context
    return 'current-tenant-id'
  }

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

  private generateId(): string {
    return 'export-' + Date.now().toString(36) + Math.random().toString(36).substr(2)
  }

  private isCacheValid(timestamp: Date): boolean {
    if (!timestamp || !(timestamp instanceof Date) || isNaN(timestamp.getTime())) {
      console.warn('Invalid timestamp provided to isCacheValid:', timestamp)
      return false
    }
    return (Date.now() - timestamp.getTime()) < this.CACHE_TTL
  }

  /**
   * Validate cache entry
   */
  validateCache(key: string): boolean {
    try {
      const cached = this.cache.get(key)
      if (!cached) {
        return false
      }

      // Check if the cached item is still valid
      return this.isCacheValid(cached.updatedAt)
    } catch (error) {
      console.error('Error validating cache:', error)
      return false
    }
  }

  /**
    * Get all pages for a specific tenant
    */
  private async getAllPagesForTenant(tenantId: string): Promise<Page[]> {
    try {
      const response = await this.makeRequest(`/api/pages?tenantId=${tenantId}`)
      if (!response.ok) {
        throw new Error('Failed to fetch pages')
      }

      return await response.json()

    } catch (error) {
      console.error('Failed to fetch pages for tenant:', error)
      throw error
    }
  }
}

interface ExportJob {
  id: string
  pageId?: string
  pageIds?: string[]
  options?: ExportOptions
  status: ExportStatus
  createdAt: Date
  initiatedBy: string
  completedAt?: Date
  error?: string
}

// Export singleton instance
export const pageExportService = PageExportService.getInstance()