<template>
  <div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="text-center p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg">
        <p class="text-3xl font-bold text-yellow-600">{{ events.pending_assignments || 0 }}</p>
        <p class="text-sm text-gray-600 mt-1">Pending</p>
      </div>
      <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
        <p class="text-3xl font-bold text-blue-600">{{ events.active_assignments || 0 }}</p>
        <p class="text-sm text-gray-600 mt-1">Active</p>
      </div>
      <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
        <p class="text-3xl font-bold text-green-600">{{ events.completed_deliveries || 0 }}</p>
        <p class="text-sm text-gray-600 mt-1">Completed</p>
      </div>
      <div class="text-center p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-lg">
        <p class="text-3xl font-bold text-red-600">{{ events.failed_deliveries || 0 }}</p>
        <p class="text-sm text-gray-600 mt-1">Failed</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  events: any
}>()
</script>
