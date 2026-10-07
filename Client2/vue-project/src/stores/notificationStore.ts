import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationService, type NotificationData } from '@/services/notificationService'
import { waiterNotificationService } from '@/services/waiterNotificationService'
import { useAuthStore } from './auth'

let notificationPollingActive = false

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<NotificationData[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const pollIntervalId = ref<number | null>(null)

  const unreadNotifications = computed(() => notifications.value.filter((n) => !n.read))
  const recentNotifications = computed(() => notifications.value.slice(0, 5))

  const getNotificationService = () => {
    const authStore = useAuthStore()
    return authStore.user?.role === 'waiter' ? waiterNotificationService : notificationService
  }

  const fetchNotifications = async (page = 1, skipUnreadCount = false) => {
    loading.value = true
    try {
      const service = getNotificationService()
      const response = await service.getNotifications(page)
      const data = response.data?.data || response.data || []
      notifications.value = Array.isArray(data) ? data : [data]

      if (!skipUnreadCount) {
        unreadCount.value = notifications.value.filter((n) => !n.read).length
      }
    } catch (error: any) {
      if (error?.response?.status === 401 || error?.response?.status === 403) {
        notifications.value = []
        return
      }
      console.error('[notificationStore] Error fetching notifications:', error)
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
      if (error?.response?.status === 401 || error?.response?.status === 403) return
      console.error('[notificationStore] Error fetching unread count:', error)
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
    }
  }

  const startPolling = (interval = 30000) => {
    const token = localStorage.getItem('token')
    if (!token || notificationPollingActive || pollIntervalId.value) return

    notificationPollingActive = true
    pollIntervalId.value = notificationService.subscribeToNotifications((newCount: number) => {
      unreadCount.value = newCount
    }, interval) as unknown as number
  }

  const stopPolling = () => {
    if (pollIntervalId.value) {
      notificationService.unsubscribeFromNotifications(pollIntervalId.value)
      pollIntervalId.value = null
    }
    notificationPollingActive = false
  }

  return {
    notifications,
    unreadCount,
    loading,
    unreadNotifications,
    recentNotifications,

    fetchNotifications,
    fetchUnreadCount,
    addNotification,
    markNotificationAsRead,
    markAllAsRead,
    startPolling,
    stopPolling,
  }
})
