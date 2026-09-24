<template>
  <span :class="statusClass">
    {{ statusLabel }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { AssignmentStatus } from '@/types/waiter'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const props = defineProps<{
  status: AssignmentStatus
}>()

const statusClass = computed(() => {
  const baseClass = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
  const statusMap: any = {
    pending: 'bg-yellow-100 text-yellow-800',
    accepted: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800',
    picked_up: 'bg-purple-100 text-purple-800',
    on_delivery: 'bg-orange-100 text-orange-800',
    delivered: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
  }
  return `${baseClass} ${statusMap[props.status] || statusMap.pending}`
})

const statusLabel = computed(() => {
  const labelMap: any = {
    pending: languageStore.t('pending', 'Pending'),
    accepted: languageStore.t('accepted', 'Accepted'),
    rejected: languageStore.t('rejected', 'Rejected'),
    picked_up: languageStore.t('picked_up', 'Picked Up'),
    on_delivery: languageStore.t('on_delivery', 'On Delivery'),
    delivered: languageStore.t('delivered', 'Delivered'),
    failed: languageStore.t('failed', 'Failed'),
    cancelled: languageStore.t('cancelled', 'Cancelled'),
  }
  return labelMap[props.status] || props.status
})
</script>
