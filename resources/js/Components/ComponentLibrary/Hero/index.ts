// ABOUTME: This file exports all Hero components and related utilities for the component library
// ABOUTME: Provides a centralized export point for Hero-related functionality

export { default as HeroBase } from './HeroBase.vue';
export { default as HeroEmployer } from './HeroEmployer.vue';
export { default as HeroIndividual } from './HeroIndividual.vue';
export { default as HeroInstitution } from './HeroInstitution.vue';
export { default as AnimatedCounter } from './AnimatedCounter.vue';
export { default as StatisticsDisplay } from './StatisticsDisplay.vue';

// Export configuration utilities
export { getHeroConfigForAudience } from '@/Data/heroSampleData';