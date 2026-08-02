<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">Notifications</h1>
          <p class="text-slate-600 mt-2">Stay updated with your delivery notifications</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="relative w-12 h-12">
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
              </svg>
              <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
                </svg>
              </div>
            </div>
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading notifications...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
          <p class="text-red-700 font-semibold">Error loading notifications</p>
          <p class="text-red-600 text-sm mt-2">{{ error }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="notifications.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
          <p class="text-slate-600 text-lg">No notifications</p>
          <p class="text-slate-500 mt-2">You're all caught up!</p>
        </div>

        <!-- Notifications List -->
        <div v-else class="space-y-4">
          <div v-for="notif in notifications" :key="notif.id" :class="[
            'bg-white rounded-lg shadow-sm p-6 border-l-4 transition',
            notif.read ? 'border-slate-300' : 'border-blue-500 bg-blue-50'
          ]">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p :class="['font-semibold', notif.read ? 'text-slate-900' : 'text-blue-900']">
                  {{ notif.title || notif.type }}
                </p>
                <p :class="['text-sm mt-1', notif.read ? 'text-slate-600' : 'text-blue-700']">
                  {{ notif.message || notif.content }}
                </p>
                <p class="text-xs mt-3" :class="notif.read ? 'text-slate-500' : 'text-blue-600'">
                  {{ formatDate(notif.created_at) }}
                </p>
              </div>
              <button
                v-if="!notif.read"
                @click="markAsRead(notif.id)"
                class="ml-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition whitespace-nowrap"
              >
                Mark Read
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'

const loading = ref(true)
const error = ref<string | null>(null)
const notifications = ref<any[]>([])

const formatDate = (date: string) => {
  const d = new Date(date)
  const now = new Date()
  const diff = now.getTime() - d.getTime()
  
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  
  if (minutes < 1) return 'just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`
  
  return d.toLocaleDateString()
}

const fetchNotifications = async () => {
  try {
    loading.value = true
    error.value = null
    
    console.log('[Notifications] Loading notifications...')
    
    // For now, use a placeholder or mock data
    // Once backend endpoint is available, use: const data = await waiterService.getNotifications()
    notifications.value = []
    
  } catch (err: any) {
    console.error('[Notifications] Error:', err)
    error.value = err.message || 'Failed to load notifications'
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notificationId: string) => {
  try {
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.read = true
    }
  } catch (err: any) {
    console.error('[Notifications] Error marking as read:', err)
  }
}

onMounted(() => {
  fetchNotifications()
})
</script>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>
