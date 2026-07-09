<script setup lang="ts">

const emit = defineEmits<{
  (e: 'create'): void
  (e: 'refresh'): void
  (e: 'export'): void
}>()
function createOrder(): void {
  emit('create')
}

function refreshOrders(): void {
  emit('refresh')
}

function exportOrders(): void {
  emit('export')
}
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div
      class="flex flex-col gap-6 p-6 lg:flex-row lg:items-center lg:justify-between"
    >
      <!-- Left Section -->
      <div>
        <h1 class="text-3xl font-bold text-gray-900">
          Restaurant Orders
        </h1>

        <p class="mt-2 text-sm text-gray-500">
          Manage restaurant orders, monitor preparation status, and track
          room charges.
        </p>
      </div>

      <!-- Right Section -->
      <div class="flex flex-wrap gap-3">
        <!-- Refresh -->
        <button
          type="button"
          class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
          @click="refreshOrders"
        >
          <span class="mr-2">🔄</span>
          Refresh
        </button>

        <!-- Export -->
        <button
          type="button"
          class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
          @click="exportOrders"
        >
          <span class="mr-2">📄</span>
          Export
        </button>

        <!-- Create -->
        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
          @click="createOrder"
        >
          <span class="mr-2">➕</span>
          New Order
        </button>
      </div>
    </div>
  </div>
</template>