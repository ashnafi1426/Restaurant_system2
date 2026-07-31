import api from '@/api/auth'

const NOTIFICATIONS_BASE = '/waiter/notifications'

export const waiterNotificationService = {
  // Get all notifications
  async getNotifications(page: number = 1) {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}?page=${page}`)
      return response.data
    } catch (error) {
      console.error('❌ Error fetching notifications:', error)
      throw error
    }
  },

  // Get unread count
  async getUnreadCount() {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}/unread-count`)
      return response.data
    } catch (error) {
      console.error('❌ Error fetching unread count:', error)
      throw error
    }
  },

  // Get unread notifications
  async getUnreadNotifications() {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}/unread`)
      return response.data
    } catch (error) {
      console.error('❌ Error fetching unread notifications:', error)
      throw error
    }
  },

  // Get notification statistics
  async getStats() {
    try {
      const response = await api.get(`${NOTIFICATIONS_BASE}/stats`)
      return response.data
    } catch (error) {
      console.error('❌ Error fetching notification stats:', error)
      throw error
    }
  },

  // Mark notification as read
  async markAsRead(notificationId: string) {
    try {
      const response = await api.patch(`${NOTIFICATIONS_BASE}/${notificationId}/read`)
      return response.data
    } catch (error) {
      console.error('❌ Error marking notification as read:', error)
      throw error
    }
  },

  // Mark all notifications as read
  async markAllAsRead() {
    try {
      const response = await api.patch(`${NOTIFICATIONS_BASE}/read-all`)
      return response.data
    } catch (error) {
      console.error('❌ Error marking all as read:', error)
      throw error
    }
  },

  // Delete notification
  async deleteNotification(notificationId: string) {
    try {
      const response = await api.delete(`${NOTIFICATIONS_BASE}/${notificationId}`)
      return response.data
    } catch (error) {
      console.error('❌ Error deleting notification:', error)
      throw error
    }
  },

  // Delete all notifications
  async deleteAll() {
    try {
      const response = await api.delete(NOTIFICATIONS_BASE)
      return response.data
    } catch (error) {
      console.error('❌ Error deleting all notifications:', error)
      throw error
    }
  },
}
