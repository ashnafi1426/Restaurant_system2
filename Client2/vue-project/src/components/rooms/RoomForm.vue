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
  <form @submit.prevent="save" class="space-y-6">
    <div class="grid grid-cols-2 gap-6">
      <div>
        <label class="block mb-2 font-semibold"> Room Number * </label>
        <div class="flex gap-2">
          <input
            v-model="form.room_number"
            type="text"
            placeholder="e.g., 101"
            class="flex-1 border rounded-lg px-4 py-2"
          />
          <button
            type="button"
            @click="autoFillRoomNumber"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-medium transition-colors"
            title="Auto-fill with next available room number"
          >
            Auto
          </button>
        </div>
        <p
          v-if="form.room_number && isRoomNumberTaken(form.room_number)"
          class="text-red-600 text-xs mt-1"
        >
          This room number is already taken
        </p>
        <p v-else-if="form.room_number" class="text-green-600 text-xs mt-1">
          This room number is available
        </p>
        <p class="text-gray-600 text-xs mt-1">Next available: {{ suggestNextRoomNumber() }}</p>
      </div>

      <div>
        <label class="block mb-2 font-semibold"> Floor </label>
        <input
          v-model.number="form.floor"
          type="number"
          placeholder="e.g., 1"
          class="w-full border rounded-lg px-4 py-2"
        />
      </div>

      <div>
        <label class="block mb-2 font-semibold"> Room Type * </label>
        <select v-model="form.room_type_id" class="w-full border rounded-lg px-4 py-2" required>
          <option value="">Select Room Type</option>
          <option v-for="type in roomTypes" :key="type.id" :value="type.id">
            {{ type.name }} ({{ type.base_price_per_night }}/night)
          </option>
        </select>
        <p v-if="!form.room_type_id" class="text-orange-600 text-xs mt-1">
          Please select a room type
        </p>
      </div>

      <div>
        <label class="block mb-2 font-semibold"> Status * </label>
        <select v-model="form.status" class="w-full border rounded-lg px-4 py-2" required>
          <option value="available">Available</option>
          <option value="reserved">Reserved</option>
          <option value="occupied">Occupied</option>
          <option value="cleaning">Cleaning</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block mb-2 font-semibold"> Description </label>
      <textarea
        v-model="form.description"
        rows="4"
        placeholder="Add room details, amenities, etc."
        class="w-full border rounded-lg px-4 py-2"
      />
    </div>

    <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
      <input type="checkbox" v-model="form.is_active" id="active-checkbox" class="w-4 h-4" />
      <label for="active-checkbox" class="font-semibold"> Active Room </label>
      <span class="text-xs text-gray-600">(Room will be available for bookings)</span>
    </div>

    <div class="flex gap-3">
      <button
        type="submit"
        :disabled="!form.room_number || !form.room_type_id || isRoomNumberTaken(form.room_number)"
        class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg font-medium transition-colors"
      >
        Save Room
      </button>
      <button
        type="button"
        @click="$emit('cancel')"
        class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
      >
        Cancel
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loadingRooms" class="text-center text-gray-600 text-sm">
      Loading existing rooms...
    </div>

    <!-- Existing Rooms Info -->
    <div
      v-if="existingRoomNumbers.length > 0"
      class="p-4 bg-blue-50 rounded-lg border border-blue-200"
    >
      <p class="text-sm font-semibold text-blue-900 mb-2">📍 Existing Rooms:</p>
      <p class="text-sm text-blue-800">
        {{ existingRoomNumbers.join(', ') }}
      </p>
    </div>
  </form>
</template>
