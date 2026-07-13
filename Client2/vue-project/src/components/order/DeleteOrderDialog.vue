<template>
  <Dialog
    v-model:visible="dialogVisible"
    modal
    header="Delete Order"
    :style="{ width: '90vw', maxWidth: '450px' }"
    :breakpoints="{ '960px': '90vw', '640px': '95vw' }"
    :pt="{
      header: {
        class:
          'border-b border-gray-200 dark:border-gray-700 px-4 sm:px-5 md:px-6 py-3 sm:py-4 md:py-5',
      },
      content: { class: 'px-4 sm:px-5 md:px-6 py-4 sm:py-5 md:py-6' },
      footer: {
        class:
          'border-t border-gray-200 dark:border-gray-700 px-4 sm:px-5 md:px-6 py-3 sm:py-4 md:py-5',
      },
    }"
  >
    <div class="space-y-3 sm:space-y-4 md:space-y-5">
      <div
        class="flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full"
      >
        <i class="pi pi-trash text-red-600 dark:text-red-400 text-xl sm:text-2xl md:text-3xl"></i>
      </div>

      <div class="text-center">
        <h3 class="text-base sm:text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100">
          Confirm Deletion
        </h3>
        <p
          class="text-xs sm:text-sm md:text-base text-gray-600 dark:text-gray-300 mt-1 sm:mt-2 md:mt-3"
        >
          Are you sure you want to delete this order?
        </p>
      </div>

      <div
        v-if="order"
        class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-2 sm:p-3 md:p-4 space-y-1.5 sm:space-y-2 md:space-y-3"
      >
        <div class="flex justify-between text-xs sm:text-sm md:text-base">
          <span class="text-gray-600 dark:text-gray-400">Order ID:</span>
          <span class="font-medium text-gray-900 dark:text-gray-100 truncate ml-2"
            >#{{ order.id }}</span
          >
        </div>
        <div class="flex justify-between text-xs sm:text-sm md:text-base">
          <span class="text-gray-600 dark:text-gray-400">Customer:</span>
          <span class="font-medium text-gray-900 dark:text-gray-100 truncate ml-2">{{
            order.customer_name || 'N/A'
          }}</span>
        </div>
        <div class="flex justify-between text-xs sm:text-sm md:text-base">
          <span class="text-gray-600 dark:text-gray-400">Total Amount:</span>
          <span class="font-medium text-gray-900 dark:text-gray-100">
            {{ formatCurrency(order.total_amount) }}
          </span>
        </div>
        <div class="flex justify-between text-xs sm:text-sm md:text-base">
          <span class="text-gray-600 dark:text-gray-400">Status:</span>
          <span
            class="px-1.5 sm:px-2 md:px-2.5 py-0.5 text-xs font-semibold rounded-full"
            :class="getStatusBadgeClass(order.status)"
          >
            {{ formatStatus(order.status) }}
          </span>
        </div>
      </div>

      <div
        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-2 sm:p-3 md:p-4"
      >
        <p
          class="text-xs sm:text-sm md:text-base text-red-700 dark:text-red-300 flex items-start gap-2 sm:gap-2.5"
        >
          <i class="pi pi-exclamation-circle mt-0.5 flex-shrink-0"></i>
          <span
            >This action is irreversible. All data associated with this order will be permanently
            removed.</span
          >
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 md:gap-4 justify-end">
        <Button
          label="Cancel"
          severity="secondary"
          text
          @click="closeDialog"
          :disabled="loading"
          class="min-h-10 text-xs sm:text-sm"
        />
        <Button
          label="Delete Order"
          severity="danger"
          @click="handleDelete"
          :loading="loading"
          class="min-h-10 text-xs sm:text-sm"
        >
          <template #icon>
            <i class="pi pi-trash"></i>
          </template>
        </Button>
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
  (e: 'confirm'): void
}>()

const dialogVisible = ref(false)
const loading = ref(false)

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

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount || 0)
}

watch(
  () => props.modelValue,
  (newVal) => {
    dialogVisible.value = newVal
  },
)

watch(dialogVisible, (newVal) => {
  emit('update:modelValue', newVal)
})

function closeDialog() {
  dialogVisible.value = false
}

async function handleDelete() {
  loading.value = true
  try {
    emit('confirm')
  } finally {
    loading.value = false
  }
}
</script>
