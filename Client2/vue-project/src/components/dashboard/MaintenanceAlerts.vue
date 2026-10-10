<script setup lang="ts">
import { computed } from 'vue'
import type { MaintenanceAlert } from '../../types/dashboard'
import { useLanguageStore } from '../../stores/language'

interface Props {
  alerts?: MaintenanceAlert[]
}

const props = withDefaults(defineProps<Props>(), {
  alerts: () => [],
})

const languageStore = useLanguageStore()

const activeAlerts = computed<MaintenanceAlert[]>(() => {
  const raw = props.alerts
  const list = Array.isArray(raw) ? raw : raw && typeof raw === 'object' ? Object.values(raw) : []
  return list
    .filter((a: any) => a && typeof a === 'object' && (a.title || a.description))
    .map((a: any) => ({
      ...a,
      severity: (a.severity || 'medium').toLowerCase(),
    }))
})

const severityThemes: Record<
  string,
  {
    card: string
    badge: string
    iconBg: string
    button: string
  }
> = {
  high: {
    card: 'bg-red-50 border-red-200 hover:bg-red-100/50',
    badge: 'text-red-700 bg-red-100',
    iconBg: 'bg-red-600',
    button: 'bg-red-100 text-red-700 hover:bg-red-200',
  },
  medium: {
    card: 'bg-amber-50 border-amber-200 hover:bg-amber-100/50',
    badge: 'text-amber-700 bg-amber-100',
    iconBg: 'bg-amber-600',
    button: 'bg-amber-100 text-amber-700 hover:bg-amber-200',
  },
  low: {
    card: 'bg-blue-50 border-blue-200 hover:bg-blue-100/50',
    badge: 'text-blue-700 bg-blue-100',
    iconBg: 'bg-blue-600',
    button: 'bg-blue-100 text-blue-700 hover:bg-blue-200',
  },
}

const getTheme = (severity: string) => severityThemes[severity] || severityThemes.low

const formatSeverity = (severity: string) => {
  const s = severity || 'medium'
  return languageStore.t(s, s.charAt(0).toUpperCase() + s.slice(1))
}
</script>

<template>
  <div
    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6 lg:p-8 hover:shadow-md transition-shadow duration-300"
  >
    <div class="flex items-center justify-between gap-3 mb-6 sm:mb-8">
      <div>
        <h3 class="text-xl sm:text-2xl font-bold text-slate-900 uppercase tracking-wide">
          {{ languageStore.t('maintenance_alerts', 'Maintenance Alerts') }}
        </h3>
        <p class="text-sm text-slate-600 mt-1">
          {{ languageStore.t('maintenance_alerts_desc', 'System status and maintenance tasks') }}
        </p>
      </div>
      <button
        class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-semibold rounded-lg transition-colors cursor-pointer"
      >
        {{ languageStore.t('clear_all', 'Clear All') }}
      </button>
    </div>

    <!-- Empty State -->
    <div v-if="activeAlerts.length === 0" class="text-center py-16">
      <div
        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4"
      >
        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>
      <p class="text-slate-600 font-semibold text-lg">
        {{ languageStore.t('all_systems_operational', 'All systems operational') }}
      </p>
      <p class="text-sm text-slate-500 mt-1">
        {{ languageStore.t('no_active_alerts', 'No active alerts at the moment') }}
      </p>
    </div>

    <!-- Alert List -->
    <div v-else class="space-y-3 sm:space-y-4">
      <div
        v-for="alert in activeAlerts"
        :key="alert.id"
        :class="[
          getTheme(alert.severity).card,
          'px-4 sm:px-5 py-4 sm:py-5 rounded-xl border transition-all duration-300 group',
        ]"
      >
        <div class="flex gap-3 sm:gap-4">
          <div class="flex-shrink-0 mt-0.5">
            <div
              :class="[
                getTheme(alert.severity).iconBg,
                'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0',
              ]"
            >
              <svg
                v-if="alert.severity === 'high' || alert.severity === 'medium'"
                class="w-4 h-4 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4v2"
                />
              </svg>
              <svg
                v-else
                class="w-4 h-4 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h4 class="text-sm sm:text-base font-bold text-slate-900">
                  {{ alert.title }}
                </h4>
                <p class="text-xs sm:text-sm text-slate-600 mt-1.5 leading-relaxed">
                  {{ alert.description }}
                </p>
              </div>
              <span
                :class="[
                  getTheme(alert.severity).badge,
                  'text-[10px] sm:text-xs font-bold px-2 sm:px-2.5 py-1 rounded-full flex-shrink-0 whitespace-nowrap',
                ]"
              >
                {{ formatSeverity(alert.severity) }}
              </span>
            </div>

            <div class="flex gap-2 mt-3 flex-wrap">
              <button
                :class="[
                  getTheme(alert.severity).button,
                  'text-xs font-semibold px-3 py-1.5 rounded-lg transition-all cursor-pointer',
                ]"
              >
                {{ languageStore.t('acknowledge', '✓ Acknowledge') }}
              </button>
              <button
                class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white border text-slate-600 hover:bg-slate-50 transition-all cursor-pointer"
              >
                {{ languageStore.t('view_details', 'View Details') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
