import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import notificationService from '../services/notificationService'
import { waiterNotificationService } from '../services/waiterNotificationService'
import type { NotificationData } from '../services/notificationService'
import { useAuthStore } from './auth'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<NotificationData[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const pollIntervalId = ref<number | null>(null)

  const allNotifications = computed(() => notifications.value)

  const unreadNotifications = computed(() => notifications.value.filter((n) => !n.read))

  const recentNotifications = computed(() => notifications.value.slice(0, 5))

  const notificationsByType = computed(() => {
    const grouped: Record<string, NotificationData[]> = {}
    notifications.value.forEach((notification) => {
      if (!grouped[notification.type]) {
        grouped[notification.type] = []
      }
      grouped[notification.type].push(notification)
    })
    return grouped
  })

  const getNotificationService = () => {
    const authStore = useAuthStore()
    const userRole = authStore.user?.role

    if (userRole === 'waiter') {
      return waiterNotificationService
    } else {
      return notificationService
    }
  }

  const fetchNotifications = async (page: number = 1) => {
    loading.value = true
    try {
      const service = getNotificationService()
      const response = await service.getNotifications(page)

      const data = response.data?.data || response.data || []
      notifications.value = Array.isArray(data) ? data : [data]

      await fetchUnreadCount()
    } catch (error: any) {
      console.error('[notificationStore] Error fetching notifications:', error)
      if (error.response?.status === 403 || error.response?.status === 401) {
        notifications.value = []
        return
      }
      notifications.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchUnreadCount = async () => {
    try {
      const service = getNotificationService()
      const response = await service.getUnreadCount()
      unreadCount.value = response.data?.unread_count || response.data?.count || 0
    } catch (error: any) {
      console.error('[notificationStore] Error fetching unread count:', error)
      if (error.response?.status === 403 || error.response?.status === 401) {
        return
      }
      unreadCount.value = unreadNotifications.value.length
    }
  }

  const addNotification = (notification: NotificationData) => {
    notifications.value.unshift(notification)
    unreadCount.value++

    if (notifications.value.length > 50) {
      notifications.value.pop()
    }
  }

  const markNotificationAsRead = async (notificationId: string) => {
    try {
      const service = getNotificationService()
      await service.markAsRead(notificationId)

      const notification = notifications.value.find((n) => n.id === notificationId)
      if (notification) {
        notification.read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (error) {
      console.error('[notificationStore] Error marking notification as read:', error)
      throw error
    }
  }

  const markAllAsRead = async () => {
    try {
      const service = getNotificationService()
      await service.markAllAsRead()

      notifications.value.forEach((n) => {
        n.read = true
      })
      unreadCount.value = 0
    } catch (error) {
      console.error('[notificationStore] Error marking all notifications as read:', error)
      throw error
    }
  }

  const deleteNotification = async (notificationId: string) => {
    try {
      const service = getNotificationService()
      await service.deleteNotification(notificationId)

      const index = notifications.value.findIndex((n) => n.id === notificationId)
      if (index > -1) {
        const notification = notifications.value[index]
        notifications.value.splice(index, 1)
        if (!notification.read) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
      }
    } catch (error) {
      console.error('[notificationStore] Error deleting notification:', error)
      throw error
    }
  }

  const clearAllNotifications = async () => {
    try {
      const service = getNotificationService()
      await service.clearAll()
      notifications.value = []
      unreadCount.value = 0
    } catch (error) {
      console.error('[notificationStore] Error clearing all notifications:', error)
      throw error
    }
  }

  const startPolling = (interval: number = 5000) => {
    const token = localStorage.getItem('token')
    if (!token) {
      return
    }

    if (pollIntervalId.value) {
      return
    }

    pollIntervalId.value = notificationService.subscribeToNotifications((notification) => {
      addNotification(notification)
    }, interval) as unknown as number
  }

  const stopPolling = () => {
    if (pollIntervalId.value) {
      notificationService.unsubscribeFromNotifications(pollIntervalId.value)
      pollIntervalId.value = null
    }
  }

  return {
    notifications: allNotifications,
    unreadCount,
    loading,

    unreadNotifications,
    recentNotifications,
    notificationsByType,

    fetchNotifications,
    fetchUnreadCount,
    addNotification,
    markNotificationAsRead,
    markAllAsRead,
    deleteNotification,
    clearAllNotifications,
    startPolling,
    stopPolling,
  }
})
