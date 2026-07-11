<script setup lang="ts">
import { reactive, onMounted, ref } from 'vue'
import { useRoomTypeStore } from '../../stores/roomType'
import { roomService } from '../../services/roomService'

const emit = defineEmits(['submit'])

const roomTypeStore = useRoomTypeStore()

const roomTypes = ref([])
const existingRoomNumbers = ref<string[]>([])
const loadingRooms = ref(false)

const form = reactive({
  room_number: '',
  room_type_id: '',
  floor: null as number | null,
  description: '',
  status: 'available',
  is_active: true,
})

onMounted(async () => {
  await roomTypeStore.fetchRoomTypes()
  roomTypes.value = roomTypeStore.roomTypes

  // Fetch existing rooms to prevent duplicates
  try {
    loadingRooms.value = true
    const response = await roomService.getRooms()
    const rooms = response.data?.data || response.data || []
    existingRoomNumbers.value = rooms.map((room: any) => room.room_number)
    console.log('Existing room numbers:', existingRoomNumbers.value)
  } catch (error) {
    console.error('Error fetching existing rooms:', error)
  } finally {
    loadingRooms.value = false
  }
})

const isRoomNumberTaken = (roomNumber: string) => {
  return existingRoomNumbers.value.includes(roomNumber)
}

const suggestNextRoomNumber = () => {
  const numbers = existingRoomNumbers.value.map((num) => parseInt(num)).sort((a, b) => a - b)

  // Find first available number starting from 101
  let nextNumber = 101
  for (let num of numbers) {
    if (num !== nextNumber) {
      break
    }
    nextNumber++
  }

  return nextNumber.toString()
}

const autoFillRoomNumber = () => {
  form.room_number = suggestNextRoomNumber()
}

const save = () => {
  if (isRoomNumberTaken(form.room_number)) {
    alert(
      ` Room number ${form.room_number} is already taken!\n\nAvailable room numbers: ${suggestNextRoomNumber()} and above`,
    )
    return
  }

  emit('submit', {
    ...form,
  })
}
</script>

<template>
  <form @submit.prevent="save" class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Form Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <!-- Room Number -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Room Number <span class="text-red-500">*</span>
        </label>
        <div class="flex gap-2">
          <input
            v-model="form.room_number"
            type="text"
            placeholder="e.g., 101"
            class="flex-1 border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
          />
          <button
            type="button"
            @click="autoFillRoomNumber"
            class="px-3 sm:px-4 py-2 sm:py-2.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs sm:text-sm font-medium text-slate-700 transition-colors duration-200"
            title="Auto-fill with next available room number"
          >
            <span class="hidden sm:inline">Auto</span>
            <span class="sm:hidden">⚡</span>
          </button>
        </div>
        <p
          v-if="form.room_number && isRoomNumberTaken(form.room_number)"
          class="text-red-500 text-xs mt-1 flex items-center gap-1"
        >
          <span>❌</span> This room number is already taken
        </p>
        <p v-else-if="form.room_number" class="text-green-500 text-xs mt-1 flex items-center gap-1">
          <span>✓</span> This room number is available
        </p>
        <p class="text-slate-600 text-xs mt-1">Next available: <strong>#{{ suggestNextRoomNumber() }}</strong></p>
      </div>

      <!-- Floor -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Floor <span class="text-slate-400 text-xs">(Optional)</span>
        </label>
        <input
          v-model.number="form.floor"
          type="number"
          placeholder="e.g., 1"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
          min="1"
          max="99"
        />
      </div>

      <!-- Room Type -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Room Type <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.room_type_id"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white"
          required
        >
          <option value="">Select Room Type</option>
          <option v-for="type in roomTypes" :key="type.id" :value="type.id">
            {{ type.name }} - ₹{{ parseFloat(type.base_price_per_night).toLocaleString('en-IN') }}/night
          </option>
        </select>
        <p v-if="!form.room_type_id" class="text-orange-500 text-xs mt-1 flex items-center gap-1">
          <span>⚠️</span> Please select a room type
        </p>
      </div>

      <!-- Status -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
          Status <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.status"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white"
          required
        >
          <option value="available">✓ Available</option>
          <option value="reserved">🔒 Reserved</option>
          <option value="occupied">👤 Occupied</option>
          <option value="cleaning">🧹 Cleaning</option>
          <option value="maintenance">🔧 Maintenance</option>
        </select>
      </div>
    </div>

    <!-- Description -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900">
        Description <span class="text-slate-400 text-xs">(Optional)</span>
      </label>
      <textarea
        v-model="form.description"
        rows="4"
        placeholder="Add room details, amenities, special features, etc..."
        class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none"
      />
    </div>

    <!-- Active Checkbox -->
    <div class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 border border-slate-200 rounded-lg">
      <input
        type="checkbox"
        v-model="form.is_active"
        id="active-checkbox"
        class="w-4 h-4 sm:w-5 sm:h-5 mt-0.5 sm:mt-0 accent-blue-600 cursor-pointer"
      />
      <div class="flex-1 min-w-0">
        <label for="active-checkbox" class="text-xs sm:text-sm font-semibold text-slate-900 cursor-pointer">
          Active Room
        </label>
        <p class="text-xs text-slate-500 mt-0.5">
          Room will be available for bookings
        </p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-2 sm:pt-4">
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-4 sm:px-6 py-2 sm:py-2.5 border border-slate-300 rounded-lg hover:bg-slate-50 text-xs sm:text-sm font-medium text-slate-700 transition-colors duration-200"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="!form.room_number || !form.room_type_id || isRoomNumberTaken(form.room_number)"
        class="px-4 sm:px-6 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-medium rounded-lg transition-colors duration-200"
      >
        <span v-if="!isRoomNumberTaken(form.room_number)">💾 Save Room</span>
        <span v-else>❌ Invalid Room</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loadingRooms" class="text-center text-slate-600 text-xs sm:text-sm py-4">
      <span class="inline-flex items-center gap-2">
        <span class="animate-spin">⌛</span> Loading existing rooms...
      </span>
    </div>

    <!-- Existing Rooms Info -->
    <div
      v-if="existingRoomNumbers.length > 0"
      class="p-3 sm:p-4 bg-blue-50 rounded-lg border border-blue-200"
    >
      <p class="text-xs sm:text-sm font-semibold text-blue-900 mb-2">📍 Existing Rooms:</p>
      <p class="text-xs sm:text-sm text-blue-800 break-words">
        {{ existingRoomNumbers.join(', ') }}
      </p>
    </div>
  </form>
</template>
