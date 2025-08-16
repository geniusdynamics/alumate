export interface SEOMetadata {
  title?: string
  description?: string
  keywords?: string[]
  author?: string
  robots?: string
  canonical?: string
  ogTitle?: string
  ogDescription?: string
  ogImage?: string
  ogUrl?: string
  ogType?: string
  twitterCard?: string
  twitterTitle?: string
  twitterDescription?: string
  twitterImage?: string
  twitterSite?: string
  twitterCreator?: string
}

export interface StructuredData {
  '@context': string
  '@type': string
  [key: string]: any
}

export interface BreadcrumbItem {
  name: string
  url: string
}

class SEOService {
  private structuredDataElements = new Set<HTMLElement>()

  /**
   * Update page metadata
   */
  public updateMetadata(metadata: SEOMetadata): void {
    // Update title
    if (metadata.title) {
      document.title = metadata.title
      this.updateMetaTag('og:title', metadata.ogTitle || metadata.title)
      this.updateMetaTag('twitter:title', metadata.twitterTitle || metadata.title)
    }

    // Update description
    if (metadata.description) {
      this.updateMetaTag('description', metadata.description)
      this.updateMetaTag('og:description', metadata.ogDescription || metadata.description)
      this.updateMetaTag('twitter:description', metadata.twitterDescription || metadata.description)
    }

    // Update keywords
    if (metadata.keywords && metadata.keywords.length > 0) {
      this.updateMetaTag('keywords', metadata.keywords.join(', '))
    }

    // Update author
    if (metadata.author) {
      this.updateMetaTag('author', metadata.author)
    }

    // Update robots
    if (metadata.robots) {
      this.updateMetaTag('robots', metadata.robots)
    }

    // Update canonical URL
    if (metadata.canonical) {
      this.updateLinkTag('canonical', metadata.canonical)
    }

    // Update Open Graph tags
    if (metadata.ogImage) {
      this.updateMetaTag('og:image', metadata.ogImage)
    }
    if (metadata.ogUrl) {
      this.updateMetaTag('og:url', metadata.ogUrl)
    }
    if (metadata.ogType) {
      this.updateMetaTag('og:type', metadata.ogType)
    }

    // Update Twitter Card tags
    if (metadata.twitterCard) {
      this.updateMetaTag('twitter:card', metadata.twitterCard)
    }
    if (metadata.twitterImage) {
      this.updateMetaTag('twitter:image', metadata.twitterImage)
    }
    if (metadata.twitterSite) {
      this.updateMetaTag('twitter:site', metadata.twitterSite)
    }
    if (metadata.twitterCreator) {
      this.updateMetaTag('twitter:creator', metadata.twitterCreator)
    }
  }

  /**
   * Add structured data to the page
   */
  public addStructuredData(data: StructuredData): void {
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.textContent = JSON.stringify(data, null, 2)
    
    document.head.appendChild(script)
    this.structuredDataElements.add(script)
  }

  /**
   * Create Organization structured data
   */
  public addOrganizationData(organization: {
    name: string
    url: string
    logo?: string
    description?: string
    address?: {
      streetAddress?: string
      addressLocality?: string
      addressRegion?: string
      postalCode?: string
      addressCountry?: string
    }
    contactPoint?: {
      telephone?: string
      contactType?: string
      email?: string
    }
    sameAs?: string[]
  }): void {
    const structuredData: StructuredData = {
      '@context': 'https://schema.org',
      '@type': 'Organization',
      name: organization.name,
      url: organization.url,
      ...organization
    }

    this.addStructuredData(structuredData)
  }

  /**
   * Create WebSite structured data with search action
   */
  public addWebSiteData(website: {
    name: string
    url: string
    description?: string
    searchUrl?: string
    searchQueryInput?: string
  }): void {
    const structuredData: StructuredData = {
      '@context': 'https://schema.org',
      '@type': 'WebSite',
      name: website.name,
      url: website.url,
      description: website.description
    }

    // Add search action if provided
    if (website.searchUrl && website.searchQueryInput) {
      structuredData.potentialAction = {
        '@type': 'SearchAction',
        target: {
          '@type': 'EntryPoint',
          urlTemplate: website.searchUrl
        },
        'query-input': website.searchQueryInput
      }
    }

    this.addStructuredData(structuredData)
  }

  /**
   * Create WebPage structured data
   */
  public addWebPageData(page: {
    name: string
    url: string
    description?: string
    datePublished?: string
    dateModified?: string
    author?: string
    breadcrumbs?: BreadcrumbItem[]
  }): void {
    const structuredData: StructuredData = {
      '@context': 'https://schema.org',
      '@type': 'WebPage',
      name: page.name,
      url: page.url,
      description: page.description,
      datePublished: page.datePublished,
      dateModified: page.dateModified
    }

    if (page.author) {
      structuredData.author = {
        '@type': 'Person',
        name: page.author
      }
    }

    this.addStructuredData(structuredData)

    // Add breadcrumbs if provided
    if (page.breadcrumbs && page.breadcrumbs.length > 0) {
      this.addBreadcrumbData(page.breadcrumbs)
    }
  }

  /**
   * Create BreadcrumbList structured data
   */
  public addBreadcrumbData(breadcrumbs: BreadcrumbItem[]): void {
    const listItems = breadcrumbs.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: item.url
    }))

    const structuredData: StructuredData = {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: listItems
    }

    this.addStructuredData(structuredData)
  }

  /**
   * Create FAQ structured data
   */
  public addFAQData(faqs: Array<{ question: string; answer: string }>): void {
    const mainEntity = faqs.map(faq => ({
      '@type': 'Question',
      name: faq.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: faq.answer
      }
    }))

    const structuredData: StructuredData = {
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity
    }

    this.addStructuredData(structuredData)
  }

  /**
   * Create Product structured data
   */
  public addProductData(product: {
    name: string
    description: string
    image?: string[]
    brand?: string
    offers?: {
      price: string
      priceCurrency: string
      availability: string
      url?: string
    }
    aggregateRating?: {
      ratingValue: number
      reviewCount: number
    }
    review?: Array<{
      author: string
      datePublished: string
      reviewBody: string
      reviewRating: number
    }>
  }): void {
    const structuredData: StructuredData = {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: product.name,
      description: product.description
    }

    if (product.image) {
      structuredData.image = product.image
    }

    if (product.brand) {
      structuredData.brand = {
        '@type': 'Brand',
        name: product.brand
      }
    }

    if (product.offers) {
      structuredData.offers = {
        '@type': 'Offer',
        ...product.offers
      }
    }

    if (product.aggregateRating) {
      structuredData.aggregateRating = {
        '@type': 'AggregateRating',
        ...product.aggregateRating
      }
    }

    if (product.review) {
      structuredData.review = product.review.map(review => ({
        '@type': 'Review',
        author: {
          '@type': 'Person',
          name: review.author
        },
        datePublished: review.datePublished,
        reviewBody: review.reviewBody,
        reviewRating: {
          '@type': 'Rating',
          ratingValue: review.reviewRating
        }
      }))
    }

    this.addStructuredData(structuredData)
  }

  /**
   * Create Article structured data
   */
  public addArticleData(article: {
    headline: string
    description: string
    image?: string[]
    author: string
    datePublished: string
    dateModified?: string
    publisher?: {
      name: string
      logo?: string
    }
  }): void {
    const structuredData: StructuredData = {
      '@context': 'https://schema.org',
      '@type': 'Article',
      headline: article.headline,
      description: article.description,
      author: {
        '@type': 'Person',
        name: article.author
      },
      datePublished: article.datePublished,
      dateModified: article.dateModified || article.datePublished
    }

    if (article.image) {
      structuredData.image = article.image
    }

    if (article.publisher) {
      structuredData.publisher = {
        '@type': 'Organization',
        name: article.publisher.name,
        logo: article.publisher.logo ? {
          '@type': 'ImageObject',
          url: article.publisher.logo
        } : undefined
      }
    }

    this.addStructuredData(structuredData)
  }

  /**
   * Update or create meta tag
   */
  private updateMetaTag(name: string, content: string): void {
    const isProperty = name.startsWith('og:') || name.startsWith('twitter:')
    const attribute = isProperty ? 'property' : 'name'
    
    let meta = document.querySelector(`meta[${attribute}="${name}"]`) as HTMLMetaElement
    
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute(attribute, name)
      document.head.appendChild(meta)
    }
    
    meta.content = content
  }

  /**
   * Update or create link tag
   */
  private updateLinkTag(rel: string, href: string): void {
    let link = document.querySelector(`link[rel="${rel}"]`) as HTMLLinkElement
    
    if (!link) {
      link = document.createElement('link')
      link.rel = rel
      document.head.appendChild(link)
    }
    
    link.href = href
  }

  /**
   * Generate sitemap data
   */
  public generateSitemapData(pages: Array<{
    url: string
    lastModified?: string
    changeFrequency?: 'always' | 'hourly' | 'daily' | 'weekly' | 'monthly' | 'yearly' | 'never'
    priority?: number
  }>): string {
    const urlset = pages.map(page => {
      let url = `    <url>\n      <loc>${page.url}</loc>`
      
      if (page.lastModified) {
        url += `\n      <lastmod>${page.lastModified}</lastmod>`
      }
      
      if (page.changeFrequency) {
        url += `\n      <changefreq>${page.changeFrequency}</changefreq>`
      }
      
      if (page.priority !== undefined) {
        url += `\n      <priority>${page.priority}</priority>`
      }
      
      url += '\n    </url>'
      return url
    }).join('\n')

    return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urlset}
</urlset>`
  }

  /**
   * Validate SEO elements
   */
  public validateSEO(): { valid: boolean; issues: string[] } {
    const issues: string[] = []

    // Check title
    const title = document.title
    if (!title) {
      issues.push('Missing page title')
    } else if (title.length < 30) {
      issues.push('Page title is too short (< 30 characters)')
    } else if (title.length > 60) {
      issues.push('Page title is too long (> 60 characters)')
    }

    // Check meta description
    const description = document.querySelector('meta[name="description"]') as HTMLMetaElement
    if (!description || !description.content) {
      issues.push('Missing meta description')
    } else if (description.content.length < 120) {
      issues.push('Meta description is too short (< 120 characters)')
    } else if (description.content.length > 160) {
      issues.push('Meta description is too long (> 160 characters)')
    }

    // Check H1 tag
    const h1Tags = document.querySelectorAll('h1')
    if (h1Tags.length === 0) {
      issues.push('Missing H1 tag')
    } else if (h1Tags.length > 1) {
      issues.push('Multiple H1 tags found')
    }

    // Check canonical URL
    const canonical = document.querySelector('link[rel="canonical"]')
    if (!canonical) {
      issues.push('Missing canonical URL')
    }

    // Check Open Graph tags
    const ogTitle = document.querySelector('meta[property="og:title"]')
    const ogDescription = document.querySelector('meta[property="og:description"]')
    const ogImage = document.querySelector('meta[property="og:image"]')
    
    if (!ogTitle) issues.push('Missing og:title')
    if (!ogDescription) issues.push('Missing og:description')
    if (!ogImage) issues.push('Missing og:image')

    // Check images alt text
    const images = document.querySelectorAll('img')
    let imagesWithoutAlt = 0
    images.forEach(img => {
      if (!img.alt) imagesWithoutAlt++
    })
    
    if (imagesWithoutAlt > 0) {
      issues.push(`${imagesWithoutAlt} images missing alt text`)
    }

    return {
      valid: issues.length === 0,
      issues
    }
  }

  /**
   * Clear all structured data
   */
  public clearStructuredData(): void {
    this.structuredDataElements.forEach(element => {
      element.remove()
    })
    this.structuredDataElements.clear()
  }

  /**
   * Get current page metadata
   */
  public getCurrentMetadata(): SEOMetadata {
    const getMetaContent = (name: string, isProperty = false) => {
      const attribute = isProperty ? 'property' : 'name'
      const meta = document.querySelector(`meta[${attribute}="${name}"]`) as HTMLMetaElement
      return meta?.content || undefined
    }

    return {
      title: document.title,
      description: getMetaContent('description'),
      keywords: getMetaContent('keywords')?.split(', '),
      author: getMetaContent('author'),
      robots: getMetaContent('robots'),
      canonical: (document.querySelector('link[rel="canonical"]') as HTMLLinkElement)?.href,
      ogTitle: getMetaContent('og:title', true),
      ogDescription: getMetaContent('og:description', true),
      ogImage: getMetaContent('og:image', true),
      ogUrl: getMetaContent('og:url', true),
      ogType: getMetaContent('og:type', true),
      twitterCard: getMetaContent('twitter:card', true),
      twitterTitle: getMetaContent('twitter:title', true),
      twitterDescription: getMetaContent('twitter:description', true),
      twitterImage: getMetaContent('twitter:image', true),
      twitterSite: getMetaContent('twitter:site', true),
      twitterCreator: getMetaContent('twitter:creator', true)
    }
  }
}

// Singleton instance
export const seoService = new SEOService()

export default SEOService