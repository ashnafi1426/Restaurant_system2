<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'

interface Props {
  title: string
  value: string | number
  trend?: number
  icon: string
  color?: string
  subtitle?: string
}

withDefaults(defineProps<Props>(), {
  color: 'teal',
  trend: 0,
  subtitle: '',
})

const colorMap: Record<string, { bg: string; text: string; border: string }> = {
  teal: { bg: 'bg-teal-50', text: 'text-teal-600', border: 'border-teal-100' },
  blue: { bg: 'bg-blue-50', text: 'text-blue-600', border: 'border-blue-100' },
  green: { bg: 'bg-green-50', text: 'text-green-600', border: 'border-green-100' },
  red: { bg: 'bg-red-50', text: 'text-red-600', border: 'border-red-100' },
  purple: { bg: 'bg-purple-50', text: 'text-purple-600', border: 'border-purple-100' },
  amber: { bg: 'bg-amber-50', text: 'text-amber-600', border: 'border-amber-100' },
}

const trendColor = (trend: number) => {
  return trend > 0 ? 'text-emerald-600' : trend < 0 ? 'text-rose-600' : 'text-slate-600'
}
</script>

<template>
  <div
    class="bg-white rounded-lg px-3 sm:px-4 py-3 sm:py-4 border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-300 group"
    :class="colorMap[color]?.border"
  >
    <div class="flex items-start justify-between gap-2 sm:gap-3">
      <!-- Left Content -->
      <div class="flex-1 min-w-0">
        <p
          class="text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 sm:mb-1.5"
        >
          {{ title }}
        </p>
        <h3
          class="text-xl sm:text-2xl font-bold text-gray-900 mb-1 sm:mb-1.5 tracking-tight"
        >
          {{ value }}
        </h3>

        <!-- Subtitle or Trend -->
        <div v-if="subtitle" class="text-[10px] sm:text-xs text-gray-600 font-medium">
          {{ subtitle }}
        </div>
        <div v-else-if="trend && trend !== 0" class="flex items-center gap-1">
          <span
            v-if="trend > 0"
            class="inline-flex items-center gap-0.5 text-[10px] sm:text-xs font-semibold"
            :class="trendColor(trend)"
          >
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M7 14c.5.8 2 2 5 2s4.5-1.2 5-2M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"
              />
            </svg>
            {{ trend }}% vs last month
          </span>
          <span
            v-else
            class="inline-flex items-center gap-0.5 text-[10px] sm:text-xs font-semibold"
            :class="trendColor(trend)"
          >
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M7 10c.5-.8 2-2 5-2s4.5 1.2 5 2M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"
              />
            </svg>
            {{ Math.abs(trend) }}% vs last month
          </span>
        </div>
      </div>

      <!-- Icon -->
      <div
        class="w-10 h-10 sm:w-11 sm:h-11 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300"
        :class="[colorMap[color]?.bg]"
      >
        <SvgIcon :class="[colorMap[color]?.text]" type="mdi" :path="icon" :size="24" />
      </div>
    </div>
  </div>
</template>
