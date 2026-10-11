<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { NotificationData } from '@/services/notificationService'
import { X, Bell, Check, AlertCircle } from 'lucide-vue-next'

interface Props {
  notification: NotificationData
  autoClose?: boolean
  duration?: number
}

const props = withDefaults(defineProps<Props>(), {
  autoClose: true,
  duration: 5000,
})

const emit = defineEmits<{
  close: []
}>()

const isVisible = ref(true)

const getIcon = (type: string) => {
  const icons: Record<string, any> = {
    booking: Bell,
    check_in: Check,
    check_out: AlertCircle,
    cancellation: AlertCircle,
    system: Bell,
  }
  return icons[type] || Bell
}

interface ToastColor {
  bg: string
  border: string
  text: string
  icon: string
  dot: string
}

const getColors = (type: string): ToastColor => {
  const colors: Record<string, ToastColor> = {
    booking: {
      bg: 'bg-blue-50 dark:bg-slate-900',
      border: 'border-blue-200 dark:border-blue-800',
      text: 'text-blue-900 dark:text-blue-100',
      icon: 'text-blue-600 dark:text-blue-400',
      dot: 'bg-blue-500',
    },
    check_in: {
      bg: 'bg-emerald-50 dark:bg-slate-900',
      border: 'border-emerald-200 dark:border-emerald-800',
      text: 'text-emerald-900 dark:text-emerald-100',
      icon: 'text-emerald-600 dark:text-emerald-400',
      dot: 'bg-emerald-500',
    },
    check_out: {
      bg: 'bg-amber-50 dark:bg-slate-900',
      border: 'border-amber-200 dark:border-amber-800',
      text: 'text-amber-900 dark:text-amber-100',
      icon: 'text-amber-600 dark:text-amber-400',
      dot: 'bg-amber-500',
    },
    cancellation: {
      bg: 'bg-rose-50 dark:bg-slate-900',
      border: 'border-rose-200 dark:border-rose-800',
      text: 'text-rose-900 dark:text-rose-100',
      icon: 'text-rose-600 dark:text-rose-400',
      dot: 'bg-rose-500',
    },
    system: {
      bg: 'bg-slate-50 dark:bg-slate-900',
      border: 'border-slate-200 dark:border-slate-800',
      text: 'text-slate-900 dark:text-slate-100',
      icon: 'text-slate-600 dark:text-slate-400',
      dot: 'bg-slate-500',
    },
  }
  return colors[type] || colors.system
}

const colors = getColors(props.notification.type)
const Icon = getIcon(props.notification.type)

const close = () => {
  isVisible.value = false
  emit('close')
}

onMounted(() => {
  if (props.autoClose) {
    setTimeout(close, props.duration)
  }
})
</script>

<template>
  <Transition
    enter-active-class="transition-all duration-300"
    enter-from-class="transform translate-x-96 opacity-0"
    enter-to-class="transform translate-x-0 opacity-100"
    leave-active-class="transition-all duration-300"
    leave-from-class="transform translate-x-0 opacity-100"
    leave-to-class="transform translate-x-96 opacity-0"
  >
    <div
      v-if="isVisible"
      :class="[
        'fixed bottom-4 right-4 max-w-md p-4 rounded-lg border shadow-lg z-50',
        colors.bg,
        colors.border,
        colors.text,
      ]"
    >
      <div class="flex items-start gap-3">
        <Icon :class="['w-5 h-5 flex-shrink-0 mt-0.5', colors.icon]" />

        <div class="flex-1">
          <h4 class="font-semibold text-sm">
            {{ notification.title }}
          </h4>
          <p class="text-sm mt-1 opacity-90">
            {{ notification.message }}
          </p>

          <div
            v-if="notification.guest_name || notification.room_number"
            class="mt-2 flex flex-wrap gap-2"
          >
            <span
              v-if="notification.guest_name"
              class="inline-flex items-center px-2 py-1 bg-white/70 dark:bg-slate-800/80 rounded text-xs font-medium border border-black/5 dark:border-white/10"
            >
              👤 {{ notification.guest_name }}
            </span>
            <span
              v-if="notification.room_number"
              class="inline-flex items-center px-2 py-1 bg-white/70 dark:bg-slate-800/80 rounded text-xs font-medium border border-black/5 dark:border-white/10"
            >
              🚪 Room {{ notification.room_number }}
            </span>
          </div>
        </div>

        <button @click="close" class="flex-shrink-0 opacity-70 hover:opacity-100 transition">
          <X class="w-5 h-5" />
        </button>
      </div>
    </div>
  </Transition>
</template>
