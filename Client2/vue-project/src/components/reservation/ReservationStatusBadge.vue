<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  status: 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled' | string
}

const props = defineProps<Props>()

const badgeConfig = computed(() => {
  switch (props.status) {
    case 'confirmed':
      return {
        label: 'Confirmed',
        dot: 'bg-emerald-500',
        styles: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
      }
    case 'checked_in':
      return {
        label: 'Checked In',
        dot: 'bg-blue-500 animate-pulse',
        styles: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
      }
    case 'checked_out':
      return {
        label: 'Checked Out',
        dot: 'bg-slate-400',
        styles: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
      }
    case 'cancelled':
      return {
        label: 'Cancelled',
        dot: 'bg-rose-500',
        styles: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
      }
    case 'pending':
    default:
      return {
        label: 'Pending',
        dot: 'bg-amber-500',
        styles: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
      }
  }
})
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border select-none transition-all whitespace-nowrap',
      badgeConfig.styles
    ]"
  >
    <span :class="['w-1.5 h-1.5 rounded-full flex-shrink-0', badgeConfig.dot]"></span>
    <span>{{ badgeConfig.label }}</span>
  </span>
</template>
