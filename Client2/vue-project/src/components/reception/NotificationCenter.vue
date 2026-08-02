<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notificationStore'
import type { NotificationData } from '@/services/notificationService'
import { Bell, X, Check, Trash2 } from 'lucide-vue-next'

const notificationStore = useNotificationStore()
const isOpen = ref(false)
const showDropdown = ref(false)

// Close dropdown when clicking outside
const closeDropdown = () => {
  showDropdown.value = false
}

// Get notification icon based on type
const getNotificationIcon = (type: string) => {
  const icons: Record<string, string> = {
    booking: '📅',
    check_in: '🔓',
    check_out: '🚪',
    cancellation: '❌',
    system: 'ℹ️',
  }
  return icons[type] || '📬'
}

// Get notification color based on type
const getNotificationColor = (type: string) => {
  const colors: Record<string, string> = {
    booking: 'bg-blue-50 border-blue-200 text-blue-900',
    check_in: 'bg-green-50 border-green-200 text-green-900',
    check_out: 'bg-yellow-50 border-yellow-200 text-yellow-900',
    cancellation: 'bg-red-50 border-red-200 text-red-900',
    system: 'bg-gray-50 border-gray-200 text-gray-900',
  }
  return colors[type] || 'bg-white border-gray-200 text-gray-900'
}

// Format date
const formatDate = (date: string | undefined) => {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Handle mark as read
const handleMarkAsRead = async (notificationId: string | undefined) => {
  if (notificationId) {
    console.log('🔵 [UI] Mark as read clicked for:', notificationId)
    try {
      await notificationStore.markNotificationAsRead(notificationId)
      console.log('🔵 [UI] Mark as read successful')
    } catch (error) {
      console.error('🔴 [UI] Error marking as read:', error)
    }
  }
}

// Handle delete
const handleDelete = async (notificationId: string | undefined) => {
  if (notificationId) {
    console.log('🗑️ [UI] Delete clicked for:', notificationId)
    try {
      await notificationStore.deleteNotification(notificationId)
      console.log('🗑️ [UI] Delete successful')
    } catch (error) {
      console.error('🗑️ [UI] Error deleting:', error)
    }
  }
}

// Handle clear all
const handleClearAll = async () => {
  console.log('🗑️ [UI] Clear all clicked')
  try {
    await notificationStore.clearAllNotifications()
    console.log('🗑️ [UI] Clear all successful')
  } catch (error) {
    console.error('🗑️ [UI] Error clearing all:', error)
  }
}

// Handle mark all read
const handleMarkAllRead = async () => {
  console.log('✓ [UI] Mark all read clicked')
  try {
    await notificationStore.markAllAsRead()
    console.log('✓ [UI] Mark all read successful')
  } catch (error) {
    console.error('✓ [UI] Error marking all read:', error)
  }
}

// Start polling on mount (only for receptionist, not for manager)
onMounted(async () => {
  const { useAuthStore } = await import('@/stores/auth')
  const auth = useAuthStore()
  
  await notificationStore.fetchNotifications()
  
  // Only enable polling for receptionist role (manager has different notification handling)
  if (auth.user?.role === 'receptionist') {
    notificationStore.startPolling(5000) // Poll every 5 seconds
  }
})

// Stop polling on unmount
onUnmounted(() => {
  notificationStore.stopPolling()
})
</script>

<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button
      @click="showDropdown = !showDropdown"
      class="relative p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all"
      title="Notifications"
    >
      <Bell class="w-6 h-6" />

      <!-- Unread Badge -->
      <span
        v-if="notificationStore.unreadCount > 0"
        class="absolute top-0 right-0 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full"
      >
        {{ notificationStore.unreadCount > 99 ? '99+' : notificationStore.unreadCount }}
      </span>
    </button>

    <!-- Notification Dropdown Panel -->
    <div
      v-if="showDropdown"
      @click.self="closeDropdown"
      class="fixed inset-0 z-40"
      @click="closeDropdown"
    />

    <div
      v-show="showDropdown"
      class="absolute right-0 mt-2 w-96 bg-white dark:bg-slate-800 rounded-lg shadow-2xl border border-gray-200 dark:border-slate-700 z-50 overflow-hidden max-h-[600px] flex flex-col"
    >
      <!-- Header -->
      <div
        class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-900 dark:to-blue-800 text-white px-4 py-3 flex items-center justify-between flex-shrink-0"
      >
        <div class="flex items-center gap-2">
          <Bell class="w-5 h-5" />
          <h3 class="font-bold">Notifications</h3>
          <span
            v-if="notificationStore.unreadCount > 0"
            class="ml-1 px-2 py-0.5 bg-white/30 dark:bg-white/20 rounded-full text-xs font-semibold"
          >
            {{ notificationStore.unreadCount }} new
          </span>
        </div>
        <button @click="closeDropdown" class="text-white/70 dark:text-white/60 hover:text-white dark:hover:text-white transition">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Notifications List -->
      <div class="overflow-y-auto flex-1">
        <!-- Loading State -->
        <div v-if="notificationStore.loading" class="p-4 space-y-3">
          <div v-for="i in 3" :key="i" class="h-20 bg-gray-200 dark:bg-slate-700 rounded-lg animate-pulse" />
        </div>

        <!-- Empty State -->
        <div
          v-else-if="notificationStore.notifications.length === 0"
          class="flex flex-col items-center justify-center py-12 px-4"
        >
          <Bell class="w-12 h-12 text-gray-300 dark:text-slate-600 mb-2" />
          <p class="text-gray-500 dark:text-slate-400 text-sm">No notifications yet</p>
        </div>

        <!-- Notifications -->
        <div v-else class="divide-y divide-gray-200 dark:divide-slate-700">
          <div
            v-for="notification in notificationStore.notifications"
            :key="notification.id"
            :class="[
              'p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition cursor-pointer border-l-4',
              !notification.read ? 'bg-blue-50/50 dark:bg-blue-900/30' : 'bg-white dark:bg-slate-800',
              notification.type === 'booking' ? 'border-l-blue-500 dark:bg-slate-800' : '',
              notification.type === 'check_in' ? 'border-l-green-500 dark:bg-slate-800' : '',
              notification.type === 'check_out' ? 'border-l-yellow-500 dark:bg-slate-800' : '',
              notification.type === 'cancellation' ? 'border-l-red-500 dark:bg-slate-800' : '',
              notification.type === 'system' ? 'border-l-gray-500 dark:bg-slate-800' : '',
            ]"
            @click="handleMarkAsRead(notification.id)"
          >
            <div class="flex items-start gap-3">
              <!-- Icon -->
              <div class="text-2xl flex-shrink-0">
                {{ getNotificationIcon(notification.type) }}
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <div>
                    <h4 class="font-semibold text-sm text-gray-900 dark:text-white">
                      {{ notification.title }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">
                      {{ notification.message }}
                    </p>

                    <!-- Additional Info -->
                    <div
                      v-if="notification.guest_name || notification.room_number"
                      class="mt-2 flex flex-wrap gap-2"
                    >
                      <span
                        v-if="notification.guest_name"
                        class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded text-xs"
                      >
                        👤 {{ notification.guest_name }}
                      </span>
                      <span
                        v-if="notification.room_number"
                        class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded text-xs"
                      >
                        🚪 Room {{ notification.room_number }}
                      </span>
                      <span
                        v-if="notification.room_type"
                        class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded text-xs"
                      >
                        🏨 {{ notification.room_type }}
                      </span>
                    </div>

                    <!-- Dates -->
                    <div
                      v-if="notification.check_in_date || notification.check_out_date"
                      class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500 dark:text-slate-500"
                    >
                      <span v-if="notification.check_in_date" class="flex items-center gap-1">
                        📍 Check-in: {{ formatDate(notification.check_in_date).split(' ')[0] }}
                      </span>
                      <span v-if="notification.check_out_date" class="flex items-center gap-1">
                        🚪 Check-out: {{ formatDate(notification.check_out_date).split(' ')[0] }}
                      </span>
                    </div>
                  </div>

                  <!-- Unread Indicator -->
                  <div
                    v-if="!notification.read"
                    class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-1.5"
                  />
                </div>

                <!-- Timestamp -->
                <p class="text-xs text-gray-500 dark:text-slate-500 mt-2">
                  {{ formatDate(notification.created_at) }}
                </p>

                <!-- Actions -->
                <div class="flex gap-2 mt-3">
                  <button
                    v-if="!notification.read"
                    @click.stop="handleMarkAsRead(notification.id)"
                    class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30 rounded transition"
                  >
                    <Check class="w-3.5 h-3.5" />
                    Mark Read
                  </button>
                  <button
                    @click.stop="handleDelete(notification.id)"
                    class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div
        v-if="notificationStore.notifications.length > 0"
        class="border-t border-gray-200 dark:border-slate-700 px-4 py-3 bg-gray-50 dark:bg-slate-700/50 flex gap-2 justify-between flex-shrink-0"
      >
        <button
          @click.prevent="handleMarkAllRead()"
          class="flex-1 px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded transition"
        >
          Mark All Read
        </button>
        <button
          @click.prevent="handleClearAll()"
          class="flex-1 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition"
        >
          Clear All
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Smooth scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Dark mode scrollbar */
:root.dark ::-webkit-scrollbar-thumb {
  background: #475569;
}

:root.dark ::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
</style>
