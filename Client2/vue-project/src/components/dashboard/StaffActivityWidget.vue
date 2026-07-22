<script setup lang="ts">
import type { StaffActivityItem } from '../../types/dashboard'

interface Props {
  activities?: StaffActivityItem[]
}

withDefaults(defineProps<Props>(), {
  activities: () => [],
})

const getActivityColor = (index: number) => {
  const colors = [
    'from-teal-400 to-teal-600',
    'from-blue-400 to-blue-600',
    'from-purple-400 to-purple-600',
    'from-pink-400 to-pink-600',
  ]
  return colors[index % colors.length]
}

const getActivityBg = (index: number) => {
  const colors = [
    'from-teal-50 to-transparent',
    'from-blue-50 to-transparent',
    'from-purple-50 to-transparent',
    'from-pink-50 to-transparent'
  ]
  return colors[index % colors.length]
}

const formatTime = (timestamp: string) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`
  return date.toLocaleDateString()
}
</script>

<template>
  <div
    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8 hover:shadow-md transition-shadow duration-300 relative h-full flex flex-col"
  >
    <!-- Header Section -->
    <div
      class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 mb-6 sm:mb-8 pb-4 sm:pb-5 border-b border-gray-100"
    >
      <div>
        <h3
          class="text-xl sm:text-2xl font-bold text-slate-900 uppercase tracking-wide"
        >
          Staff Activity
        </h3>
        <p class="text-sm text-slate-600 mt-1">Recent staff actions and updates</p>
      </div>
      <router-link
        to="/admin/activity"
        class="px-4 sm:px-5 py-2 sm:py-2.5 bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white text-xs sm:text-sm font-semibold rounded-lg transition-all shadow-sm hover:shadow-md whitespace-nowrap group"
      >
        <span class="flex items-center gap-2">
          View All
          <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </span>
      </router-link>
    </div>

    <!-- Empty State -->
    <div v-if="activities.length === 0" class="text-center py-16 flex-1 flex items-center justify-center">
      <div>
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-slate-600 font-medium">No recent activity</p>
        <p class="text-sm text-slate-500 mt-1">Activity will appear here</p>
      </div>
    </div>

    <!-- Activity List -->
    <div v-else class="space-y-3 flex-1 overflow-y-auto pr-2">
      <div
        v-for="(activity, index) in activities"
        :key="activity.id"
        class="flex gap-3 items-start p-3.5 rounded-xl bg-gradient-to-r hover:shadow-md transition-all duration-300 border border-gray-100/50 group"
        :class="getActivityBg(index)"
      >
        <!-- Avatar -->
        <div
          :class="[
            'bg-gradient-to-br ' + getActivityColor(index),
            'w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm font-bold text-white text-xs group-hover:scale-110 transition-transform',
          ]"
        >
          {{ activity.staff_initials }}
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-slate-900 truncate">
            {{ activity.staff_name }}
          </p>
          <p class="text-xs text-slate-600 mt-1.5 line-clamp-2 leading-relaxed">
            {{ activity.action }}
          </p>
          <p class="text-[11px] text-slate-400 mt-2 font-medium">
            {{ formatTime(activity.timestamp) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Footer Action -->
    <div class="mt-4 pt-4 border-t border-gray-100">
      <button
        class="w-full px-4 py-2.5 bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border border-blue-200 text-blue-700 rounded-lg font-semibold text-sm transition-all duration-300"
      >
        <span class="flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Log Activity
        </span>
      </button>
    </div>
  </div>
</template>

<style scoped>
div::-webkit-scrollbar {
  width: 4px;
}
div::-webkit-scrollbar-track {
  background: transparent;
}
div::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 9999px;
}
div::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}
</style>
