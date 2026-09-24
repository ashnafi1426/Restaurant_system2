import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useSidebarStore = defineStore('sidebar', () => {
  const sidebarCollapsed = ref(false)
  const hoverExpand = ref(false)
  const hoverEnabled = ref(false)
  const isMobileOpen = ref(false)

  const savedState = localStorage.getItem('sidebarCollapsed')
  if (savedState !== null) {
    sidebarCollapsed.value = savedState === 'true'
    if (sidebarCollapsed.value) {
      hoverEnabled.value = true
    }
  }

  const sidebarWidth = computed(() => {
    if (!sidebarCollapsed.value) return 'w-72'
    if (hoverExpand.value) return 'w-72'
    return 'w-20'
  })

  const isExpanded = computed(() => !sidebarCollapsed.value || hoverExpand.value)
  const isCollapsed = computed(() => sidebarCollapsed.value && !hoverExpand.value)

  function toggleCollapse() {
    if (!sidebarCollapsed.value) {
      sidebarCollapsed.value = true
      hoverEnabled.value = true
      localStorage.setItem('sidebarCollapsed', 'true')
    } else {
      sidebarCollapsed.value = false
      hoverEnabled.value = false
      hoverExpand.value = false
      localStorage.setItem('sidebarCollapsed', 'false')
    }
  }

  function onMouseEnter() {
    if (hoverEnabled.value) {
      hoverExpand.value = true
    }
  }

  function onMouseLeave() {
    if (hoverEnabled.value) {
      hoverExpand.value = false
    }
  }

  function toggleMobile() {
    isMobileOpen.value = !isMobileOpen.value
  }

  function closeMobile() {
    isMobileOpen.value = false
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
    sidebarCollapsed,
    hoverExpand,
    hoverEnabled,
    isMobileOpen,

    sidebarWidth,
    isExpanded,
    isCollapsed,

    toggleCollapse,
    onMouseEnter,
    onMouseLeave,
    toggleMobile,
    closeMobile,
    expand,
    collapse,
  }
})
