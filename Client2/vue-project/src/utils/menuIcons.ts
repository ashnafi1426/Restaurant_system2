/**
 * Menu Icons Utility
 * Centralized Material Design Icons (mdi-js) for menu components
 * Ensures consistency across the application
 */

export const MENU_ICONS = {
  // ==========================================
  // PLACEHOLDERS & DISPLAY ICONS
  // ==========================================
  FORK_KNIFE: 'mdi-silverware-fork-knife',
  CHEF_HAT: 'mdi-chef-hat',
  UTENSILS: 'mdi-silverware',

  // ==========================================
  // CATEGORY ICONS
  // ==========================================
  BREAKFAST: 'mdi-egg',
  LUNCH: 'mdi-silverware-fork-knife',
  DINNER: 'mdi-silverware',
  DRINKS: 'mdi-coffee',
  DESSERT: 'mdi-cake-variant',

  // ==========================================
  // STATUS & STATE ICONS
  // ==========================================
  AVAILABLE: 'mdi-check-circle',
  UNAVAILABLE: 'mdi-alert-circle',
  LOADING: 'mdi-loading',
  SYNC: 'mdi-sync',

  // ==========================================
  // ACTION ICONS
  // ==========================================
  ADD: 'mdi-plus',
  ADD_CIRCLE: 'mdi-plus-circle',
  EDIT: 'mdi-pencil',
  EDIT_CIRCLE: 'mdi-pencil-circle',
  DELETE: 'mdi-trash-outline',
  DELETE_ALERT: 'mdi-delete-alert',
  CLOSE: 'mdi-close',
  REFRESH: 'mdi-refresh',
  SEARCH: 'mdi-magnify',
  FILTER: 'mdi-filter-variant',
  CLEAR: 'mdi-close-circle',

  // ==========================================
  // VIEW & NAVIGATION ICONS
  // ==========================================
  MENU: 'mdi-menu',
  LIST: 'mdi-view-list',
  TAG: 'mdi-label',
  CHEVRON_RIGHT: 'mdi-chevron-right',
  EYE: 'mdi-eye',
  EYE_OFF: 'mdi-eye-off',
  TOGGLE_ON: 'mdi-toggle-switch',
  TOGGLE_OFF: 'mdi-toggle-switch-off',

  // ==========================================
  // STATISTICS & ANALYTICS ICONS
  // ==========================================
  LAYERS: 'mdi-layers',
  TROPHY: 'mdi-trophy',
  CHART_BAR: 'mdi-chart-bar',
  CHECK: 'mdi-check',
  ALERT: 'mdi-alert-circle',

  // ==========================================
  // BADGE & VERIFICATION ICONS
  // ==========================================
  CHECK_CIRCLE: 'mdi-check-circle',
  CIRCLE_CHECK: 'mdi-circle-check',
} as const

export type MenuIconType = (typeof MENU_ICONS)[keyof typeof MENU_ICONS]

/**
 * Category-to-Icon mapping
 * Maps backend category values to appropriate Material Design Icons
 */
export const CATEGORY_TO_ICON: Record<string, MenuIconType> = {
  breakfast: MENU_ICONS.BREAKFAST,
  lunch: MENU_ICONS.LUNCH,
  dinner: MENU_ICONS.DINNER,
  drinks: MENU_ICONS.DRINKS,
  dessert: MENU_ICONS.DESSERT,
}

/**
 * Category-to-Display-Label mapping
 * User-friendly labels for categories
 */
export const CATEGORY_LABELS: Record<string, string> = {
  breakfast: 'Breakfast',
  lunch: 'Main Course',
  dinner: 'Starters',
  drinks: 'Beverages',
  dessert: 'Desserts',
}

/**
 * Category-to-Color mapping
 * Tailwind color classes for visual differentiation
 */
export const CATEGORY_COLORS: Record<string, { bg: string; icon: string }> = {
  breakfast: { bg: 'bg-amber-100', icon: 'text-amber-600' },
  lunch: { bg: 'bg-emerald-100', icon: 'text-emerald-600' },
  dinner: { bg: 'bg-violet-100', icon: 'text-violet-600' },
  drinks: { bg: 'bg-sky-100', icon: 'text-sky-600' },
  dessert: { bg: 'bg-pink-100', icon: 'text-pink-600' },
}

/**
 * Icon Sizes (in pixels)
 * Standardized sizes for consistent UI
 */
export const ICON_SIZES = {
  MICRO: 12, // 'w-3 h-3'
  SMALL: 16, // 'w-4 h-4'
  STANDARD: 20, // 'w-5 h-5'
  MEDIUM: 24, // 'w-6 h-6'
  LARGE: 32, // 'w-8 h-8'
  HERO: 48, // 'w-12 h-12'
} as const

/**
 * Helper function to get category icon
 */
export function getCategoryIcon(category: string): MenuIconType {
  return CATEGORY_TO_ICON[category] || MENU_ICONS.FORK_KNIFE
}

/**
 * Helper function to get category display info
 */
export function getCategoryInfo(category: string) {
  return {
    icon: getCategoryIcon(category),
    label: CATEGORY_LABELS[category] || category.charAt(0).toUpperCase() + category.slice(1),
    colors: CATEGORY_COLORS[category] || { bg: 'bg-slate-100', icon: 'text-slate-600' },
  }
}
