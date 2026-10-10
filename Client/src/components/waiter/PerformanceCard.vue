<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ title }}</h3>
    <div class="flex items-end gap-4">
      <div>
        <p class="text-4xl font-bold text-blue-600">{{ formattedValue }}</p>
        <p class="text-sm text-gray-600 mt-1">{{ metric }}</p>
      </div>
      <div class="flex-1">
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            :style="{ width: progressPercentage + '%' }"
            class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">{{ progressPercentage }}%</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  title: string
  value: number
  total: number
  metric: string
}>()

const progressPercentage = computed(() => {
  return Math.round((props.value / Math.max(props.total, 1)) * 100)
})

const formattedValue = computed(() => {
  if (props.metric === 'minutes') {
    return props.value.toFixed(1)
  }
  if (props.metric === '%') {
    return props.value.toFixed(0)
  }
  return props.value
})
</script>
