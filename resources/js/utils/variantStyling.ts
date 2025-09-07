import type { HeroComponentConfig } from '@/types/components'

export interface VariantStyleClasses {
  hero: string[]
  headline: string[]
  subheading: string[]
  description: string[]
  cta: string[]
  statistics: string[]
}

/**
 * Generate CSS classes based on variant styling configuration
 */
export function getVariantStyleClasses(config: HeroComponentConfig): VariantStyleClasses {
  const styling = config.variantStyling || {}
  
  return {
    hero: getHeroClasses(styling, config.audienceType),
    headline: getHeadlineClasses(styling, config.audienceType),
    subheading: getSubheadingClasses(styling, config.audienceType),
    description: getDescriptionClasses(styling, config.audienceType),
    cta: getCtaClasses(styling, config.audienceType),
    statistics: getStatisticsClasses(styling, config.audienceType)
  }
}

function getHeroClasses(styling: NonNullable<HeroComponentConfig['variantStyling']>, audienceType: string): string[] {
  const classes: string[] = []
  
  // Color scheme classes
  switch (styling.colorScheme) {
    case 'warm':
      classes.push('bg-gradient-to-br', 'from-orange-400', 'via-red-500', 'to-pink-500')
      break
    case 'cool':
      classes.push('bg-gradient-to-br', 'from-blue-400', 'via-purple-500', 'to-indigo-600')
      break
    case 'professional':
      classes.push('bg-gradient-to-br', 'from-gray-700', 'via-gray-800', 'to-gray-900')
      break
    case 'energetic':
      classes.push('bg-gradient-to-br', 'from-green-400', 'via-blue-500', 'to-purple-600')
      break
    default:
      // Audience-specific defaults
      if (audienceType === 'individual') {
        classes.push('bg-gradient-to-br', 'from-purple-500', 'via-pink-500', 'to-red-500')
      } else if (audienceType === 'institution') {
        classes.push('bg-gradient-to-br', 'from-blue-600', 'via-indigo-600', 'to-purple-700')
      } else if (audienceType === 'employer') {
        classes.push('bg-gradient-to-br', 'from-green-500', 'via-teal-600', 'to-blue-600')
      }
  }
  
  // Spacing classes
  switch (styling.spacing) {
    case 'compact':
      classes.push('py-12', 'sm:py-16', 'lg:py-20')
      break
    case 'spacious':
      classes.push('py-20', 'sm:py-28', 'lg:py-36')
      break
    default:
      classes.push('py-16', 'sm:py-24', 'lg:py-32')
  }
  
  return classes
}

function getHeadlineClasses(styling: NonNullable<HeroComponentConfig['variantStyling']>, audienceType: string): string[] {
  const classes: string[] = ['font-bold', 'leading-tight', 'text-white']
  
  // Typography classes
  switch (styling.typography) {
    case 'modern':
      classes.push('font-sans', 'tracking-tight')
      break
    case 'classic':
      classes.push('font-serif', 'tracking-normal')
      break
    case 'bold':
      classes.push('font-black', 'tracking-tighter')
      break
    default:
      classes.push('font-bold', 'tracking-tight')
  }
  
  // Audience-specific sizing
  if (audienceType === 'individual') {
    classes.push('text-4xl', 'sm:text-5xl', 'lg:text-6xl', 'xl:text-7xl')
  } else if (audienceType === 'institution') {
    classes.push('text-3xl', 'sm:text-4xl', 'lg:text-5xl', 'xl:text-6xl')
  } else if (audienceType === 'employer') {
    classes.push('text-3xl', 'sm:text-4xl', 'lg:text-5xl', 'xl:text-6xl')
  }
  
  return classes
}

function getSubheadingClasses(styling: NonNullable<HeroComponentConfig['variantStyling']>, audienceType: string): string[] {
  const classes: string[] = ['text-white/90', 'mb-6']
  
  // Typography classes
  switch (styling.typography) {
    case 'modern':
      classes.push('font-medium', 'tracking-wide')
      break
    case 'classic':
      classes.push('font-normal', 'tracking-normal')
      break
    case 'bold':
      classes.push('font-semibold', 'tracking-tight')
      break
    default:
      classes.push('font-medium', 'tracking-normal')
  }
  
  // Audience-specific sizing
  if (audienceType === 'individual') {
    classes.push('text-xl', 'sm:text-2xl', 'lg:text-3xl')
  } else {
    classes.push('text-lg', 'sm:text-xl', 'lg:text-2xl')
  }
  
  return classes
}

function getDescriptionClasses(styling: NonNullable<HeroComponentConfig['variantStyling']>, audienceType: string): string[] {
  const classes: string[] = ['text-white/80', 'max-w-3xl', 'mb-8']
  
  // Typography classes
  switch (styling.typography) {
    case 'modern':
      classes.push('font-light', 'leading-relaxed')
      break
    case 'classic':
      classes.push('font-normal', 'leading-normal')
      break
    case 'bold':
      classes.push('font-medium', 'leading-snug')
      break
    default:
      classes.push('font-normal', 'leading-relaxed')
  }
  
  // Consistent sizing across audiences
  classes.push('text-base', 'sm:text-lg', 'lg:text-xl')
  
  return classes
}

function getCtaClasses(styling: NonNullable<HeroComponentConfig['variantStyling']>, audienceType: string): string[] {
  const classes: string[] = ['inline-flex', 'items-center', 'justify-center', 'font-medium', 'rounded-lg', 'transition-all', 'duration-200']
  
  // Color scheme specific button styles
  switch (styling.colorScheme) {
    case 'warm':
      classes.push('bg-white', 'text-orange-600', 'hover:bg-orange-50', 'hover:text-orange-700')
      break
    case 'cool':
      classes.push('bg-white', 'text-blue-600', 'hover:bg-blue-50', 'hover:text-blue-700')
      break
    case 'professional':
      classes.push('bg-white', 'text-gray-800', 'hover:bg-gray-100', 'hover:text-gray-900')
      break
    case 'energetic':
      classes.push('bg-white', 'text-purple-600', 'hover:bg-purple-50', 'hover:text-purple-700')
      break
    default:
      // Audience-specific defaults
      if (audienceType === 'individual') {
        classes.push('bg-white', 'text-purple-600', 'hover:bg-purple-50')
      } else if (audienceType === 'institution') {
        classes.push('bg-white', 'text-blue-600', 'hover:bg-blue-50')
      } else if (audienceType === 'employer') {
        classes.push('bg-white', 'text-green-600', 'hover:bg-green-50')
      }
  }
  
  // Typography classes
  switch (styling.typography) {
    case 'bold':
      classes.push('font-bold')
      break
    default:
      classes.push('font-semibold')
  }
  
  return classes
}

function getStatisticsClasses(styling: NonNullable<HeroComponentConfig['variantStyling']>, audienceType: string): string[] {
  const classes: string[] = ['text-white']
  
  // Typography classes
  switch (styling.typography) {
    case 'modern':
      classes.push('font-light')
      break
    case 'classic':
      classes.push('font-normal')
      break
    case 'bold':
      classes.push('font-bold')
      break
    default:
      classes.push('font-semibold')
  }
  
  return classes
}

/**
 * Get background gradient styles for different color schemes
 */
export function getBackgroundGradient(colorScheme?: string, audienceType?: string): Record<string, string> {
  switch (colorScheme) {
    case 'warm':
      return {
        background: 'linear-gradient(135deg, #f97316 0%, #ef4444 50%, #ec4899 100%)'
      }
    case 'cool':
      return {
        background: 'linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #4f46e5 100%)'
      }
    case 'professional':
      return {
        background: 'linear-gradient(135deg, #374151 0%, #1f2937 50%, #111827 100%)'
      }
    case 'energetic':
      return {
        background: 'linear-gradient(135deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%)'
      }
    default:
      // Audience-specific defaults
      if (audienceType === 'individual') {
        return {
          background: 'linear-gradient(135deg, #8b5cf6 0%, #ec4899 50%, #ef4444 100%)'
        }
      } else if (audienceType === 'institution') {
        return {
          background: 'linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #7c3aed 100%)'
        }
      } else if (audienceType === 'employer') {
        return {
          background: 'linear-gradient(135deg, #059669 0%, #0891b2 50%, #2563eb 100%)'
        }
      }
      
      return {
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
      }
  }
}