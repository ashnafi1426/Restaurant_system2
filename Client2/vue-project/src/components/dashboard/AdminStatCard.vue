<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'

interface Props {
  title: string
  value: string | number
  trend?: number
  icon: string
  color?: string
}

withDefaults(defineProps<Props>(), {
  color: 'teal',
  trend: 0,
})

const colorMap: Record<string, string> = {
  teal: 'text-teal-600',
  blue: 'text-blue-600',
  green: 'text-green-600',
  red: 'text-red-600',
  purple: 'text-purple-600',
}
</script>

<template>
  <div
    class="bg-white rounded-lg px-4 sm:px-6 md:px-8 lg:px-10 py-4 sm:py-5 md:py-6 lg:py-8 border border-gray-200 hover:shadow-md transition-all duration-300"
  >
    <div class="flex items-start justify-between gap-2 sm:gap-3 md:gap-4">
      <div class="flex-1 min-w-0">
        <p
          class="text-xs sm:text-xs md:text-sm lg:text-base font-semibold text-gray-500 uppercase tracking-wide mb-1 sm:mb-2"
        >
          {{ title }}
        </p>
        <h3
          class="text-2xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 mb-1 sm:mb-2 truncate"
        >
          {{ value }}
        </h3>
        <p
          v-if="trend && trend !== 0"
          class="text-xs sm:text-xs md:text-sm lg:text-base text-green-600 font-semibold"
        >
          <span v-if="trend > 0">↑ {{ trend }}%</span>
          <span v-else>↓ {{ Math.abs(trend) }}%</span>
          <span class="hidden sm:inline">vs last month</span>
          <span class="sm:hidden">prev</span>
        </p>
      </div>
      <SvgIcon
        :class="[colorMap[color] || colorMap.teal]"
        type="mdi"
        :path="icon"
        :size="28"
        class="sm:w-8 md:w-9 lg:w-10 flex-shrink-0"
      />
    </div>
  </div>
</template>
