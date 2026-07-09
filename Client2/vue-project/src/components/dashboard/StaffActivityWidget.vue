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
  <div class="bg-white rounded-lg border border-gray-200 p-6 relative">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-semibold text-gray-900 uppercase tracking-wide text-xs">
        STAFF ACTIVITY
      </h3>
      <router-link
        to="/admin/activity"
        class="text-teal-600 hover:text-teal-700 text-sm font-medium"
      >
        View All
      </router-link>
    </div>
    <div v-if="activities.length === 0" class="text-center py-8">
      <p class="text-gray-500 text-sm">No recent activity</p>
    </div>

    <div v-else class="space-y-4 pr-2">
      <div
        v-for="(activity, index) in activities"
        :key="activity.id"
        class="flex gap-3 items-start"
      >
        <!-- Avatar -->
        <div
          :class="[
            'bg-gradient-to-br ' + getActivityColor(index),
            'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
          ]"
        >
          <span class="text-xs font-bold text-white">{{ activity.staff_initials }}</span>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-900">{{ activity.staff_name }}</p>
          <p class="text-xs text-gray-600 mt-0.5">{{ activity.action }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ activity.timestamp }}</p>
        </div>
      </div>
    </div>

    <!-- Floating Action Button -->
    <button
      class="absolute bottom-6 right-6 w-12 h-12 rounded-full bg-teal-600 hover:bg-teal-700 text-white shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center group"
      title="Add new activity"
    >
      <SvgIcon type="mdi" :path="mdiPlus" :size="24" />
    </button>
  </div>
</template>
