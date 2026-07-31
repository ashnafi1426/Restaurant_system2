<template>
  <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-400 hover:shadow transition">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <h4 class="font-semibold text-gray-900">Order #{{ assignment.order?.order_number }}</h4>
        <StatusBadge :status="assignment.status" />
      </div>
      <span v-if="assignment.order?.priority" :class="getPriorityClass(assignment.order.priority)">
        {{ assignment.order.priority.toUpperCase() }}
      </span>
    </div>

    <div class="grid grid-cols-2 gap-3 mb-4 text-sm text-gray-700">
      <div>
        <p class="text-gray-500">Guest</p>
        <p class="font-medium">{{ assignment.order?.guest_name || 'Unknown' }}</p>
      </div>
      <div>
        <p class="text-gray-500">Room</p>
        <p class="font-medium">{{ assignment.order?.room_number }}</p>
      </div>
    </div>

    <div class="flex items-center gap-2 mb-4">
      <Clock :size="16" class="text-gray-400" />
      <span class="text-sm text-gray-600">{{ formatTime(assignment.assigned_at) }}</span>
    </div>

    <div class="flex gap-2">
      <button
        v-if="assignment.status === 'pending'"
        @click="$emit('accept', assignment.id)"
        class="flex-1 px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition"
      >
        Accept
      </button>
      <button
        v-if="assignment.status === 'pending'"
        @click="$emit('reject', assignment.id)"
        class="flex-1 px-3 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition"
      >
        Reject
      </button>
      <button
        @click="$emit('view', assignment)"
        class="flex-1 px-3 py-2 bg-gray-200 text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-300 transition"
      >
        View
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Clock } from 'lucide-vue-next'
import StatusBadge from './StatusBadge.vue'
import type { WaiterAssignment } from '@/types/waiter'

defineProps<{
  assignment: WaiterAssignment
}>()

defineEmits<{
  accept: [id: string]
  reject: [id: string]
  view: [assignment: WaiterAssignment]
}>()

const getPriorityClass = (priority: string) => {
  const baseClass = 'px-3 py-1 rounded-full text-xs font-semibold'
  const priorityMap: any = {
    vip: 'bg-purple-100 text-purple-700',
    urgent: 'bg-red-100 text-red-700',
    normal: 'bg-gray-100 text-gray-700',
  }
  return `${baseClass} ${priorityMap[priority] || priorityMap.normal}`
}

const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}
</script>
