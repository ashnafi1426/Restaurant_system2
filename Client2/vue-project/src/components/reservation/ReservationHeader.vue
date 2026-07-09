<script setup lang="ts">
interface Props {
  loading?: boolean
}
defineProps<Props>()
const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'create'): void
}>()
</script>
<template>
  <div class="space-y-5">
    <!-- Breadcrumb -->

    <div class="flex items-center text-sm text-gray-500">
      <span>Dashboard</span>

      <span class="mx-2">/</span>

      <span>Reservations</span>

      <span class="mx-2">/</span>

      <span class="font-semibold text-gray-800"> Reservation Management </span>
    </div>

    <!-- Header -->

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
        <!-- Left -->

        <div>
          <h1 class="text-3xl font-bold text-gray-900">Reservation Management</h1>

          <p class="text-gray-500 mt-2">
            Create, manage and monitor hotel reservations, check-ins and guest stays.
          </p>
        </div>

        <!-- Right -->
        <div class="flex flex-wrap gap-3">
          <button
            @click="emit('refresh')"
            :disabled="loading"
            class="flex items-center gap-2 px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 transition disabled:opacity-60"
          >
            <span class="material-icons"> refresh </span>
            Refresh
          </button>

          <button
            @click="emit('create')"
            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow transition"
          >
            <span class="material-icons"> add </span>

            New Reservation
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
