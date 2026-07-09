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
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">MAINTENANCE ALERTS</h3>

    <div v-if="alerts.length === 0" class="text-center py-8">
      <p class="text-gray-500 text-sm">No active alerts</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        :class="[getSeverityColor(alert.severity), 'p-4 rounded-lg border']"
      >
        <div class="flex gap-3">
          <!-- Icon -->
          <div class="flex-shrink-0 mt-0.5">
            <SvgIcon
              type="mdi"
              :path="getSeverityIcon(alert.severity)"
              :size="20"
              :class="getSeverityIconColor(alert.severity)"
            />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h4 :class="[getSeverityBadgeColor(alert.severity), 'text-sm font-semibold']">
              {{ alert.title }}
            </h4>
            <p class="text-xs text-gray-600 mt-1">{{ alert.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
