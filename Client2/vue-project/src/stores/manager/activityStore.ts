import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type { NotificationItem, RecentActivity } from '@/types/manager'

export const useManagerActivityStore = defineStore('managerActivity', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const notifications = ref<NotificationItem[]>([])
  const activities = ref<RecentActivity[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  const unreadNotifications = computed(() =>
    notifications.value.filter((notification) => !notification.read_at),
  )

  const unreadCount = computed(() => unreadNotifications.value.length)

  const activityStats = computed(() => ({
    total: activities.value.length,
    unreadNotifications: unreadCount.value,
  }))

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function loadNotifications() {
    try {
      loading.value = true
      error.value = null
      notifications.value = await managerService.getNotifications()
    } catch (err: any) {
      error.value = err.message
    } finally {
      loading.value = false
    }
  }

  async function loadActivities() {
    try {
      activities.value = await managerService.getRecentActivities()
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function markNotificationRead(id: string) {
    try {
      await managerService.markNotificationAsRead(id)
      const notification = notifications.value.find((item) => item.id === id)
      if (notification) {
        notification.read_at = new Date().toISOString()
      }
    } catch (err: any) {
      error.value = err.message
      throw err
    }
  }

  async function initialize() {
    await Promise.all([loadNotifications(), loadActivities()])
  }

  function reset() {
    notifications.value = []
    activities.value = []
    error.value = null
  }

  return {
    notifications,
    activities,
    loading,
    error,
    unreadNotifications,
    unreadCount,
    activityStats,
    loadNotifications,
    loadActivities,
    markNotificationRead,
    initialize,
    reset,
  }
})
