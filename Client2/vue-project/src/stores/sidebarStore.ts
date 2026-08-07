import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useSidebarStore = defineStore('sidebar', () => {
  // Three-state sidebar system
  const sidebarCollapsed = ref(false)     // Collapsed to icon-only mode
  const hoverExpand = ref(false)          // Temporarily expanded on hover
  const hoverEnabled = ref(false)         // Hover mode is active
  const isMobileOpen = ref(false)

  // Load saved state from localStorage on initialization
  const savedState = localStorage.getItem('sidebarCollapsed')
  if (savedState !== null) {
    sidebarCollapsed.value = savedState === 'true'
    // If sidebar was collapsed, enable hover mode
    if (sidebarCollapsed.value) {
      hoverEnabled.value = true
    }
  }

  // Computed
  const sidebarWidth = computed(() => {
    // If not collapsed, always full width
    if (!sidebarCollapsed.value) return 'w-72'
    // If collapsed but hover expanded, show full width
    if (hoverExpand.value) return 'w-72'
    // Otherwise, show icon-only width
    return 'w-20'
  })

  const isExpanded = computed(() => !sidebarCollapsed.value || hoverExpand.value)
  const isCollapsed = computed(() => sidebarCollapsed.value && !hoverExpand.value)

  // Actions
  function toggleCollapse() {
    if (!sidebarCollapsed.value) {
      // Collapsing: Enable hover mode
      sidebarCollapsed.value = true
      hoverEnabled.value = true
      localStorage.setItem('sidebarCollapsed', 'true')
      console.log('🔄 Sidebar collapsed - Hover mode enabled')
    } else {
      // Expanding: Disable hover mode
      sidebarCollapsed.value = false
      hoverEnabled.value = false
      hoverExpand.value = false
      localStorage.setItem('sidebarCollapsed', 'false')
      console.log('🔄 Sidebar expanded - Hover mode disabled')
    }
  }

  function onMouseEnter() {
    if (hoverEnabled.value) {
      hoverExpand.value = true
      console.log('�️ Mouse entered - Expanding sidebar temporarily')
    }
  }

  function onMouseLeave() {
    if (hoverEnabled.value) {
      hoverExpand.value = false
      console.log('🖱️ Mouse left - Collapsing sidebar to icons')
    }
  }

  function toggleMobile() {
    isMobileOpen.value = !isMobileOpen.value
    console.log('📱 Mobile sidebar toggled:', isMobileOpen.value ? 'OPEN' : 'CLOSED')
  }

  function closeMobile() {
    isMobileOpen.value = false
    console.log('📱 Mobile sidebar closed')
  }

  function expand() {
    sidebarCollapsed.value = false
    hoverEnabled.value = false
    hoverExpand.value = false
    localStorage.setItem('sidebarCollapsed', 'false')
  }

  function collapse() {
    sidebarCollapsed.value = true
    hoverEnabled.value = true
    localStorage.setItem('sidebarCollapsed', 'true')
  }

  return {
    // State
    sidebarCollapsed,
    hoverExpand,
    hoverEnabled,
    isMobileOpen,
    
    // Computed
    sidebarWidth,
    isExpanded,
    isCollapsed,
    
    // Actions
    toggleCollapse,
    onMouseEnter,
    onMouseLeave,
    toggleMobile,
    closeMobile,
    expand,
    collapse,
  }
})
