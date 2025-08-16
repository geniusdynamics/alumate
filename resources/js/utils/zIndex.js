// ABOUTME: Centralized z-index management system for consistent layering
// ABOUTME: Provides standardized z-index values to prevent overlay conflicts

/**
 * Standardized z-index values for consistent layering
 * Higher values appear above lower values
 */
export const Z_INDEX = {
  // Base layers
  BASE: 0,
  DROPDOWN: 10,
  STICKY: 20,
  FIXED: 30,
  
  // Overlay layers
  OVERLAY_BACKDROP: 40,
  MODAL_BACKDROP: 50,
  MODAL_CONTENT: 60,
  
  // High priority overlays
  TOOLTIP: 70,
  POPOVER: 80,
  NOTIFICATION: 90,
  
  // Critical overlays (should be used sparingly)
  GUIDED_TOUR: 100,
  MEDIA_VIEWER: 110,
  CRITICAL_ALERT: 120
}

/**
 * Get z-index value by name
 * @param {string} name - The z-index level name
 * @returns {number} The z-index value
 */
export function getZIndex(name) {
  return Z_INDEX[name] || Z_INDEX.BASE
}

/**
 * Generate Tailwind CSS class for z-index
 * @param {string} name - The z-index level name
 * @returns {string} Tailwind CSS class
 */
export function getZIndexClass(name) {
  const value = getZIndex(name)
  return `z-[${value}]`
}

/**
 * Check if one layer should be above another
 * @param {string} layer1 - First layer name
 * @param {string} layer2 - Second layer name
 * @returns {boolean} True if layer1 should be above layer2
 */
export function isAbove(layer1, layer2) {
  return getZIndex(layer1) > getZIndex(layer2)
}

export default Z_INDEX