import api from '../api/auth'

export interface NotificationData {
  id?: string
  type: 'booking' | 'check_in' | 'check_out' | 'cancellation' | 'system' | 'order_created' | 'order_preparing' | 'order_ready' | 'order_served'
  title: string
  message: string
  reservation_id?: string
  guest_name?: string
  room_number?: string
  room_type?: string
  check_in_date?: string
  check_out_date?: string
  read: boolean
  created_at?: string
}

export const notificationService = {
  /**
   * Fetch recent notifications for receptionist
   */
  getNotifications(limit: number = 10) {
    return api.get('/notifications', { params: { limit } })
  },

  /**
   * Fetch unread notifications count
   */
  getUnreadCount() {
    return api.get('/notifications/unread-count')
  },

  /**
   * Mark notification as read
   */
  markAsRead(notificationId: string) {
    return api.put(`/notifications/${notificationId}/read`)
  },

  /**
   * Mark all notifications as read
   */
  markAllAsRead() {
    return api.put('/notifications/read-all')
  },

  /**
   * Delete a notification
   */
  deleteNotification(notificationId: string) {
    return api.delete(`/notifications/${notificationId}`)
  },

  /**
   * Clear all notifications
   */
  clearAll() {
    return api.delete('/notifications/clear-all')
  },

  /**
   * Subscribe to real-time notifications via polling
   * Returns an interval ID that can be cleared later
   * Stops polling on 403/401 errors to prevent spam
   */
  subscribeToNotifications(
    callback: (notification: NotificationData) => void,
    interval: number = 5000,
  ) {
    let failureCount = 0
    const maxFailures = 3
    const seenNotificationIds = new Set<string>() // Track which notifications we've already seen

    const intervalId = setInterval(async () => {
      try {
        const response = await api.get('/notifications/latest')
        if (response.data && response.data.data) {
          const notification = response.data.data
          
          // Only call the callback for new notifications we haven't seen before
          if (notification.id && !seenNotificationIds.has(notification.id)) {
            seenNotificationIds.add(notification.id)
            callback(notification)
            console.log('🔔 [NOTIFICATION SERVICE] New notification shown:', notification.id)
          } else {
            console.log('🔕 [NOTIFICATION SERVICE] Duplicate notification skipped:', notification.id)
          }
          failureCount = 0 // Reset on success
        }
      } catch (error: any) {
        failureCount++
        
        // Stop polling on auth errors (401, 403)
        if (error.response?.status === 401 || error.response?.status === 403) {
          console.warn('🔒 Notification access denied (403/401). Stopping notification polling.')
          clearInterval(intervalId)
          return
        }

        // Stop polling after too many failures
        if (failureCount >= maxFailures) {
          console.error('❌ Too many notification polling failures. Stopping.')
          clearInterval(intervalId)
          return
        }

        console.error('⚠️ Error fetching notification (attempt ' + failureCount + '):', error.message)
      }
    }, interval)

    return intervalId
  },

  /**
   * Unsubscribe from real-time notifications
   */
  unsubscribeFromNotifications(intervalId: number) {
    clearInterval(intervalId)
  },
}

export default notificationService
