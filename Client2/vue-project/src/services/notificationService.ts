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

// Request deduplication and debouncing
const pendingRequests = new Map<string, Promise<any>>()
let lastUnreadCountFetch = 0
const UNREAD_COUNT_DEBOUNCE_MS = 2000 // Don't fetch more than once every 2 seconds

// Singleton polling instance tracker
let globalPollIntervalId: number | null = null
let lastKnownUnreadCount = 0

export const notificationService = {
  getNotifications(limit: number = 10) {
    const key = `notifications-${limit}`
    
    // Return existing pending request if one exists
    if (pendingRequests.has(key)) {
      return pendingRequests.get(key)!
    }

    const request = api.get('/notifications', { params: { limit } }).finally(() => {
      pendingRequests.delete(key)
    })

    pendingRequests.set(key, request)
    return request
  },

  getUnreadCount() {
    const now = Date.now()
    
    // Debounce: if we fetched recently, return the cached promise
    if (pendingRequests.has('unread-count')) {
      return pendingRequests.get('unread-count')!
    }

    // If fetched within debounce window, skip
    if (now - lastUnreadCountFetch < UNREAD_COUNT_DEBOUNCE_MS) {
      return Promise.resolve({ data: { unread_count: 0 } })
    }

    lastUnreadCountFetch = now
    const request = api.get('/notifications/unread-count').finally(() => {
      pendingRequests.delete('unread-count')
    })

    pendingRequests.set('unread-count', request)
    return request
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
    callback: (newCount: number) => void,
    interval: number = 30000,
  ) {
    // Prevent multiple polling instances - return existing interval if already active
    if (globalPollIntervalId !== null) {
      console.warn('[NotificationService] Polling already active, reusing existing interval')
      return globalPollIntervalId
    }

    // Enforce minimum interval of 30 seconds
    const safeInterval = Math.max(interval, 30000)

    const intervalId = setInterval(async () => {
      // Skip polling if tab is hidden
      if (typeof document !== 'undefined' && document.visibilityState === 'hidden') {
        return
      }

      try {
        const response = await api.get('/notifications/unread-count')
        const newCount = response.data?.unread_count || response.data?.count || 0

        // Only notify if count has increased
        if (newCount > lastKnownUnreadCount) {
          lastKnownUnreadCount = newCount
          callback(newCount)
        }
      } catch (error: any) {
        console.error('[NotificationService] Polling error:', error)

        // Stop polling on auth errors
        if (error.response?.status === 401 || error.response?.status === 403) {
          console.warn('[NotificationService] Auth error, stopping polling')
          clearInterval(intervalId)
          globalPollIntervalId = null
          lastKnownUnreadCount = 0
          return
        }
        // For other errors, continue polling (network glitches should not kill the interval)
      }
    }, safeInterval)

    globalPollIntervalId = intervalId
    return intervalId
  },

  unsubscribeFromNotifications(intervalId: number) {
    if (intervalId === globalPollIntervalId) {
      clearInterval(intervalId)
      globalPollIntervalId = null
      lastKnownUnreadCount = 0
    }
  },
}

export default notificationService
