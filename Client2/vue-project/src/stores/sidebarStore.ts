import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useSidebarStore = defineStore('sidebar', () => {
  // State
  const isCollapsed = ref(false)
  const isMobileOpen = ref(false)

  // Load saved state from localStorage on initialization
  const savedState = localStorage.getItem('sidebarCollapsed')
  if (savedState !== null) {
    isCollapsed.value = savedState === 'true'
  }

  // Computed
  const sidebarWidth = computed(() => (isCollapsed.value ? '80px' : '256px'))
  const isExpanded = computed(() => !isCollapsed.value)

  // Actions
  function toggleCollapse() {
    console.log('🔄 toggleCollapse called - Before:', isCollapsed.value)
    isCollapsed.value = !isCollapsed.value
    localStorage.setItem('sidebarCollapsed', String(isCollapsed.value))
    console.log('🔄 toggleCollapse called - After:', isCollapsed.value)
    console.log('💾 Saved to localStorage:', localStorage.getItem('sidebarCollapsed'))
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
    isCollapsed.value = false
    localStorage.setItem('sidebarCollapsed', 'false')
  }

  function collapse() {
    isCollapsed.value = true
    localStorage.setItem('sidebarCollapsed', 'true')
  }

  return {
    // State
    isCollapsed,
    isMobileOpen,
    
    // Computed
    sidebarWidth,
    isExpanded,
    
    // Actions
    toggleCollapse,
    toggleMobile,
    closeMobile,
    expand,
    collapse,
  }
})
