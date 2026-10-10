<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useLanguageStore } from '@/stores/language'
import { useHotelStore } from '@/stores/hotelStore'
import { waiterNotificationService } from '@/services/waiterNotificationService'

interface NotificationItem {
  id: string | number
  title?: string
  type?: string
  message?: string
  content?: string
  read?: boolean
  created_at?: string
}

const languageStore = useLanguageStore()
const hotelStore = useHotelStore()

const loading = ref(true)
const error = ref<string | null>(null)
const notifications = ref<NotificationItem[]>([])

const formatRelativeTime = (date?: string) => {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return date
  const diff = Date.now() - d.getTime()

  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return languageStore.t('just_now', 'just now')
  if (minutes < 60) return `${minutes}${languageStore.t('m_ago', 'm ago')}`
  if (hours < 24) return `${hours}${languageStore.t('h_ago', 'h ago')}`
  if (days < 7) return `${days}${languageStore.t('d_ago', 'd ago')}`

  return d.toLocaleDateString()
}

const fetchNotifications = async () => {
  try {
    loading.value = true
    error.value = null
    const response = await waiterNotificationService.getNotifications().catch(() => null)
    if (response) {
      notifications.value = Array.isArray(response) ? response : response.data || []
    } else {
      notifications.value = []
    }
  } catch (err: any) {
    console.error('[Notifications] Error fetching notifications:', err)
    error.value = err.message || 'Failed to load notifications'
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notificationId: string | number) => {
  const notification = notifications.value.find((n) => n.id === notificationId)
  if (notification) {
    notification.read = true
  }
  try {
    await waiterNotificationService.markAsRead(String(notificationId))
  } catch (err: any) {
    console.error('[Notifications] Failed to mark as read on server:', err)
  }
}

onMounted(fetchNotifications)
watch(() => hotelStore.hotelId, fetchNotifications)
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">
            {{ languageStore.t('notifications', 'Notifications') }}
          </h1>
          <p class="text-slate-600 mt-2">
            {{
              languageStore.t(
                'stay_updated_with_delivery_notifications',
                'Stay updated with your delivery notifications',
              )
            }}
          </p>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="relative w-12 h-12 mx-auto">
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle
                  cx="50"
                  cy="50"
                  r="45"
                  fill="none"
                  stroke="#0EA5E9"
                  stroke-width="6"
                  opacity="0.3"
                />
              </svg>
              <div class="absolute inset-0 animate-spin">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle
                    cx="50"
                    cy="50"
                    r="45"
                    fill="none"
                    stroke="#FBBF24"
                    stroke-width="8"
                    stroke-linecap="round"
                    stroke-dasharray="70 280"
                  />
                </svg>
              </div>
            </div>
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">
              {{ languageStore.t('loading_notifications', 'Loading notifications...') }}
            </p>
          </div>
        </div>

        <div v-else-if="error" class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
          <p class="text-red-700 font-semibold">
            {{ languageStore.t('error_loading_notifications', 'Error loading notifications') }}
          </p>
          <p class="text-red-600 text-sm mt-2">{{ error }}</p>
        </div>

        <div
          v-else-if="notifications.length === 0"
          class="bg-white rounded-lg shadow-sm p-12 text-center"
        >
          <p class="text-slate-600 text-lg">
            {{ languageStore.t('no_notifications', 'No notifications') }}
          </p>
          <p class="text-slate-500 mt-2">
            {{ languageStore.t('all_caught_up', "You're all caught up!") }}
          </p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="notif in notifications"
            :key="notif.id"
            :class="[
              'bg-white rounded-lg shadow-sm p-6 border-l-4 transition',
              notif.read ? 'border-slate-300' : 'border-blue-500 bg-blue-50',
            ]"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <p :class="['font-semibold', notif.read ? 'text-slate-900' : 'text-blue-900']">
                  {{ notif.title || notif.type }}
                </p>
                <p :class="['text-sm mt-1', notif.read ? 'text-slate-600' : 'text-blue-700']">
                  {{ notif.message || notif.content }}
                </p>
                <p class="text-xs mt-3" :class="notif.read ? 'text-slate-500' : 'text-blue-600'">
                  {{ formatRelativeTime(notif.created_at) }}
                </p>
              </div>
              <button
                v-if="!notif.read"
                @click="markAsRead(notif.id)"
                class="ml-4 px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition whitespace-nowrap"
              >
                {{ languageStore.t('mark_read', 'Mark Read') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
