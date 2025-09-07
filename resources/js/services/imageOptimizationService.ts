/**
 * Image Optimization Service
 *
 * Provides comprehensive image optimization with WebP/AVIF support,
 * responsive images, lazy loading, and CDN integration.
 */

interface ImageFormat {
  webp: string;
  avif: string;
  original: string;
  jpeg: string;
  png: string;
}

interface ImageVariant {
  url: string;
  width: number;
  height: number;
  format: string;
  size?: number;
}

interface ResponsiveImageConfig {
  src: string;
  alt: string;
  sizes?: string;
  breakpoints?: number[];
  loading?: 'lazy' | 'eager';
  priority?: boolean;
  formats?: string[];
}

interface ImageOptimizationOptions {
  quality?: number;
  format?: string;
  width?: number;
  height?: number;
  crop?: boolean;
  gravity?: string;
}

class ImageOptimizationService {
  private readonly CDN_BASE_URL = process.env.MIX_CDN_URL || '/storage';
  private readonly IMAGE_API_URL = '/api/media/optimize';

  private readonly SUPPORTED_FORMATS: string[] = ['webp', 'avif', 'jpeg', 'png'];
  private readonly DEFAULT_BRKPOINTS: number[] = [320, 640, 768, 1024, 1280, 1536];

  // Track format support
  private formatSupport: { [key: string]: boolean } = {};

  constructor() {
    this.detectFormatSupport();
  }

  /**
   * Detect browser image format support
   */
  private async detectFormatSupport(): Promise<void> {
    try {
      // Check WebP support
      const webpSupported = await this.checkFormatSupport('webp');
      this.formatSupport.webp = webpSupported;

      // Check AVIF support
      const avifSupported = await this.checkFormatSupport('avif');
      this.formatSupport.avif = avifSupported;

      // Check JPEG support (always supported)
      this.formatSupport.jpeg = true;
      this.formatSupport.png = true;

      console.log('Image format support detected:', this.formatSupport);
    } catch (error) {
      console.warn('Failed to detect image format support:', error);
    }
  }

  /**
   * Check if browser supports specific image format
   */
  private async checkFormatSupport(format: string): Promise<boolean> {
    return new Promise((resolve) => {
      const canvas = document.createElement('canvas');
      canvas.width = 1;
      canvas.height = 1;

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        resolve(false);
        return;
      }

      const img = new Image();

      img.onload = () => {
        // Convert to expected format
        const dataURL = format === 'webp' ? canvas.toDataURL('image/webp') : canvas.toDataURL('image/avif');
        resolve(dataURL.indexOf(`data:image/${format}`) === 0);
      };

      img.onerror = () => resolve(false);

      // Create a small test image
      ctx.fillStyle = '#FFFFFF';
      ctx.fillRect(0, 0, 1, 1);

      const testDataURL = canvas.toDataURL();
      img.src = testDataURL;
    });
  }

  /**
   * Generate responsive image sources with multiple formats
   */
  public async generateResponsiveImage(config: ResponsiveImageConfig): Promise<string> {
    const {
      src,
      alt,
      sizes = '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 25vw',
      breakpoints = this.DEFAULT_BRKPOINTS,
      loading = 'lazy',
      priority = false,
      formats = this.SUPPORTED_FORMATS,
    } = config;

    // Generate base image URL
    const baseImageUrl = this.sanitizeImageUrl(src);

    // Generate source sets for each supported format
    const sources: string[] = [];

    for (const format of formats) {
      if (this.formatSupport[format]) {
        const srcset = await this.generateSrcSet(baseImageUrl, breakpoints, { format });
        sources.push(`<source srcset="${srcset}" sizes="${sizes}" type="image/${format}">`);
      }
    }

    // Fallback image
    const fallbackSrc = this.generateOptimizedUrl(baseImageUrl, {
      format: 'jpeg',
      quality: 80,
      width: Math.max(...breakpoints),
    });

    const imgAttributes = [
      `src="${fallbackSrc}"`,
      `alt="${this.escapeHtml(alt)}"`,
      `loading="${loading}"`,
      sizes ? `sizes="${sizes}"` : '',
      priority ? 'fetchpriority="high"' : '',
    ].filter(Boolean).join(' ');

    return `
      <picture>
        ${sources.join('\n        ')}
        <img ${imgAttributes}>
      </picture>
    `;
  }

  /**
   * Generate img element with multiple formats
   */
  public async generateOptimizedImgElement(config: ResponsiveImageConfig): Promise<string> {
    const {
      src,
      alt,
      sizes,
      loading = 'lazy',
      priority = false,
    } = config;

    const baseImageUrl = this.sanitizeImageUrl(src);

    // Determine best format
    const bestFormat = this.getBestSupportedFormat();
    const optimizedSrc = this.generateOptimizedUrl(baseImageUrl, {
      format: bestFormat,
      quality: 85,
      width: sizes ? 1024 : undefined, // Use responsive if no sizes specified
    });

    const attributes = [
      `src="${optimizedSrc}"`,
      `alt="${this.escapeHtml(alt)}"`,
      loading ? `loading="${loading}"` : '',
      priority ? 'fetchpriority="high"' : '',
      priority ? 'decoding="async"' : '',
    ].filter(Boolean).join(' ');

    return `<img ${attributes}>`;
  }

  /**
   * Generate srcset for multiple breakpoints
   */
  private async generateSrcSet(
    baseUrl: string,
    breakpoints: number[],
    options: ImageOptimizationOptions = {}
  ): Promise<string> {
    const sources: string[] = [];

    for (const width of breakpoints) {
      const url = this.generateOptimizedUrl(baseUrl, {
        ...options,
        width,
      });

      sources.push(`${url} ${width}w`);
    }

    return sources.join(', ');
  }

  /**
   * Generate optimized image URL with CDN transformations
   */
  public generateOptimizedUrl(
    baseUrl: string,
    options: ImageOptimizationOptions = {}
  ): string {
    const {
      quality = 85,
      format = this.getBestSupportedFormat(),
      width,
      height,
      crop = false,
      gravity = 'center',
    } = options;

    const transformations: string[] = [];

    // Quality
    if (quality && quality !== 100) {
      transformations.push(`q_${quality}`);
    }

    // Format
    if (format && format !== 'original') {
      transformations.push(`f_${format}`);
    }

    // Dimensions
    if (crop && width && height) {
      transformations.push(`c_crop,w_${width},h_${height},g_${gravity}`);
    } else {
      if (width) transformations.push(`w_${width}`);
      if (height) transformations.push(`h_${height}`);
    }

    // Apply transformations
    if (transformations.length > 0) {
      const transformString = transformations.join(',');
      return `${this.CDN_BASE_URL}/cdn-cgi/image/${transformString}/${baseUrl}`;
    }

    return `${this.CDN_BASE_URL}${baseUrl}`;
  }

  /**
   * Optimize image for component background
   */
  public generateBackgroundImage(src: string, config: {
    size?: 'cover' | 'contain' | 'auto';
    position?: string;
    preload?: boolean;
  } = {}): string {
    const {
      size = 'cover',
      position = 'center',
      preload = false,
    } = config;

    const optimizedUrl = this.generateOptimizedUrl(src, {
      quality: 90,
      format: this.getBestSupportedFormat(),
    });

    const style = `background-image: url('${optimizedUrl}'); background-size: ${size}; background-position: ${position};`;

    if (preload) {
      this.preloadImage(optimizedUrl);
    }

    return style;
  }

  /**
   * Preload critical images
   */
  public preloadImage(src: string): HTMLLinkElement {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.as = 'image';
    link.href = src;

    // Add appropriate fetchpriority for critical images
    if (src.includes('critical') || src.includes('hero')) {
      link.setAttribute('fetchpriority', 'high');
    }

    document.head.appendChild(link);
    return link;
  }

  /**
   * Generate mobile-optimized images with appropriate breakpoints
   */
  public async generateMobileOptimizedImage(src: string, alt: string): Promise<string> {
    const mobileBreakpoints = [320, 640, 768];
    const config: ResponsiveImageConfig = {
      src,
      alt,
      breakpoints: mobileBreakpoints,
      sizes: '(max-width: 320px) 90vw, (max-width: 640px) 80vw, (max-width: 768px) 70vw, 100vw',
      formats: ['webp', 'jpeg'], // AVIF can be too heavy for mobile
    };

    return this.generateResponsiveImage(config);
  }

  /**
   * Optimize images for component library previews
   */
  public async generateComponentPreviewImage(src: string, componentId: string): Promise<ImageVariant[]> {
    const breakpoints = [200, 400, 600]; // Preview sizes
    const variants: ImageVariant[] = [];

    for (const width of breakpoints) {
      const webpUrl = this.generateOptimizedUrl(src, {
        width,
        quality: 75,
        format: 'webp',
      });

      const fallbackUrl = this.generateOptimizedUrl(src, {
        width,
        quality: 80,
        format: 'jpeg',
      });

      variants.push(
        {
          url: this.formatSupport.webp ? webpUrl : fallbackUrl,
          width,
          height: Math.round((width * 3) / 4), // Assume 4:3 ratio
          format: this.formatSupport.webp ? 'webp' : 'jpeg',
        }
      );
    }

    return variants;
  }

  /**
   * Generate lazy loading skeleton while image loads
   */
  public generateLazyLoadingSkeleton(width: number, height: number, classes: string = ''): string {
    const aspectRatio = (height / width) * 100;

    return `
      <div class="image-skeleton ${classes}" style="width: 100%; position: relative;">
        <div style="padding-bottom: ${aspectRatio}%; background: #f0f0f0; border-radius: 8px;">
          <div class="skeleton-animation" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite;"></div>
        </div>
      </div>
      <style>
        @keyframes skeleton-loading {
          0% { background-position: -200% 0; }
          100% { background-position: 200% 0; }
        }
        .skeleton-animation {
          background-size: 200% 100%;
          animation: skeleton-loading 1.5s infinite;
        }
      </style>
    `;
  }

  /**
   * Get best supported image format
   */
  private getBestSupportedFormat(): string {
    // Prefer modern formats for better compression
    if (this.formatSupport.avif) return 'avif';
    if (this.formatSupport.webp) return 'webp';
    return 'jpeg';
  }

  /**
   * Sanitize image URL
   */
  private sanitizeImageUrl(url: string): string {
    // Remove leading slashes and ensure proper format
    return url.replace(/^\/+/, '');
  }

  /**
   * Escape HTML entities
   */
  private escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Calculate optimal image dimensions
   */
  public calculateOptimalDimensions(
    originalWidth: number,
    originalHeight: number,
    maxWidth: number,
    maxHeight: number
  ): { width: number; height: number } {
    if (originalWidth <= maxWidth && originalHeight <= maxHeight) {
      return { width: originalWidth, height: originalHeight };
    }

    const aspectRatio = originalWidth / originalHeight;
    const maxAspectRatio = maxWidth / maxHeight;

    if (aspectRatio > maxAspectRatio) {
      // Width-constrained
      return {
        width: maxWidth,
        height: Math.round(maxWidth / aspectRatio),
      };
    } else {
      // Height-constrained
      return {
        width: Math.round(maxHeight * aspectRatio),
        height: maxHeight,
      };
    }
  }

  /**
   * Batch optimize images
   */
  public async batchOptimizeImages(
    images: string[],
    options: ImageOptimizationOptions = {}
  ): Promise<string[]> {
    const optimizedUrls: string[] = [];

    for (const src of images) {
      const optimizedUrl = this.generateOptimizedUrl(src, options);
      optimizedUrls.push(optimizedUrl);
    }

    return optimizedUrls;
  }

  /**
   * Check if image is eligible for optimization
   */
  public isEligibleForOptimization(src: string): {
    eligible: boolean;
    reason?: string;
    recommendation?: string;
  } {
    try {
      const url = new URL(src, window.location.origin);

      // Skip external images that we can't control
      if (url.origin !== window.location.origin && !this.CDN_BASE_URL.includes(url.origin)) {
        return {
          eligible: false,
          reason: 'External image from different origin',
          recommendation: 'Upload images to your CDN for optimal performance',
        };
      }

      // Check file format
      const pathname = url.pathname.toLowerCase();
      const supportedExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
      const hasSupportedExtension = supportedExtensions.some(ext => pathname.includes(ext));

      if (!hasSupportedExtension) {
        return {
          eligible: false,
          reason: 'Unsupported image format',
          recommendation: 'Use JPEG, PNG, GIF, or WebP formats',
        };
      }

      return { eligible: true };
    } catch (error) {
      return {
        eligible: false,
        reason: 'Invalid URL format',
        recommendation: 'Ensure image URL is valid',
      };
    }
  }

  /**
   * Generate performance metrics for image optimization
   */
  public async generateImagePerformanceMetrics(images: string[]): Promise<{
    eligibleImages: number;
    ineligibleImages: number;
    potentialSavings: number;
    optimizationReport: any[];
  }> {
    let eligibleImages = 0;
    let ineligibleImages = 0;
    let potentialSavings = 0;
    const optimizationReport: any[] = [];

    for (const src of images) {
      const eligibility = this.isEligibleForOptimization(src);

      if (eligibility.eligible) {
        eligibleImages++;

        // Estimate potential savings based on format optimization
        const estimatedSavings = this.estimateOptimizationSavings(src);
        potentialSavings += estimatedSavings;

        optimizationReport.push({
          src,
          status: 'eligible',
          estimatedSavings,
          recommendation: 'Optimize to WebP/AVIF format with responsive variants',
        });
      } else {
        ineligibleImages++;
        optimizationReport.push({
          src,
          status: 'ineligible',
          reason: eligibility.reason,
          recommendation: eligibility.recommendation,
        });
      }
    }

    return {
      eligibleImages,
      ineligibleImages,
      potentialSavings,
      optimizationReport,
    };
  }

  /**
   * Estimate optimization savings for an image
   */
  private estimateOptimizationSavings(src: string): number {
    // Simple estimation based on average compression ratios
    const avgWebpRatio = 0.25; // WebP typically reduces file size to 25-35% of original
    const avgAvifRatio = 0.20; // AVIF provides even better compression
    const avgFallbackRatio = 0.8; // JPEG fallback size

    const bestRatio = this.formatSupport.avif ? avgAvifRatio :
                     this.formatSupport.webp ? avgWebpRatio : avgFallbackRatio;

    // Assume average image size of 200KB for estimation
    const avgImageSize = 200000; // bytes
    return Math.round(avgImageSize * (1 - bestRatio));
  }
}

export default ImageOptimizationService;
export type { ImageFormat, ImageVariant, ResponsiveImageConfig, ImageOptimizationOptions };