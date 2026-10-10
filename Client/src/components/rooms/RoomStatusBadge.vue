<script setup lang="ts">
import { computed } from 'vue'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const props = defineProps<{
  status: string
}>()

const statusConfig = computed(() => {
  const s = (props.status || '').toLowerCase()
  switch (s) {
    case 'available':
      return {
        label: languageStore.t('available', 'Available'),
        dot: 'bg-emerald-500',
        styles: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      }
    case 'occupied':
      return {
        label: languageStore.t('occupied', 'Occupied'),
        dot: 'bg-rose-500',
        styles: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
      }
    case 'reserved':
      return {
        label: languageStore.t('reserved', 'Reserved'),
        dot: 'bg-amber-500',
        styles: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
      }
    case 'cleaning':
      return {
        label: languageStore.t('cleaning', 'Cleaning'),
        dot: 'bg-sky-500 animate-pulse',
        styles: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
      }
    case 'maintenance':
      return {
        label: languageStore.t('maintenance', 'Maintenance'),
        dot: 'bg-slate-400',
        styles: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
      }
    default:
      return {
        label: props.status || 'Unknown',
        dot: 'bg-slate-400',
        styles: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
      }
  }
})
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border select-none transition-all whitespace-nowrap',
      statusConfig.styles,
    ]"
  >
    <span :class="['w-1.5 h-1.5 rounded-full flex-shrink-0', statusConfig.dot]"></span>
    <span>{{ statusConfig.label }}</span>
  </span>
</template>
