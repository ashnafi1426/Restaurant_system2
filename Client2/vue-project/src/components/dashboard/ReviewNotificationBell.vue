<template>
  <div class="review-notification-bell relative">
    <!-- Notification Bell Button -->
    <button
      @click="showDropdown = !showDropdown"
      class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex-shrink-0"
      title="Review Notifications"
    >
      <!-- Bell Icon -->
      <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>

      <!-- Unread Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showDropdown"
        class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-900 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 z-50"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 p-4">
          <h3 class="text-sm font-bold text-gray-900 dark:text-white">Review Notifications</h3>
          <button
            @click="showDropdown = false"
            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="loading" class="p-4 text-center">
            <p class="text-sm text-gray-500">Loading...</p>
          </div>

          <div v-else-if="notifications.length === 0" class="p-6 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="text-sm text-gray-600">No notifications</p>
          </div>

          <div v-else class="divide-y divide-slate-200 dark:divide-slate-700">
            <div
              v-for="notification in notifications.slice(0, 5)"
              :key="notification.id"
              :class="[
                'p-4 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer',
                !notification.is_read ? 'bg-blue-50 dark:bg-blue-900/20' : ''
              ]"
              @click="markAsRead(notification.id)"
            >
              <!-- Icon -->
              <div class="flex gap-3">
                <div class="flex-shrink-0">
                  <span class="inline-flex items-center justify-center h-8 w-8 rounded-full"
                    :class="[
                      notification.notification_type === 'new_review' ? 'bg-blue-100 dark:bg-blue-900' :
                      notification.notification_type === 'review_approved' ? 'bg-green-100 dark:bg-green-900' :
                      'bg-red-100 dark:bg-red-900'
                    ]"
                  >
                    <span class="text-lg">
                      {{ notification.notification_type === 'new_review' ? '📝' :
                         notification.notification_type === 'review_approved' ? '' : '' }}
                    </span>
                  </span>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    <span v-if="notification.notification_type === 'new_review'">New Review</span>
                    <span v-else-if="notification.notification_type === 'review_approved'">Approved</span>
                    <span v-else>Rejected</span>
                  </p>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                    {{ notification.message }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    {{ formatTime(notification.created_at) }}
                  </p>
                </div>

                <!-- Unread Indicator -->
                <div v-if="!notification.is_read" class="flex-shrink-0">
                  <div class="h-2 w-2 bg-blue-600 rounded-full mt-1"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-slate-200 dark:border-slate-700 p-3 text-center">
          <router-link
            to="/reviews"
            @click="showDropdown = false"
            class="text-sm text-blue-600 hover:text-blue-700 font-semibold"
          >
            View All Notifications
          </router-link>
        </div>
      </div>
    </transition>

    <!-- Backdrop -->
    <div
      v-if="showDropdown"
      class="fixed inset-0 z-40"
      @click="showDropdown = false"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import reviewService from '@/services/reviewService'
import type { ReviewNotification } from '@/types/review'

const showDropdown = ref(false)
const notifications = ref<ReviewNotification[]>([])
const unreadCount = ref(0)
const loading = ref(false)
let pollInterval: NodeJS.Timeout | null = null

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const hours = Math.floor(diff / (1000 * 60 * 60))
  const days = Math.floor(hours / 24)

  if (hours < 1) return 'Just now'
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`
  
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const loadNotifications = async () => {
  loading.value = true
  try {
    const data = await reviewService.getReviewNotifications(1, 10)
    notifications.value = (data.data as ReviewNotification[]) || []
    unreadCount.value = await reviewService.getUnreadNotificationCount()
  } catch (error) {
    console.error('Failed to load notifications:', error)
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notificationId: string) => {
  try {
    await reviewService.markNotificationAsRead(notificationId)
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification && !notification.is_read) {
      notification.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

onMounted(() => {
  loadNotifications()

  // Poll for new notifications every 30 seconds
  pollInterval = setInterval(() => {
    loadNotifications()
  }, 30000)
})

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
})
</script>

<style scoped>
.review-notification-bell {
  /* Component styles */
}
</style>
