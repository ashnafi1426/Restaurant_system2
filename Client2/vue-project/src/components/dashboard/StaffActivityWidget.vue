<script setup lang="ts">
import type { StaffActivityItem } from '../../types/dashboard'
import { mdiPlus } from '@mdi/js'
import SvgIcon from '@jamescoyle/vue-icon'

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
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-5 md:py-6 lg:py-8 relative">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-3 mb-3 sm:mb-4 md:mb-6">
      <h3 class="text-xs sm:text-xs md:text-sm lg:text-base font-semibold text-gray-900 uppercase tracking-wide">
        STAFF ACTIVITY
      </h3>
      <router-link
        to="/admin/activity"
        class="text-teal-600 hover:text-teal-700 text-xs sm:text-sm font-medium whitespace-nowrap"
      >
        View All
      </router-link>
    </div>
    <div v-if="activities.length === 0" class="text-center py-6 sm:py-8 md:py-10">
      <p class="text-gray-500 text-xs sm:text-sm md:text-base">No recent activity</p>
    </div>

    <div v-else class="space-y-3 sm:space-y-4 pr-1 sm:pr-2">
      <div
        v-for="(activity, index) in activities"
        :key="activity.id"
        class="flex gap-2 sm:gap-3 items-start"
      >
        <!-- Avatar -->
        <div
          :class="[
            'bg-gradient-to-br ' + getActivityColor(index),
            'w-8 sm:w-9 md:w-10 h-8 sm:h-9 md:h-10 rounded-full flex items-center justify-center flex-shrink-0',
          ]"
        >
          <span class="text-xs font-bold text-white">{{ activity.staff_initials }}</span>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-xs sm:text-sm font-semibold text-gray-900 truncate">{{ activity.staff_name }}</p>
          <p class="text-xs text-gray-600 mt-0.5 truncate">{{ activity.action }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ activity.timestamp }}</p>
        </div>
      </div>
    </div>

    <!-- Floating Action Button -->
    <button
      class="absolute bottom-4 sm:bottom-6 right-4 sm:right-6 w-10 sm:w-11 md:w-12 h-10 sm:h-11 md:h-12 rounded-full bg-teal-600 hover:bg-teal-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center group flex-shrink-0"
      title="Add new activity"
    >
      <SvgIcon type="mdi" :path="mdiPlus" :size="20" class="sm:w-5 md:w-6" />
    </button>
  </div>
</template>
