<template>
  <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-gray-600 text-sm font-medium">{{ title }}</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ value }}</p>
      </div>
      <div :class="iconBackgroundClass">
        <component :is="iconComponent" :size="24" :class="iconColorClass" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  Clock,
  Truck,
  CheckCircle,
  AlertCircle,
  Package,
  Zap,
  TrendingUp,
  Users,
} from 'lucide-vue-next'

const props = defineProps<{
  title: string
  value: number | string
  icon: string
  color?: 'blue' | 'green' | 'yellow' | 'red' | 'purple' | 'orange'
}>()

const iconComponent = computed(() => {
  const iconMap: any = {
    Clock,
    Truck,
    CheckCircle,
    AlertCircle,
    Package,
    Zap,
    TrendingUp,
    Users,
  }
  return iconMap[props.icon] || Package
})

const iconBackgroundClass = computed(() => {
  const bgMap: any = {
    blue: 'bg-blue-100',
    green: 'bg-green-100',
    yellow: 'bg-yellow-100',
    red: 'bg-red-100',
    purple: 'bg-purple-100',
    orange: 'bg-orange-100',
  }
  return `p-3 rounded-lg ${bgMap[props.color || 'blue']}`
})

const iconColorClass = computed(() => {
  const colorMap: any = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    yellow: 'text-yellow-600',
    red: 'text-red-600',
    purple: 'text-purple-600',
    orange: 'text-orange-600',
  }
  return colorMap[props.color || 'blue']
})
</script>
