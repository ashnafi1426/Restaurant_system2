<template>
  <div class="assignment-grid">
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Floor-Waiter Assignment Matrix</h3>
      <p class="text-sm text-gray-600">Visual representation of current assignments</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="floor in floors"
        :key="floor.id"
        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow"
      >
        <!-- Floor Header -->
        <div class="mb-4 pb-3 border-b">
          <h4 class="font-semibold text-gray-900">{{ floor.name }}</h4>
          <p class="text-xs text-gray-500">Floor {{ floor.floor_number }}</p>
        </div>

        <!-- Assignments -->
        <div class="space-y-2">
          <!-- Primary Assignment -->
          <div class="rounded bg-blue-50 p-2">
            <p class="text-xs font-medium text-gray-600 mb-1">Primary</p>
            <div v-if="getAssignment(floor.id, 'primary')" class="flex items-center justify-between">
              <p class="text-sm text-gray-900 font-medium">
                {{ getAssignment(floor.id, 'primary')?.waiter_name }}
              </p>
              <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
            </div>
            <p v-else class="text-sm text-gray-500 italic">Unassigned</p>
          </div>

          <!-- Secondary Assignment -->
          <div class="rounded bg-yellow-50 p-2">
            <p class="text-xs font-medium text-gray-600 mb-1">Secondary</p>
            <div v-if="getAssignment(floor.id, 'secondary')" class="flex items-center justify-between">
              <p class="text-sm text-gray-900 font-medium">
                {{ getAssignment(floor.id, 'secondary')?.waiter_name }}
              </p>
              <span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span>
            </div>
            <p v-else class="text-sm text-gray-500 italic">Unassigned</p>
          </div>

          <!-- Backup Assignment -->
          <div class="rounded bg-red-50 p-2">
            <p class="text-xs font-medium text-gray-600 mb-1">Backup</p>
            <div v-if="getAssignment(floor.id, 'backup')" class="flex items-center justify-between">
              <p class="text-sm text-gray-900 font-medium">
                {{ getAssignment(floor.id, 'backup')?.waiter_name }}
              </p>
              <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span>
            </div>
            <p v-else class="text-sm text-gray-500 italic">Unassigned</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-4 flex gap-2">
          <button
            @click="$emit('edit', floor.id)"
            class="flex-1 rounded bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700 transition-colors"
          >
            Edit
          </button>
          <button
            @click="$emit('view-stats', floor.id)"
            class="flex-1 rounded bg-gray-200 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-300 transition-colors"
          >
            Stats
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div v-if="assignments.length > 0" class="mt-6 grid gap-4 md:grid-cols-4">
      <div class="rounded-lg bg-gradient-to-br from-green-50 to-green-100 p-4 border border-green-200">
        <p class="text-sm text-gray-600">Total Assignments</p>
        <p class="text-2xl font-bold text-green-700">{{ assignments.length }}</p>
      </div>
      <div class="rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 p-4 border border-blue-200">
        <p class="text-sm text-gray-600">Primary Only</p>
        <p class="text-2xl font-bold text-blue-700">{{ assignments.filter(a => a.type === 'primary').length }}</p>
      </div>
      <div class="rounded-lg bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 border border-yellow-200">
        <p class="text-sm text-gray-600">Full Coverage</p>
        <p class="text-2xl font-bold text-yellow-700">{{ fullyCoveredFloors }}</p>
      </div>
      <div class="rounded-lg bg-gradient-to-br from-red-50 to-red-100 p-4 border border-red-200">
        <p class="text-sm text-gray-600">Unassigned Floors</p>
        <p class="text-2xl font-bold text-red-700">{{ unassignedFloors }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  floors?: any[]
  assignments?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  floors: () => [
    { id: 1, name: 'Ground Floor', floor_number: 0 },
    { id: 2, name: 'First Floor', floor_number: 1 },
    { id: 3, name: 'Second Floor', floor_number: 2 },
  ],
  assignments: () => [],
})

const emit = defineEmits<{
  'edit': [floorId: number]
  'view-stats': [floorId: number]
}>()

const fullyCoveredFloors = computed(() => {
  return props.floors.filter(floor => {
    const floorAssignments = props.assignments.filter(a => a.floor_id === floor.id)
    return floorAssignments.length === 3
  }).length
})

const unassignedFloors = computed(() => {
  return props.floors.filter(floor => {
    const floorAssignments = props.assignments.filter(a => a.floor_id === floor.id)
    return floorAssignments.length === 0
  }).length
})

function getAssignment(floorId: number, type: string) {
  return props.assignments.find(a => a.floor_id === floorId && a.type === type)
}
</script>

<style scoped>
.assignment-grid {
  width: 100%;
}
</style>
