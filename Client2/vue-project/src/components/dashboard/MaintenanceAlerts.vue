<script setup lang="ts">
import type { MaintenanceAlert } from '../../types/dashboard'
import { mdiAlert, mdiAlertCircle, mdiAlertOctagon } from '@mdi/js'
import SvgIcon from '@jamescoyle/vue-icon'

interface Props {
  alerts?: MaintenanceAlert[]
}

withDefaults(defineProps<Props>(), {
  alerts: () => [],
})
const getSeverityIcon = (severity: string) => {
  const icons: Record<string, string> = {
    high: mdiAlertOctagon,
    medium: mdiAlertCircle,
    low: mdiAlert,
  }
  return icons[severity] || mdiAlert
}
const getSeverityColor = (severity: string) => {
  const colors: Record<string, string> = {
    high: 'bg-red-50 border-red-200',
    medium: 'bg-amber-50 border-amber-200',
    low: 'bg-blue-50 border-blue-200',
  }
  return colors[severity] || 'bg-gray-50 border-gray-200'
}
const getSeverityBadgeColor = (severity: string) => {
  const colors: Record<string, string> = {
    high: 'text-red-700',
    medium: 'text-amber-700',
    low: 'text-blue-700',
  }
  return colors[severity] || 'text-gray-700'
}

const getSeverityIconColor = (severity: string) => {
  const colors: Record<string, string> = {
    high: 'text-red-600',
    medium: 'text-amber-600',
    low: 'text-blue-600',
  }
  return colors[severity] || 'text-gray-600'
}
</script>

<template>
  <div
    class="bg-white rounded-lg border border-gray-200 px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-5 md:py-6 lg:py-8"
  >
    <h3
      class="text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900 mb-3 sm:mb-4 md:mb-5"
    >
      MAINTENANCE ALERTS
    </h3>

    <div v-if="alerts.length === 0" class="text-center py-6 sm:py-8 md:py-10">
      <p class="text-gray-500 text-xs sm:text-sm md:text-base">No active alerts</p>
    </div>

    <div v-else class="space-y-2 sm:space-y-3 md:space-y-4">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        :class="[
          getSeverityColor(alert.severity),
          'px-3 sm:px-4 md:px-5 py-3 sm:py-4 rounded-lg border',
        ]"
      >
        <div class="flex gap-2 sm:gap-3 md:gap-4">
          <!-- Icon -->
          <div class="flex-shrink-0 mt-0.5">
            <SvgIcon
              type="mdi"
              :path="getSeverityIcon(alert.severity)"
              :size="18"
              class="sm:w-5 md:w-6"
              :class="getSeverityIconColor(alert.severity)"
            />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h4
              :class="[
                getSeverityBadgeColor(alert.severity),
                'text-xs sm:text-sm md:text-base font-semibold',
              ]"
            >
              {{ alert.title }}
            </h4>
            <p class="text-xs sm:text-xs md:text-sm text-gray-600 mt-1">{{ alert.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
