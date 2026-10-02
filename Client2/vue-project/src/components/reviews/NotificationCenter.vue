<template>
  <div class="notification-center">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold">{{ languageStore.t('review_notifications', 'Review Notifications') }}</h3>
      <span v-if="unreadCount > 0" class="bg-red-600 text-white rounded-full px-3 py-1 text-sm font-semibold">
        {{ unreadCount }} {{ languageStore.t('new', 'new') }}
      </span>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="animate-pulse h-20 bg-gray-200 rounded"></div>
    </div>

    <div v-else-if="notifications.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <p class="text-gray-600">{{ languageStore.t('no_notifications', 'No notifications') }}</p>
    </div>

    <div v-else class="space-y-3">
      <div 
        v-for="notification in notifications" 
        :key="notification.id"
        @click="markAsRead(notification.id)"
        :class="[
          'p-4 rounded-lg cursor-pointer transition-colors',
          notification.is_read ? 'bg-gray-50' : 'bg-blue-50 border-l-4 border-blue-500'
        ]"
      >
        <div class="flex items-start gap-3">
          <span class="text-2xl flex-shrink-0">
            <span v-if="notification.notification_type === 'new_review'">📝</span>
            <span v-else-if="notification.notification_type === 'review_approved'"></span>
            <span v-else></span>
          </span>

          <div class="flex-1">
            <p class="font-semibold text-gray-900">
              <span v-if="notification.notification_type === 'new_review'">{{ languageStore.t('new_review_submitted', 'New Review Submitted') }}</span>
              <span v-else-if="notification.notification_type === 'review_approved'">{{ languageStore.t('review_approved', 'Review Approved') }}</span>
              <span v-else>{{ languageStore.t('review_rejected', 'Review Rejected') }}</span>
            </p>
            <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ formatDate(notification.created_at) }}</p>
          </div>

          <div v-if="!notification.is_read" class="flex-shrink-0">
            <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-4">
      <button
        @click="currentPage = Math.max(1, currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 cursor-pointer"
      >
        {{ languageStore.t('previous', 'Prev') }}
      </button>
      
      <span class="text-xs text-gray-600">
        {{ currentPage }} / {{ totalPages }}
      </span>

      <button
        @click="currentPage = Math.min(totalPages, currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 cursor-pointer"
      >
        {{ languageStore.t('next', 'Next') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import reviewService from '@/services/reviewService'
import type { ReviewNotification } from '@/types/review'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const notifications = ref<ReviewNotification[]>([])
const loading = ref(true)
const unreadCount = ref(0)
const currentPage = ref(1)
const totalPages = ref(1)

const formatDate = (dateString: string) => {
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
    const data = await reviewService.getReviewNotifications(currentPage.value, 10)
    notifications.value = (data.data as ReviewNotification[]) || []
    totalPages.value = data.last_page || 1

    const count = await reviewService.getUnreadNotificationCount()
    unreadCount.value = count
  } catch (error) {
    console.error('[NotificationCenter] Failed to load notifications:', error)
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
    console.error('[NotificationCenter] Failed to mark notification as read:', error)
  }
}

watch(currentPage, () => {
  loadNotifications()
})

onMounted(() => {
  loadNotifications()
})
</script>

<style scoped>
.notification-center {
  background: white;
  border-radius: 8px;
  padding: 16px;
}
</style>
