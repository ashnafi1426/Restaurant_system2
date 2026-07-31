<template>
  <div class="waiter-selector">
    <label class="label">
      <span class="text-sm font-medium text-gray-700">{{ label }}</span>
      <div class="relative mt-1">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="placeholder"
          :disabled="disabled || loading"
          @focus="isOpen = true"
          @blur="() => setTimeout(() => (isOpen = false), 200)"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100"
        />

        <transition
          name="dropdown"
          @enter="onEnter"
          @leave="onLeave"
        >
          <div
            v-if="isOpen && filteredWaiters.length > 0"
            class="absolute top-full left-0 right-0 mt-1 max-h-64 overflow-y-auto rounded-md border border-gray-300 bg-white shadow-lg z-10"
          >
            <div
              v-for="waiter in filteredWaiters"
              :key="waiter.id"
              @click="selectWaiter(waiter)"
              class="cursor-pointer px-3 py-2 hover:bg-blue-50 border-b last:border-b-0"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ waiter.name }}</p>
                  <p class="text-xs text-gray-500">{{ waiter.email }}</p>
                </div>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded',
                    waiter.status === 'active'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-800',
                  ]"
                >
                  {{ waiter.availability_status || 'available' }}
                </span>
              </div>
            </div>
          </div>
        </transition>

        <div v-if="isOpen && filteredWaiters.length === 0 && searchQuery" class="absolute top-full left-0 right-0 mt-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-500 shadow-lg z-10">
          No waiters found
        </div>
      </div>
    </label>

    <div v-if="loading" class="mt-1 text-xs text-gray-500">
      Loading waiters...
    </div>
    <div v-if="error" class="mt-1 text-xs text-red-500">
      {{ error }}
    </div>

    <!-- Selected Waiters Display -->
    <div v-if="selectedWaiters.length > 0" class="mt-3 flex flex-wrap gap-2">
      <div
        v-for="waiter in selectedWaiters"
        :key="waiter.id"
        class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800"
      >
        <span>{{ waiter.name }}</span>
        <button
          v-if="multiple"
          @click="removeWaiter(waiter.id)"
          type="button"
          class="text-blue-600 hover:text-blue-900"
        >
          ×
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useManagerStore } from '../../stores/manager/waiterManagementStore'

interface Props {
  modelValue?: (number | string)[] | number | string
  label?: string
  placeholder?: string
  disabled?: boolean
  multiple?: boolean
}

interface Waiter {
  id: number
  name: string
  email: string
  status: string
  availability_status: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  label: 'Select Waiter',
  placeholder: 'Search by name or email...',
  disabled: false,
  multiple: true,
})

const emit = defineEmits<{
  'update:modelValue': [value: (number | string)[] | number | string]
}>()

const store = useManagerStore()
const loading = ref(false)
const error = ref('')
const waiters = ref<Waiter[]>([])
const searchQuery = ref('')
const isOpen = ref(false)

const selectedWaiters = computed(() => {
  const selected = Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue].filter(Boolean)
  return waiters.value.filter(w => selected.includes(w.id))
})

const filteredWaiters = computed(() => {
  if (!searchQuery.value) return waiters.value
  const query = searchQuery.value.toLowerCase()
  return waiters.value.filter(
    w => w.name.toLowerCase().includes(query) || w.email.toLowerCase().includes(query)
  )
})

onMounted(async () => {
  await loadWaiters()
})

async function loadWaiters() {
  loading.value = true
  error.value = ''
  try {
    // Mock data - in real scenario fetch from API
    waiters.value = [
      { id: 1, name: 'Ahmed Hassan', email: 'ahmed@hotel.com', status: 'active', availability_status: 'available' },
      { id: 2, name: 'Fatima Ali', email: 'fatima@hotel.com', status: 'active', availability_status: 'busy' },
      { id: 3, name: 'Muhammad Khan', email: 'khan@hotel.com', status: 'active', availability_status: 'available' },
      { id: 4, name: 'Sara Ibrahim', email: 'sara@hotel.com', status: 'active', availability_status: 'break' },
    ]
  } catch (err) {
    error.value = 'Failed to load waiters'
    console.error('Error loading waiters:', err)
  } finally {
    loading.value = false
  }
}

function selectWaiter(waiter: Waiter) {
  if (props.multiple) {
    const selected = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    if (!selected.includes(waiter.id)) {
      selected.push(waiter.id)
      emit('update:modelValue', selected)
    }
  } else {
    emit('update:modelValue', waiter.id)
    isOpen.value = false
    searchQuery.value = waiter.name
  }
}

function removeWaiter(waiterId: number) {
  if (Array.isArray(props.modelValue)) {
    const selected = props.modelValue.filter(id => id !== waiterId)
    emit('update:modelValue', selected)
  }
}

function onEnter(el: Element) {
  (el as HTMLElement).style.opacity = '0'
  setTimeout(() => {
    (el as HTMLElement).style.transition = 'opacity 150ms'
    (el as HTMLElement).style.opacity = '1'
  }, 0)
}

function onLeave(el: Element) {
  (el as HTMLElement).style.transition = 'opacity 150ms'
  ;(el as HTMLElement).style.opacity = '0'
}
</script>

<style scoped>
.waiter-selector {
  width: 100%;
}

.label {
  display: block;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 150ms ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
