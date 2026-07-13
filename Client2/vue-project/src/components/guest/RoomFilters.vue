<script setup lang="ts">
const props = defineProps({
  type: String,
  capacity: String,
})

const emit = defineEmits(['update:type', 'update:capacity'])

const roomTypes = ['All', 'Standard', 'Deluxe', 'Suite']
const capacities = ['All', '1', '2', '4', '6']

function updateType(value: string) {
  emit('update:type', value)
}

function updateCapacity(value: string) {
  emit('update:capacity', value)
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
    <!-- Filters Header -->
    <h3 class="text-lg sm:text-xl font-semibold text-slate-900 mb-6">Filter Rooms</h3>

    <!-- Filters Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <!-- Room Type Filter -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Room Type</label>
        <select
          :value="type"
          @change="(e) => updateType((e.target as HTMLSelectElement).value)"
          class="w-full px-4 py-2 sm:py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
        >
          <option v-for="t in roomTypes" :key="t" :value="t">{{ t }}</option>
        </select>
      </div>

      <!-- Capacity Filter -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Capacity</label>
        <select
          :value="capacity"
          @change="(e) => updateCapacity((e.target as HTMLSelectElement).value)"
          class="w-full px-4 py-2 sm:py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
        >
          <option v-for="c in capacities" :key="c" :value="c">
            {{ c === 'All' ? 'All Capacities' : `${c} Guest${c !== '1' ? 's' : ''}` }}
          </option>
        </select>
      </div>

      <!-- Price Filter (placeholder) -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Price Range</label>
        <input
          type="range"
          min="50"
          max="300"
          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500"
        />
      </div>

      <!-- Availability Filter (placeholder) -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">Availability</label>
        <select
          class="w-full px-4 py-2 sm:py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
        >
          <option>All</option>
          <option>Available</option>
          <option>Booked</option>
        </select>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Responsive filter styles */
</style>
