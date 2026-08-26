<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { roomService } from '../../../services/roomService'
import { useRoomTypeStore } from '../../../stores/roomType'
import { ArrowLeft, Save, Loader2, AlertTriangle, CheckCircle2 } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const roomId = String(route.params.id)

const roomTypeStore = useRoomTypeStore()
const roomTypes = ref<any[]>([])

const loading = reactive({
  page: true,
  saving: false,
})

const error = ref<string | null>(null)
const validationErrors = ref<Record<string, string[]>>({})
const successMessage = ref<string | null>(null)

const form = reactive({
  room_number: '',
  room_type_id: '',
  floor: null as number | null,
  description: '',
  status: 'available',
  is_active: true,
})

const loadData = async () => {
  loading.page = true
  error.value = null

  try {
    // 1. Fetch Room Types
    await roomTypeStore.fetchRoomTypes()
    roomTypes.value = roomTypeStore.roomTypes || []

    // 2. Fetch Room Details
    const response = await roomService.getRoom(roomId)
    const roomData = response.data?.data || response.data

    if (roomData) {
      form.room_number = roomData.room_number || ''
      form.room_type_id = roomData.room_type_id || (roomData.room_type?.id ? String(roomData.room_type.id) : '')
      form.floor = roomData.floor !== undefined && roomData.floor !== null ? Number(roomData.floor) : null
      form.description = roomData.description || ''
      form.status = roomData.status || 'available'
      form.is_active = roomData.is_active ?? true
    }
  } catch (err: any) {
    console.error('Error loading room details:', err)
    error.value = 'Unable to load room details. The room may not exist.'
  } finally {
    loading.page = false
  }
}

const updateRoom = async () => {
  loading.saving = true
  error.value = null
  validationErrors.value = {}
  successMessage.value = null

  try {
    const payload = {
      room_number: form.room_number,
      room_type_id: form.room_type_id,
      floor: form.floor !== null && form.floor !== undefined && form.floor !== ('' as any) ? Number(form.floor) : null,
      description: form.description,
      status: form.status,
      is_active: form.is_active ? 1 : 0,
    }

    await roomService.updateRoom(roomId, payload as any)
    
    successMessage.value = 'Room updated successfully!'
    setTimeout(() => {
      router.push('/rooms')
    }, 1200)

  } catch (err: any) {
    console.error('Error updating room:', err)
    const errData = err.response?.data

    if (errData?.errors) {
      validationErrors.value = errData.errors
      error.value = errData.message || 'Validation failed. Please check the form fields below.'
    } else {
      error.value = errData?.message || err.message || 'Failed to update room.'
    }
  } finally {
    loading.saving = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <DashboardLayout>
    <div class="max-w-4xl mx-auto px-4 py-8">
      
      <!-- Top Navigation Header -->
      <div class="mb-6 flex items-center justify-between">
        <button
          type="button"
          @click="router.push('/rooms')"
          class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 transition"
        >
          <ArrowLeft class="w-4 h-4" />
          Back to Rooms
        </button>
      </div>

      <!-- Page Title -->
      <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Edit Room</h1>
        <p class="text-slate-500 mt-1 text-sm">Update room status, assigned type, floor, or description.</p>
      </div>

      <!-- Loading Page State -->
      <div v-if="loading.page" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
        <Loader2 class="w-8 h-8 text-blue-600 animate-spin mx-auto mb-3" />
        <p class="text-slate-600 font-medium text-sm">Loading room details...</p>
      </div>

      <!-- Main Form Container -->
      <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        
        <!-- Header Banner -->
        <div class="bg-slate-900 px-6 py-4 border-b border-slate-800 flex items-center justify-between">
          <h2 class="text-base font-bold text-white tracking-wide">Room Details (#{{ form.room_number }})</h2>
          <span class="px-3 py-1 bg-slate-800 text-slate-300 text-xs font-semibold rounded-full border border-slate-700">
            ID: {{ roomId }}
          </span>
        </div>

        <form @submit.prevent="updateRoom" class="p-6 md:p-8 space-y-6">

          <!-- Success Alert -->
          <transition name="fade">
            <div v-if="successMessage" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
              <CheckCircle2 class="w-5 h-5 text-emerald-600 flex-shrink-0" />
              <p class="text-sm font-semibold text-emerald-800">{{ successMessage }}</p>
            </div>
          </transition>

          <!-- Error Alert -->
          <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
            <AlertTriangle class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
            <div class="flex-1">
              <p class="text-sm font-bold text-red-800">{{ error }}</p>
              <ul v-if="Object.keys(validationErrors).length" class="mt-2 text-xs text-red-700 space-y-1 list-disc list-inside">
                <li v-for="(msgs, field) in validationErrors" :key="field">
                  <strong class="capitalize">{{ field.replace('_', ' ') }}:</strong> {{ msgs.join(', ') }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Input Fields Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Room Number -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Room Number <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.room_number"
                type="text"
                required
                placeholder="e.g. 101"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold text-slate-900 transition"
              />
            </div>

            <!-- Floor -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Floor <span class="text-slate-400 font-normal lowercase">(optional)</span>
              </label>
              <input
                v-model.number="form.floor"
                type="number"
                min="0"
                placeholder="e.g. 1"
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold text-slate-900 transition"
              />
            </div>

            <!-- Room Type Selection -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Room Type <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.room_type_id"
                required
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold text-slate-900 transition"
              >
                <option value="" disabled>-- Select Room Type --</option>
                <option v-for="t in roomTypes" :key="t.id" :value="t.id">
                  {{ t.name }} - {{ t.base_price_per_night ? '$' + t.base_price_per_night + '/night' : '' }}
                </option>
              </select>
            </div>

            <!-- Status Selection -->
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Status <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.status"
                required
                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-semibold text-slate-900 transition"
              >
                <option value="available">Available</option>
                <option value="reserved">Reserved</option>
                <option value="occupied">Occupied</option>
                <option value="cleaning">Cleaning</option>
                <option value="maintenance">Maintenance</option>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Description <span class="text-slate-400 font-normal lowercase">(optional)</span>
            </label>
            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Add room details, views, features..."
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium text-slate-900 transition resize-none"
            ></textarea>
          </div>

          <!-- Active Checkbox -->
          <div class="flex items-center gap-3 p-4 bg-slate-50 border border-slate-200 rounded-xl">
            <input
              type="checkbox"
              v-model="form.is_active"
              id="active-room-check"
              class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500 accent-blue-600 cursor-pointer"
            />
            <label for="active-room-check" class="text-sm font-semibold text-slate-800 cursor-pointer select-none">
              Active Room (Available for guest bookings and assignment)
            </label>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="router.push('/rooms')"
              class="px-6 py-2.5 border-2 border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold rounded-xl text-sm transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading.saving"
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition shadow-md hover:shadow-lg disabled:opacity-50"
            >
              <Loader2 v-if="loading.saving" class="w-4 h-4 animate-spin" />
              <Save v-else class="w-4 h-4" />
              <span>{{ loading.saving ? 'Updating Room...' : 'Save Changes' }}</span>
            </button>
          </div>

        </form>
      </div>

    </div>
  </DashboardLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
