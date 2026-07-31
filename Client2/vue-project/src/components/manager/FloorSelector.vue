<template>
  <div class="floor-selector">
    <label class="label">
      <span class="text-sm font-medium text-gray-700">{{ label }}</span>
      <select
        v-model="selectedFloor"
        :disabled="disabled || loading"
        @change="$emit('update:modelValue', selectedFloor)"
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
      >
        <option value="">{{ placeholder }}</option>
        <option
          v-for="floor in floors"
          :key="floor.id"
          :value="floor.id"
        >
          {{ floor.name }} (Floor {{ floor.floor_number }})
        </option>
      </select>
    </label>

    <div v-if="loading" class="mt-1 text-xs text-gray-500">
      Loading floors...
    </div>
    <div v-if="error" class="mt-1 text-xs text-red-500">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useManagerStore } from '../../stores/manager/floorAssignmentStore'

interface Props {
  modelValue?: string | number
  label?: string
  placeholder?: string
  disabled?: boolean
}

interface Floor {
  id: number
  name: string
  floor_number: number
  status: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  label: 'Select Floor',
  placeholder: 'Choose a floor...',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const store = useManagerStore()
const loading = ref(false)
const error = ref('')
const floors = ref<Floor[]>([])

const selectedFloor = computed({
  get: () => props.modelValue,
  set: (value) => {
    // Value updates through emit
  },
})

onMounted(async () => {
  await loadFloors()
})

async function loadFloors() {
  loading.value = true
  error.value = ''
  try {
    // In a real scenario, fetch from API
    // For now, we'll use mock data or fetch from store
    floors.value = [
      { id: 1, name: 'Ground Floor', floor_number: 0, status: 'active' },
      { id: 2, name: 'First Floor', floor_number: 1, status: 'active' },
      { id: 3, name: 'Second Floor', floor_number: 2, status: 'active' },
      { id: 4, name: 'Third Floor', floor_number: 3, status: 'active' },
      { id: 5, name: 'Rooftop', floor_number: 4, status: 'active' },
    ]
  } catch (err) {
    error.value = 'Failed to load floors'
    console.error('Error loading floors:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.floor-selector {
  width: 100%;
}

.label {
  display: block;
}
</style>
