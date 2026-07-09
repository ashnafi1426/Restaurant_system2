<script setup lang="ts">
defineProps<{
  status: string
}>()

const statusClass = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-green-100 text-green-700'

    case 'occupied':
      return 'bg-red-100 text-red-700'

    case 'reserved':
      return 'bg-yellow-100 text-yellow-700'

    case 'maintenance':
      return 'bg-gray-200 text-gray-700'

    default:
      return 'bg-slate-100 text-slate-700'
  }
}
</script>

<template>
  <span
    :class="statusClass(status)"
    class="px-3 py-1 rounded-full text-xs font-semibold capitalize"
  >
    {{ status }}
  </span>
</template>
