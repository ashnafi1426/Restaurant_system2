import api from '@/api/auth'

const NOTIFICATIONS_BASE = '/waiter/notifications'

export const waiterNotificationService = {
  async getNotifications(page: number = 1) {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}?page=${page}`)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error fetching notifications:', error)
      throw error
    }
  },

  async getUnreadCount() {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}/unread-count`)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error fetching unread count:', error)
      throw error
    }
  },

  async getUnreadNotifications() {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}/unread`)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error fetching unread notifications:', error)
      throw error
    }
  },

  async getStats() {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}/stats`)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error fetching stats:', error)
      throw error
    }
  },

  async markAsRead(notificationId: string) {
    try {
      const response = await api.patch(`${NOTIFICATIONS_BASE}/${notificationId}/read`)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error marking notification as read:', error)
      throw error
    }
  },

  async markAllAsRead() {
    try {
      const response = await api.patch(`${NOTIFICATIONS_BASE}/read-all`)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error marking all notifications as read:', error)
      throw error
    }
  },

  async deleteNotification(notificationId: string) {
    try {
      const response = await api.delete(`${NOTIFICATIONS_BASE}/${notificationId}`)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error deleting notification:', error)
      throw error
    }
  },

  async deleteAll() {
    try {
      const response = await api.delete(NOTIFICATIONS_BASE)
      return response.data
    } catch (error) {
      console.error('[WaiterNotificationService] Error deleting all notifications:', error)
      throw error
    }
  },
}
