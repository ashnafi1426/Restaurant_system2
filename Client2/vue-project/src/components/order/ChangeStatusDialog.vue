<template>
  <Dialog
    v-model:visible="dialogVisible"
    modal
    :header="`Change Status - Order #${order?.id || ''}`"
    :style="{ width: '90vw', maxWidth: '450px' }"
    :breakpoints="{ '960px': '90vw', '640px': '95vw' }"
    :pt="{
      header: { class: 'border-b border-gray-200 dark:border-gray-700 px-4 sm:px-5 md:px-6 py-3 sm:py-4 md:py-5' },
      content: { class: 'px-4 sm:px-5 md:px-6 py-4 sm:py-5 md:py-6' },
      footer: { class: 'border-t border-gray-200 dark:border-gray-700 px-4 sm:px-5 md:px-6 py-3 sm:py-4 md:py-5' },
    }"
  >
    <div class="space-y-3 sm:space-y-4 md:space-y-5">
      <div>
        <label class="block text-xs sm:text-sm md:text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2 md:mb-3">
          Current Status
        </label>
        <div class="flex items-center gap-1.5 sm:gap-2 md:gap-3">
          <span
            class="px-2 sm:px-2.5 md:px-3 py-0.5 sm:py-1 md:py-1.5 text-xs sm:text-sm md:text-base font-semibold rounded-full"
            :class="getStatusBadgeClass(order?.status || '')"
          >
            {{ formatStatus(order?.status || '') }}
          </span>
        </div>
      </div>

      <div>
        <label class="block text-xs sm:text-sm md:text-base font-medium text-gray-700 dark:text-gray-300 mb-1.5 sm:mb-2 md:mb-3">
          New Status
        </label>
        <select
          v-model="selectedStatus"
          class="w-full px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 md:py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-xs sm:text-sm md:text-base text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors min-h-10"
        >
          <option v-for="status in statusOptions" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>

      <div
        v-if="selectedStatus === 'cancelled'"
        class="bg-red-50 dark:bg-red-900/20 p-2 sm:p-3 md:p-4 rounded-lg"
      >
        <p class="text-xs sm:text-sm md:text-base text-red-700 dark:text-red-300">
          <i class="pi pi-exclamation-triangle mr-1.5 sm:mr-2"></i>
          Are you sure you want to cancel this order? This action cannot be undone.
        </p>
      </div>

      <div
        v-if="selectedStatus === 'completed'"
        class="bg-green-50 dark:bg-green-900/20 p-2 sm:p-3 md:p-4 rounded-lg"
      >
        <p class="text-xs sm:text-sm md:text-base text-green-700 dark:text-green-300">
          <i class="pi pi-check-circle mr-1.5 sm:mr-2"></i>
          Marking this order as completed will finalize the transaction.
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 md:gap-4 justify-end">
        <Button label="Cancel" severity="secondary" text @click="closeDialog" :disabled="loading" class="min-h-10 text-xs sm:text-sm" />
        <Button
          label="Update Status"
          severity="primary"
          @click="handleUpdate"
          :loading="loading"
          :disabled="!selectedStatus || selectedStatus === order?.status || loading"
          class="min-h-10 text-xs sm:text-sm"
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { Order } from '@/types/order'

const props = defineProps<{
  modelValue: boolean
  order: Order | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'update', status: string): void
}>()

const dialogVisible = ref(false)
const loading = ref(false)
const selectedStatus = ref('')

const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'processing', label: 'Processing' },
  { value: 'shipped', label: 'Shipped' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
]

function getStatusBadgeClass(status: string): string {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    processing: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    shipped: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    delivered: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  }
  return classes[status as keyof typeof classes] || classes.pending
}

function formatStatus(status: string): string {
  const map: Record<string, string> = {
    pending: 'Pending',
    processing: 'Processing',
    shipped: 'Shipped',
    delivered: 'Delivered',
    completed: 'Completed',
    cancelled: 'Cancelled',
  }
  return map[status] || status
}

watch(
  () => props.modelValue,
  (newVal) => {
    dialogVisible.value = newVal
    if (newVal && props.order) {
      selectedStatus.value = props.order.status
    }
  },
)

watch(dialogVisible, (newVal) => {
  emit('update:modelValue', newVal)
  if (!newVal) {
    selectedStatus.value = ''
  }
})

function closeDialog() {
  dialogVisible.value = false
}

async function handleUpdate() {
  if (!selectedStatus.value || !props.order) return

  loading.value = true
  try {
    emit('update', selectedStatus.value)
  } finally {
    loading.value = false
  }
}
</script>
