<template>
  <div class="bg-white rounded-lg shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Room Type</label>
        <select
          :value="type"
          @change="onTypeChange"
          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="All">All Types</option>
          <option value="Standard">Standard</option>
          <option value="Deluxe">Deluxe</option>
          <option value="Suite">Suite</option>
          <option value="Executive Suite">Executive Suite</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Capacity</label>
        <select
          :value="capacity"
          @change="onCapacityChange"
          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="All">Any Capacity</option>
          <option value="1">1+ Guests</option>
          <option value="2">2+ Guests</option>
          <option value="3">3+ Guests</option>
          <option value="4">4+ Guests</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  type: string
  capacity: string
}>()

const emit = defineEmits<{
  'update:type': [value: string]
  'update:capacity': [value: string]
}>()

const onTypeChange = (e: Event) => {
  const target = e.target as HTMLSelectElement | null
  if (target) emit('update:type', target.value)
}

const onCapacityChange = (e: Event) => {
  const target = e.target as HTMLSelectElement | null
  if (target) emit('update:capacity', target.value)
}
</script>
