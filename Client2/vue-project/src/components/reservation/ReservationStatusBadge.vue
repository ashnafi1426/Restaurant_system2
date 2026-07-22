<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  status: 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled'
}

const props = defineProps<Props>()

const badgeClass = computed(() => {
  const classes: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-700 border-amber-200',
    confirmed: 'bg-green-100 text-green-700 border-green-200',
    checked_in: 'bg-blue-100 text-blue-700 border-blue-200',
    checked_out: 'bg-slate-100 text-slate-700 border-slate-200',
    cancelled: 'bg-red-100 text-red-700 border-red-200',
  }
  return classes[props.status] || classes.pending
})

const statusIcon = computed(() => {
  const icons: Record<string, string> = {
    pending: 'schedule',
    confirmed: 'check_circle',
    checked_in: 'login',
    checked_out: 'logout',
    cancelled: 'cancel',
  }
  return icons[props.status] || 'help'
})

const statusLabel = computed(() => {
  const labels: Record<string, string> = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    checked_in: 'Checked In',
    checked_out: 'Checked Out',
    cancelled: 'Cancelled',
  }
  return labels[props.status] || 'Unknown'
})
</script>

<template>
  <span
    :class="badgeClass"
    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold border"
  >
    <span class="material-symbols-rounded text-sm">{{ statusIcon }}</span>
    {{ statusLabel }}
  </span>
</template>
