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

// Get icon based on notification type
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

// Get colors based on notification type
const getColors = (type: string) => {
  const colors: Record<string, object> = {
    booking: {
      bg: 'bg-blue-50',
      border: 'border-blue-200',
      text: 'text-blue-900',
      icon: 'text-blue-600',
      dot: 'bg-blue-500',
    },
    check_in: {
      bg: 'bg-green-50',
      border: 'border-green-200',
      text: 'text-green-900',
      icon: 'text-green-600',
      dot: 'bg-green-500',
    },
    check_out: {
      bg: 'bg-yellow-50',
      border: 'border-yellow-200',
      text: 'text-yellow-900',
      icon: 'text-yellow-600',
      dot: 'bg-yellow-500',
    },
    cancellation: {
      bg: 'bg-red-50',
      border: 'border-red-200',
      text: 'text-red-900',
      icon: 'text-red-600',
      dot: 'bg-red-500',
    },
    system: {
      bg: 'bg-gray-50',
      border: 'border-gray-200',
      text: 'text-gray-900',
      icon: 'text-gray-600',
      dot: 'bg-gray-500',
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

// Auto close after duration
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
        <!-- Icon -->
        <Icon :class="['w-5 h-5 flex-shrink-0 mt-0.5', colors.icon]" />

        <!-- Content -->
        <div class="flex-1">
          <h4 class="font-semibold text-sm">
            {{ notification.title }}
          </h4>
          <p class="text-sm mt-1 opacity-90">
            {{ notification.message }}
          </p>

          <!-- Guest Info -->
          <div
            v-if="notification.guest_name || notification.room_number"
            class="mt-2 flex flex-wrap gap-2"
          >
            <span
              v-if="notification.guest_name"
              class="inline-flex items-center px-2 py-1 bg-white/50 rounded text-xs font-medium"
            >
              👤 {{ notification.guest_name }}
            </span>
            <span
              v-if="notification.room_number"
              class="inline-flex items-center px-2 py-1 bg-white/50 rounded text-xs font-medium"
            >
              🚪 Room {{ notification.room_number }}
            </span>
          </div>
        </div>

        <!-- Close Button -->
        <button @click="close" class="flex-shrink-0 opacity-70 hover:opacity-100 transition">
          <X class="w-5 h-5" />
        </button>
      </div>
    </div>
  </Transition>
</template>
