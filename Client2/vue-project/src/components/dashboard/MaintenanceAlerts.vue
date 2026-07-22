<script setup lang="ts">
import type { MaintenanceAlert } from '../../types/dashboard'

interface Props {
  alerts?: MaintenanceAlert[]
}

withDefaults(defineProps<Props>(), {
  alerts: () => [],
})

const getSeverityColor = (severity: string) => {
  const colors: Record<string, string> = {
    high: 'bg-red-50 border-red-200 hover:bg-red-100/50',
    medium: 'bg-amber-50 border-amber-200 hover:bg-amber-100/50',
    low: 'bg-blue-50 border-blue-200 hover:bg-blue-100/50',
  }
  return colors[severity] || 'bg-gray-50 border-gray-200'
}

const getSeverityBadgeColor = (severity: string) => {
  const colors: Record<string, string> = {
    high: 'text-red-700 bg-red-100',
    medium: 'text-amber-700 bg-amber-100',
    low: 'text-blue-700 bg-blue-100',
  }
  return colors[severity] || 'text-gray-700 bg-gray-100'
}

const getSeverityIcon = (severity: string) => {
  const icons: Record<string, string> = {
    high: 'high',
    medium: 'medium',
    low: 'low',
  }
  return icons[severity] || 'low'
}
</script>

<template>
  <div
    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8 hover:shadow-md transition-shadow duration-300"
  >
    <!-- Title Section -->
    <div class="flex items-center justify-between gap-3 mb-6 sm:mb-8">
      <div>
        <h3
          class="text-xl sm:text-2xl font-bold text-slate-900 uppercase tracking-wide"
        >
          Maintenance Alerts
        </h3>
        <p class="text-sm text-slate-600 mt-1">System status and maintenance tasks</p>
      </div>
      <button class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-semibold rounded-lg transition-colors">
        Clear All
      </button>
    </div>

    <!-- Empty State -->
    <div v-if="alerts.length === 0" class="text-center py-16">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="text-slate-600 font-semibold text-lg">All systems operational</p>
      <p class="text-sm text-slate-500 mt-1">No active alerts at the moment</p>
    </div>

    <!-- Alerts List -->
    <div v-else class="space-y-3 sm:space-y-4">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        :class="[
          getSeverityColor(alert.severity),
          'px-4 sm:px-5 py-4 sm:py-5 rounded-xl border transition-all duration-300 group',
        ]"
      >
        <div class="flex gap-3 sm:gap-4">
          <!-- Icon -->
          <div class="flex-shrink-0 mt-0.5">
            <div v-if="alert.severity === 'high'" class="w-6 h-6 rounded-full bg-red-600 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2" />
              </svg>
            </div>
            <div v-else-if="alert.severity === 'medium'" class="w-6 h-6 rounded-full bg-amber-600 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2" />
              </svg>
            </div>
            <div v-else class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h4 class="text-sm sm:text-base font-bold text-slate-900">
                  {{ alert.title }}
                </h4>
                <p class="text-xs sm:text-sm text-slate-600 mt-1.5 leading-relaxed">{{ alert.description }}</p>
              </div>
              <span
                :class="[getSeverityBadgeColor(alert.severity), 'text-[10px] sm:text-xs font-bold px-2 sm:px-2.5 py-1 rounded-full flex-shrink-0 whitespace-nowrap']"
              >
                {{ alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1) }}
              </span>
            </div>
            
            <div class="flex gap-2 mt-3 flex-wrap">
              <button
                class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-all"
                :class="
                  alert.severity === 'high' ? 'bg-red-100 text-red-700 hover:bg-red-200' :
                  alert.severity === 'medium' ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' :
                  'bg-blue-100 text-blue-700 hover:bg-blue-200'
                "
              >
                ✓ Acknowledge
              </button>
              <button
                class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border text-slate-600 hover:bg-slate-50 transition-all"
              >
                View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
