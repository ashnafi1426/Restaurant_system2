import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import notificationService from '../services/notificationService'
import type { NotificationData } from '../services/notificationService'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<NotificationData[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const pollIntervalId = ref<number | null>(null)

  /**
   * Get all notifications
   */
  const allNotifications = computed(() => notifications.value)

  /**
   * Get unread notifications
   */
  const unreadNotifications = computed(() => notifications.value.filter((n) => !n.read))

  /**
   * Get recent notifications
   */
  const recentNotifications = computed(() => notifications.value.slice(0, 5))

  /**
   * Get notifications grouped by type
   */
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

  /**
   * Fetch notifications from backend
   */
  const fetchNotifications = async (limit: number = 10) => {
    loading.value = true
    try {
      console.log('📬 [NOTIFICATION STORE] Fetching notifications...')
      const response = await notificationService.getNotifications(limit)
      console.log('📬 [NOTIFICATION STORE] Response:', response)

      // Handle both direct array and wrapped response
      const data = response.data?.data || response.data || []
      notifications.value = Array.isArray(data) ? data : [data]

      console.log('📬 [NOTIFICATION STORE] Notifications loaded:', notifications.value.length)

      // Update unread count
      await fetchUnreadCount()
    } catch (error: any) {
      // Silently handle 403 errors - user may not have notification permission
      if (error.response?.status === 403 || error.response?.status === 401) {
        console.warn('⚠️ [NOTIFICATION STORE] Notifications not available for this role')
        notifications.value = []
        return
      }
      console.error('❌ [NOTIFICATION STORE] Error fetching notifications:', error)
      notifications.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch unread notification count
   */
  const fetchUnreadCount = async () => {
    try {
      const response = await notificationService.getUnreadCount()
      unreadCount.value = response.data?.count || response.data || 0
      console.log('📬 [NOTIFICATION STORE] Unread count:', unreadCount.value)
    } catch (error: any) {
      // Silently handle 403 errors - user may not have notification permission
      if (error.response?.status === 403 || error.response?.status === 401) {
        console.warn('⚠️ [NOTIFICATION STORE] Notifications not available for this role')
        return
      }
      console.error('❌ [NOTIFICATION STORE] Error fetching unread count:', error)
      unreadCount.value = unreadNotifications.value.length
    }
  }

  /**
   * Add a new notification to the top of the list
   */
  const addNotification = (notification: NotificationData) => {
    console.log('🔔 [NOTIFICATION STORE] Adding notification:', notification)
    notifications.value.unshift(notification)
    unreadCount.value++

    // Keep only last 50 notifications in memory
    if (notifications.value.length > 50) {
      notifications.value.pop()
    }
  }

  /**
   * Mark notification as read
   */
  const markNotificationAsRead = async (notificationId: string) => {
    try {
      console.log('✓ [NOTIFICATION STORE] Marking notification as read:', notificationId)
      await notificationService.markAsRead(notificationId)

      // Update local state
      const notification = notifications.value.find((n) => n.id === notificationId)
      if (notification) {
        notification.read = true
        unreadCount.value = Math.max(0, unreadCount.value - 1)
        console.log(
          '✓ [NOTIFICATION STORE] Mark as read successful, unread count:',
          unreadCount.value,
        )
      }
    } catch (error) {
      console.error('❌ [NOTIFICATION STORE] Error marking as read:', error)
      throw error
    }
  }

  /**
   * Mark all notifications as read
   */
  const markAllAsRead = async () => {
    try {
      console.log('✓ [NOTIFICATION STORE] Marking all as read')
      await notificationService.markAllAsRead()

      // Update local state
      notifications.value.forEach((n) => {
        n.read = true
      })
      unreadCount.value = 0
      console.log('✓ [NOTIFICATION STORE] Mark all as read successful')
    } catch (error) {
      console.error('❌ [NOTIFICATION STORE] Error marking all as read:', error)
      throw error
    }
  }

  /**
   * Delete notification
   */
  const deleteNotification = async (notificationId: string) => {
    try {
      console.log('🗑️ [NOTIFICATION STORE] Deleting notification:', notificationId)
      await notificationService.deleteNotification(notificationId)

      // Remove from local state
      const index = notifications.value.findIndex((n) => n.id === notificationId)
      if (index > -1) {
        const notification = notifications.value[index]
        notifications.value.splice(index, 1)
        if (!notification.read) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
      }
      console.log('✓ [NOTIFICATION STORE] Delete successful')
    } catch (error) {
      console.error('❌ [NOTIFICATION STORE] Error deleting notification:', error)
      throw error
    }
  }

  /**
   * Clear all notifications
   */
  const clearAllNotifications = async () => {
    try {
      console.log('🗑️ [NOTIFICATION STORE] Clearing all notifications')
      await notificationService.clearAll()
      notifications.value = []
      unreadCount.value = 0
      console.log('✓ [NOTIFICATION STORE] Clear all successful')
    } catch (error) {
      console.error('❌ [NOTIFICATION STORE] Error clearing all:', error)
      throw error
    }
  }

  /**
   * Start polling for real-time notifications
   */
  const startPolling = (interval: number = 5000) => {
    if (pollIntervalId.value) {
      console.log('⚠️ [NOTIFICATION STORE] Polling already active')
      return
    }

    console.log('🔄 [NOTIFICATION STORE] Starting notification polling every', interval, 'ms')

    pollIntervalId.value = notificationService.subscribeToNotifications((notification) => {
      console.log('🔔 [NOTIFICATION STORE] New notification received:', notification)
      addNotification(notification)
    }, interval) as unknown as number
  }

  /**
   * Stop polling for real-time notifications
   */
  const stopPolling = () => {
    if (pollIntervalId.value) {
      console.log('⛔ [NOTIFICATION STORE] Stopping notification polling')
      notificationService.unsubscribeFromNotifications(pollIntervalId.value)
      pollIntervalId.value = null
    }
  }

  return {
    // State
    notifications: allNotifications,
    unreadCount,
    loading,

    // Computed
    unreadNotifications,
    recentNotifications,
    notificationsByType,

    // Actions
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
