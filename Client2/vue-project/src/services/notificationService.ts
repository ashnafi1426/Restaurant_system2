import api from '../api/auth'

export interface NotificationData {
  id?: string
  type:
  | 'booking'
  | 'check_in'
  | 'check_out'
  | 'cancellation'
  | 'system'
  | 'order_created'
  | 'order_preparing'
  | 'order_ready'
  | 'order_served'
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
  getNotifications(limit: number = 10) {
    return api.get('/notifications', { params: { limit } })
  },

  getUnreadCount() {
    return api.get('/notifications/unread-count')
  },

  markAsRead(notificationId: string) {
    return api.put(`/notifications/${notificationId}/read`)
  },

  markAllAsRead() {
    return api.put('/notifications/read-all')
  },

  deleteNotification(notificationId: string) {
    return api.delete(`/notifications/${notificationId}`)
  },

  clearAll() {
    return api.delete('/notifications/clear-all')
  },

  subscribeToNotifications(
    callback: (notification: NotificationData) => void,
    interval: number = 5000,
  ) {
    let failureCount = 0
    const maxFailures = 3
    const seenNotificationIds = new Set<string>()

    const intervalId = setInterval(async () => {
      try {
        const response = await api.get('/notifications/latest')
        if (response.data && response.data.data) {
          const notification = response.data.data

          if (notification.id && !seenNotificationIds.has(notification.id)) {
            seenNotificationIds.add(notification.id)
            callback(notification)
          }
          failureCount = 0
        }
      } catch (error: any) {
        console.error('[NotificationService] Polling error:', error)
        failureCount++

        if (error.response?.status === 401 || error.response?.status === 403) {
          clearInterval(intervalId)
          return
        }

        if (failureCount >= maxFailures) {
          clearInterval(intervalId)
          return
        }
      }
    }, interval)

    return intervalId
  },

  unsubscribeFromNotifications(intervalId: number) {
    clearInterval(intervalId)
  },
}

export default notificationService
