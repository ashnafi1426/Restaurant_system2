<script setup lang="ts">
import { computed, type Component } from 'vue'
import {
  LogIn,
  LogOut,
  Users,
  BedDouble,
  Clock,
  CheckCircle2
} from 'lucide-vue-next'

interface Props {
  title: string
  value: number | string
  icon: string
  color?: 'teal' | 'red' | 'blue' | 'green' | 'orange' | 'purple'
  subtext?: string
}

const props = withDefaults(defineProps<Props>(), {
  color: 'teal',
  subtext: 'Today',
})

const colorStyles = computed(() => {
  const map: Record<string, { bg: string; text: string; iconBg: string; border: string }> = {
    teal: {
      bg: 'bg-emerald-500/10 dark:bg-emerald-500/15',
      text: 'text-emerald-600 dark:text-emerald-400',
      iconBg: 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300',
      border: 'border-emerald-200/60 dark:border-emerald-900/40',
    },
    red: {
      bg: 'bg-rose-500/10 dark:bg-rose-500/15',
      text: 'text-rose-600 dark:text-rose-400',
      iconBg: 'bg-rose-500/20 text-rose-600 dark:text-rose-300',
      border: 'border-rose-200/60 dark:border-rose-900/40',
    },
    blue: {
      bg: 'bg-sky-500/10 dark:bg-sky-500/15',
      text: 'text-sky-600 dark:text-sky-400',
      iconBg: 'bg-sky-500/20 text-sky-600 dark:text-sky-300',
      border: 'border-sky-200/60 dark:border-sky-900/40',
    },
    green: {
      bg: 'bg-teal-500/10 dark:bg-teal-500/15',
      text: 'text-teal-600 dark:text-teal-400',
      iconBg: 'bg-teal-500/20 text-teal-600 dark:text-teal-300',
      border: 'border-teal-200/60 dark:border-teal-900/40',
    },
    orange: {
      bg: 'bg-amber-500/10 dark:bg-amber-500/15',
      text: 'text-amber-600 dark:text-amber-400',
      iconBg: 'bg-amber-500/20 text-amber-600 dark:text-amber-300',
      border: 'border-amber-200/60 dark:border-amber-900/40',
    },
    purple: {
      bg: 'bg-indigo-500/10 dark:bg-indigo-500/15',
      text: 'text-indigo-600 dark:text-indigo-400',
      iconBg: 'bg-indigo-500/20 text-indigo-600 dark:text-indigo-300',
      border: 'border-indigo-200/60 dark:border-indigo-900/40',
    },
  }
  return map[props.color] || map.teal
})

const iconComponent = computed(() => {
  const map: Record<string, Component> = {
    checkin: LogIn,
    checkout: LogOut,
    guests: Users,
    rooms: BedDouble,
    pending: Clock,
    confirmed: CheckCircle2,
  }
  return map[props.icon] || LogIn
})
</script>

<template>
  <div
    class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-2xl border p-4 shadow-sm hover:shadow-md transition-all duration-200 group"
    :class="colorStyles.border"
  >
    <div class="flex items-center justify-between">
      <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
        {{ title }}
      </span>
      <div
        class="w-9 h-9 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110"
        :class="colorStyles.iconBg"
      >
        <component :is="iconComponent" class="w-5 h-5" />
      </div>
    </div>

    <div class="mt-3 flex items-baseline justify-between">
      <span class="text-2xl sm:text-3xl font-extrabold tracking-tight" :class="colorStyles.text">
        {{ value }}
      </span>
      <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">
        {{ subtext }}
      </span>
    </div>
  </div>
</template>
