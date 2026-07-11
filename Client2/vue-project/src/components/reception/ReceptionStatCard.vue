<script setup lang="ts">
interface Props {
  title: string
  value: number | string
  icon: string
  color?: 'teal' | 'red' | 'blue' | 'green' | 'orange' | 'purple'
  subtext?: string
}

withDefaults(defineProps<Props>(), {
  color: 'teal',
  subtext: 'Today',
})

const colorMap = {
  teal: { text: 'text-teal-600', icon: 'text-teal-500' },
  red: { text: 'text-red-600', icon: 'text-red-500' },
  blue: { text: 'text-blue-600', icon: 'text-blue-500' },
  green: { text: 'text-green-600', icon: 'text-green-500' },
  orange: { text: 'text-orange-600', icon: 'text-orange-500' },
  purple: { text: 'text-purple-600', icon: 'text-purple-500' },
}

const iconMap: Record<string, string> = {
  checkin:
    'M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v14z',
  checkout:
    'M15.5 1h-8C6.12 1 5 2.12 5 3.5v15C5 19.88 6.12 21 7.5 21h8c1.38 0 2.5-1.12 2.5-2.5v-15C18 2.12 16.88 1 15.5 1zm-4 21h-4v-1h4v1zm4-3H7.5V4h12v15z',
  guests:
    'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm8 0c1.66 0 2.99-1.34 2.99-3S25.66 5 24 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8-1c-.29 0-.62.02-.97.05 1.16.89 1.97 1.74 1.97 2.95V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z',
  rooms:
    'M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM9 4h6v2H9V4zm9 16H6v-6h12v6zm0-8H6V4h12v8z',
  pending:
    'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z',
  confirmed:
    'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z',
}
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm hover:shadow-md transition">
    <!-- Title (uppercase, small) -->
    <p class="text-xs md:text-sm font-bold text-gray-500 uppercase tracking-widest mb-2 sm:mb-3 md:mb-4">{{ title }}</p>

    <!-- Value and Icon -->
    <div class="flex items-end justify-between gap-2 sm:gap-3 md:gap-4">
      <div class="flex-1">
        <p :class="`${colorMap[color].text} text-4xl sm:text-5xl md:text-6xl font-bold leading-none`">{{ value }}</p>
        <p class="text-xs sm:text-sm md:text-base text-gray-500 mt-1 sm:mt-2 md:mt-3 font-medium">{{ subtext }}</p>
      </div>
      <!-- Icon SVG -->
      <div class="flex-shrink-0">
        <svg class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7" :class="colorMap[color].icon" fill="currentColor" viewBox="0 0 24 24">
          <path :d="iconMap[icon]" />
        </svg>
      </div>
    </div>
  </div>
</template>
