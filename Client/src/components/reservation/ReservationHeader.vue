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
    <div class="flex items-center text-sm text-gray-500">
      <span>Dashboard</span>
      <span class="mx-2">/</span>
      <span>Reservations</span>
      <span class="mx-2">/</span>
      <span class="font-semibold text-gray-800"> Reservation Management </span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Reservation Management</h1>
          <p class="text-gray-500 mt-2">
            Create, manage and monitor hotel reservations, check-ins and guest stays.
          </p>
        </div>

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
            type="button"
            @click="emit('create')"
            class="inline-flex items-center justify-center rounded-[10px] bg-[#1257A2] hover:bg-[#0e4884] active:bg-[#0b3c6f] text-white px-5 sm:px-6 py-2 sm:py-2.5 text-sm sm:text-[15px] font-semibold transition-colors duration-150 cursor-pointer flex-shrink-0 shadow-none border-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1257A2]/40"
          >
            <span class="text-white font-semibold">New Reservation</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
