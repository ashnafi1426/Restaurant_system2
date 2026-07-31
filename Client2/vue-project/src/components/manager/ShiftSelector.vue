<template>
  <div class="shift-selector">
    <label class="label">
      <span class="text-sm font-medium text-gray-700">{{ label }}</span>
      <select
        v-model="selectedShift"
        :disabled="disabled || loading"
        @change="$emit('update:modelValue', selectedShift)"
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
      >
        <option value="">{{ placeholder }}</option>
        <option
          v-for="shift in shifts"
          :key="shift.id"
          :value="shift.id"
        >
          {{ shift.name }} ({{ shift.start_time }} - {{ shift.end_time }})
        </option>
      </select>
    </label>

    <div v-if="selectedShiftDetails" class="mt-2 rounded-md bg-blue-50 p-2 text-sm text-gray-600">
      <p><strong>Shift:</strong> {{ selectedShiftDetails.name }}</p>
      <p><strong>Duration:</strong> {{ selectedShiftDetails.start_time }} - {{ selectedShiftDetails.end_time }}</p>
    </div>

    <div v-if="loading" class="mt-1 text-xs text-gray-500">
      Loading shifts...
    </div>
    <div v-if="error" class="mt-1 text-xs text-red-500">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Props {
  modelValue?: string | number
  label?: string
  placeholder?: string
  disabled?: boolean
}

interface Shift {
  id: number
  name: string
  start_time: string
  end_time: string
  status: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  label: 'Select Shift',
  placeholder: 'Choose a shift...',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const loading = ref(false)
const error = ref('')
const shifts = ref<Shift[]>([])

const selectedShift = computed({
  get: () => props.modelValue,
  set: (value) => {
    // Value updates through emit
  },
})

const selectedShiftDetails = computed(() => {
  return shifts.value.find(s => s.id === Number(props.modelValue))
})

onMounted(async () => {
  await loadShifts()
})

async function loadShifts() {
  loading.value = true
  error.value = ''
  try {
    // Mock data
    shifts.value = [
      { id: 1, name: 'Morning', start_time: '06:00 AM', end_time: '02:00 PM', status: 'active' },
      { id: 2, name: 'Afternoon', start_time: '02:00 PM', end_time: '10:00 PM', status: 'active' },
      { id: 3, name: 'Night', start_time: '10:00 PM', end_time: '06:00 AM', status: 'active' },
    ]
  } catch (err) {
    error.value = 'Failed to load shifts'
    console.error('Error loading shifts:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.shift-selector {
  width: 100%;
}

.label {
  display: block;
}
</style>
