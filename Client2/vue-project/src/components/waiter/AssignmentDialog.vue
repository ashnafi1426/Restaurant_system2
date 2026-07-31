<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Header -->
      <div class="border-b border-gray-200 p-6 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Order Details</h2>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 text-2xl"
        >
          ×
        </button>
      </div>

      <!-- Content -->
      <div class="p-6 space-y-4">
        <div v-if="assignment">
          <!-- Order Info -->
          <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <p class="text-sm text-gray-600">Order Number</p>
            <p class="text-lg font-semibold text-gray-900">#{{ assignment.order?.order_number }}</p>
          </div>

          <!-- Guest & Room -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-600">Guest</p>
              <p class="font-semibold text-gray-900">{{ assignment.order?.guest_name }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-600">Room</p>
              <p class="font-semibold text-gray-900">#{{ assignment.order?.room_number }}</p>
            </div>
          </div>

          <!-- Priority & Status -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-600">Priority</p>
              <p class="font-semibold text-gray-900">{{ assignment.order?.priority }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-600">Status</p>
              <StatusBadge :status="assignment.status" />
            </div>
          </div>

          <!-- Timeline -->
          <div class="space-y-2 text-sm mb-4">
            <div class="flex items-center gap-2">
              <CheckCircle :size="16" class="text-gray-400" />
              <span class="text-gray-600">Assigned: {{ formatDateTime(assignment.assigned_at) }}</span>
            </div>
            <div v-if="assignment.accepted_at" class="flex items-center gap-2">
              <CheckCircle :size="16" class="text-green-500" />
              <span class="text-gray-600">Accepted: {{ formatDateTime(assignment.accepted_at) }}</span>
            </div>
            <div v-if="assignment.picked_up_at" class="flex items-center gap-2">
              <CheckCircle :size="16" class="text-green-500" />
              <span class="text-gray-600">Picked Up: {{ formatDateTime(assignment.picked_up_at) }}</span>
            </div>
            <div v-if="assignment.delivered_at" class="flex items-center gap-2">
              <CheckCircle :size="16" class="text-green-500" />
              <span class="text-gray-600">Delivered: {{ formatDateTime(assignment.delivered_at) }}</span>
            </div>
          </div>

          <!-- Remarks -->
          <div v-if="assignment.remarks" class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm">
            <p class="text-gray-600 font-semibold mb-1">Remarks</p>
            <p class="text-gray-900">{{ assignment.remarks }}</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="assignment" class="border-t border-gray-200 p-6 flex gap-3">
        <button
          v-if="assignment.status === 'pending'"
          @click="$emit('accept', assignment.id)"
          class="flex-1 px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition"
        >
          Accept
        </button>
        <button
          v-if="assignment.status === 'pending'"
          @click="$emit('reject', assignment.id)"
          class="flex-1 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
        >
          Reject
        </button>
        <button
          @click="$emit('close')"
          class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 font-medium rounded-lg hover:bg-gray-300 transition"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckCircle } from 'lucide-vue-next'
import StatusBadge from './StatusBadge.vue'
import type { WaiterAssignment } from '@/types/waiter'

defineProps<{
  assignment: WaiterAssignment | null
}>()

defineEmits<{
  close: []
  accept: [id: string]
  reject: [id: string]
}>()

const formatDateTime = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
