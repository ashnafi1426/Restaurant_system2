<template>
  <div class="delivery-card rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow">
    <!-- Card Header -->
    <div class="border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-3">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-semibold text-gray-900">Order #{{ delivery.order_id }}</h3>
          <p class="text-xs text-gray-600 mt-1">Assigned: {{ formatDate(delivery.assigned_at) }}</p>
        </div>
        <div class="text-right">
          <span
            :class="[
              'inline-block px-2 py-1 text-xs font-medium rounded-full',
              delivery.status === 'completed'
                ? 'bg-green-100 text-green-800'
                : delivery.status === 'in_progress'
                  ? 'bg-blue-100 text-blue-800'
                  : delivery.status === 'failed'
                    ? 'bg-red-100 text-red-800'
                    : 'bg-yellow-100 text-yellow-800',
            ]"
          >
            {{ formatStatus(delivery.status) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Card Content -->
    <div class="p-4 space-y-3">
      <!-- Waiter Info -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs text-gray-600 font-medium">Waiter</p>
          <p class="text-sm font-medium text-gray-900">{{ delivery.waiter_name }}</p>
        </div>
        <div class="text-right">
          <p class="text-xs text-gray-600 font-medium">Delivery Type</p>
          <span
            :class="[
              'inline-block px-2 py-1 text-xs font-medium rounded',
              delivery.assignment_type === 'automatic'
                ? 'bg-blue-100 text-blue-800'
                : 'bg-purple-100 text-purple-800',
            ]"
          >
            {{ delivery.assignment_type || 'manual' }}
          </span>
        </div>
      </div>

      <!-- Details Grid -->
      <div class="grid grid-cols-2 gap-3 bg-gray-50 rounded-lg p-3">
        <div>
          <p class="text-xs text-gray-600 font-medium">Room</p>
          <p class="text-sm font-semibold text-gray-900">{{ delivery.room_number }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-600 font-medium">Floor</p>
          <p class="text-sm font-semibold text-gray-900">{{ delivery.floor_number }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-600 font-medium">Items</p>
          <p class="text-sm font-semibold text-gray-900">{{ delivery.total_items }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-600 font-medium">Est. Time</p>
          <p class="text-sm font-semibold text-gray-900">{{ delivery.estimated_time || '15 min' }}</p>
        </div>
      </div>

      <!-- Customer Note -->
      <div v-if="delivery.customer_note" class="rounded-md bg-amber-50 border border-amber-200 p-2">
        <p class="text-xs font-medium text-amber-900 mb-1">Special Instructions</p>
        <p class="text-sm text-amber-800">{{ delivery.customer_note }}</p>
      </div>

      <!-- Timeline Info -->
      <div class="space-y-2 text-sm">
        <div class="flex justify-between items-center">
          <span class="text-gray-600">Assigned:</span>
          <span class="font-medium text-gray-900">{{ formatTime(delivery.assigned_at) }}</span>
        </div>
        <div v-if="delivery.started_at" class="flex justify-between items-center">
          <span class="text-gray-600">Started:</span>
          <span class="font-medium text-gray-900">{{ formatTime(delivery.started_at) }}</span>
        </div>
        <div v-if="delivery.completed_at" class="flex justify-between items-center">
          <span class="text-gray-600">Completed:</span>
          <span class="font-medium text-green-700">{{ formatTime(delivery.completed_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Card Footer - Actions -->
    <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 flex gap-2">
      <button
        v-if="delivery.status !== 'completed' && delivery.status !== 'failed'"
        @click="$emit('reassign')"
        class="flex-1 rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
      >
        Reassign
      </button>
      <button
        v-if="delivery.status === 'pending' || delivery.status === 'assigned'"
        @click="$emit('cancel')"
        class="flex-1 rounded bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors"
      >
        Cancel
      </button>
      <button
        @click="$emit('view-details')"
        class="flex-1 rounded bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 transition-colors"
      >
        Details
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  delivery: {
    id: number
    order_id: number
    waiter_id: number
    waiter_name: string
    floor_id: number
    floor_number: number
    room_number: string
    status: string
    assignment_type?: string
    total_items: number
    customer_note?: string
    estimated_time?: string
    assigned_at: string
    started_at?: string
    completed_at?: string
  }
}

withDefaults(defineProps<Props>(), {})

defineEmits<{
  'reassign': []
  'cancel': []
  'view-details': []
}>()

function formatDate(date: string) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

function formatTime(time: string) {
  if (!time) return '-'
  return new Date(time).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatStatus(status: string) {
  const statusMap: Record<string, string> = {
    pending: 'Pending',
    assigned: 'Assigned',
    in_progress: 'In Progress',
    completed: 'Completed',
    failed: 'Failed',
  }
  return statusMap[status] || status
}
</script>

<style scoped>
.delivery-card {
  width: 100%;
  transition: all 150ms ease;
}

.delivery-card:hover {
  transform: translateY(-2px);
}
</style>
