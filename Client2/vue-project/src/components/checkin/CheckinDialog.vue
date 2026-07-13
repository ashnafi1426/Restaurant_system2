<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useCheckInStore } from '@/stores/checkInStore'
import checkInService from '@/services/checkInService'

const props = defineProps<{
  modelValue: boolean
  reservations: any[]
}>()

const emit = defineEmits(['update:modelValue', 'success'])

const store = useCheckInStore()

const loading = ref(false)
const error = ref('')
const searchQuery = ref('')

const reservation = ref<any | null>(null)

watch(
  () => props.modelValue,
  (value) => {
    if (!value) {
      reservation.value = null
      error.value = ''
      searchQuery.value = ''
    }
  },
)

// Only show reservations that are confirmed
// Room availability is validated server-side during check-in
const checkableReservations = computed(() => {
  console.log('🔍 [CHECKIN DIALOG] Computing checkable reservations...')
  console.log('📥 [CHECKIN DIALOG] Props reservations received:', props.reservations.length)

  if (props.reservations.length === 0) {
    console.warn('⚠️  [CHECKIN DIALOG] NO RESERVATIONS PROVIDED TO DIALOG!')
    return []
  }

  // Log first 3 reservations for debugging
  console.log('📋 [CHECKIN DIALOG] Sample reservations:')
  props.reservations.slice(0, 3).forEach((r: any) => {
    console.log(`  - ID: ${r.id}`)
    console.log(`    Status: ${r.status}`)
    console.log(`    Room ID: ${r.room?.id}`)
    console.log(`    Room Status: ${r.room?.status}`)
    console.log(`    Room Number: ${r.room?.room_number}`)
    console.log(`    Guest: ${r.guest?.full_name}`)
  })

  const result = props.reservations.filter((r: any) => {
    const statusCheck = r.status === 'confirmed'

    if (!statusCheck) {
      console.log(`  ❌ ${r.id}: FAILS status check (${r.status} !== 'confirmed')`)
    }
    if (statusCheck) {
      console.log(`  ✅ ${r.id}: PASSES status check - will be shown`)
    }

    return statusCheck
  })

  console.log(
    `✅ [CHECKIN DIALOG] Total checkable reservations: ${result.length} out of ${props.reservations.length}`,
  )

  if (result.length === 0 && props.reservations.length > 0) {
    console.warn('⚠️  [CHECKIN DIALOG] NO CONFIRMED RESERVATIONS FOUND!')
    console.log('📊 [CHECKIN DIALOG] Reservation status breakdown:')
    const statuses = new Map()
    props.reservations.forEach((r: any) => {
      const key = `${r.status}`
      statuses.set(key, (statuses.get(key) || 0) + 1)
    })
    statuses.forEach((count, status) => {
      console.log(`   ${status}: ${count}`)
    })
  }

  return result
})

const filteredReservations = computed(() => {
  if (!searchQuery.value) return checkableReservations.value
  return checkableReservations.value.filter(
    (r) =>
      r.booking_reference?.includes(searchQuery.value) ||
      r.guest?.full_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      r.room?.room_number?.includes(searchQuery.value),
  )
})

async function confirmCheckIn() {
  if (!reservation.value) {
    error.value = 'Please select a reservation'
    return
  }

  loading.value = true
  error.value = ''

  try {
    console.log(' [DIALOG] Attempting check-in with reservation:', reservation.value)
    console.log(' [DIALOG] Reservation ID:', reservation.value.id)
    console.log(' [DIALOG] Reservation ID type:', typeof reservation.value.id)
    console.log(' [DIALOG] Reservation details:')
    console.log('   - Status:', reservation.value.status)
    console.log('   - Guest:', reservation.value.guest?.full_name)
    console.log('   - Room:', reservation.value.room?.room_number)
    console.log('   - Check-in date:', reservation.value.check_in_date)

    await store.checkInGuest(reservation.value.id)
    emit('success')
    emit('update:modelValue', false)
  } catch (err: any) {
    const errorMsg = err.message || 'Failed to check in guest'
    error.value = errorMsg
    console.error(' [DIALOG] Check-in error:', errorMsg)
    console.error(' [DIALOG] Full error details:', err)
    console.error(' [DIALOG] Error response:', err.response?.data)
    console.error(' [DIALOG] Error status:', err.response?.status)
  } finally {
    loading.value = false
  }
}

function closeDialog() {
  emit('update:modelValue', false)
}
</script>

<template>
  <teleport to="body">
    <!-- Dialog Backdrop -->
    <div
      v-if="modelValue"
      class="fixed inset-0 z-40 bg-black/50 transition-opacity"
      @click="closeDialog"
    />

    <!-- Dialog Content -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]"
          @click.stop
        >
          <!-- Header -->
          <div
            class="border-b border-slate-200 bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 rounded-t-2xl text-white flex-shrink-0"
          >
            <div class="flex items-center justify-between">
              <h2 class="text-2xl font-bold flex items-center gap-2">
                <span class="material-symbols-rounded">login</span>
                Guest Check-In
              </h2>
              <button @click="closeDialog" class="p-1 hover:bg-white/20 rounded-lg transition">
                <span class="material-symbols-rounded">close</span>
              </button>
            </div>
          </div>

          <!-- Body - Scrollable -->
          <div class="p-6 space-y-4 overflow-y-auto flex-1">
            <!-- Error Message -->
            <div
              v-if="error"
              class="p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3"
            >
              <span class="material-symbols-rounded text-red-600 flex-shrink-0 mt-0.5">error</span>
              <div>
                <p class="font-semibold text-red-900">Error</p>
                <p class="text-sm text-red-700">{{ error }}</p>
              </div>
            </div>

            <!-- Reservation Selection -->
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                <span class="flex items-center gap-2">
                  <span class="material-symbols-rounded text-base">calendar_month</span>
                  Select Reservation
                </span>
              </label>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by reservation #, guest name, or room..."
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-3"
              />
              <div
                v-if="checkableReservations.length === 0"
                class="border border-amber-300 bg-amber-50 rounded-lg p-4 text-center"
              >
                <p class="text-sm text-amber-800 font-semibold">No Available Reservations</p>
                <p class="text-xs text-amber-700 mt-1">
                  No confirmed reservations found. Please create or confirm a reservation first.
                </p>
              </div>
              <div v-else class="border border-slate-300 rounded-lg max-h-40 overflow-y-auto">
                <button
                  v-for="res in filteredReservations"
                  :key="res.id"
                  @click="reservation = res"
                  :class="[
                    'w-full text-left px-4 py-3 border-b border-slate-200 hover:bg-blue-50 transition text-sm',
                    reservation?.id === res.id && 'bg-blue-100 border-b-2 border-blue-500',
                  ]"
                >
                  <div class="font-semibold text-slate-900">{{ res.booking_reference }}</div>
                  <div class="text-xs text-slate-600">
                    {{ res.guest?.full_name }} • Room {{ res.room?.room_number }}
                  </div>
                </button>
                <div
                  v-if="filteredReservations.length === 0 && searchQuery"
                  class="p-4 text-center text-slate-500 text-sm"
                >
                  No reservations found matching your search
                </div>
              </div>
            </div>

            <!-- Reservation Details -->
            <div v-if="reservation" class="space-y-3">
              <!-- Guest Info -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-slate-900 mb-2 flex items-center gap-2 text-sm">
                  <span class="material-symbols-rounded text-blue-600 text-lg">person</span>
                  Guest Information
                </h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                  <div>
                    <p class="text-slate-600 font-medium">Name</p>
                    <p class="text-slate-900">{{ reservation.guest?.full_name }}</p>
                  </div>
                  <div>
                    <p class="text-slate-600 font-medium">Email</p>
                    <p class="text-slate-900 truncate">{{ reservation.guest?.email }}</p>
                  </div>
                  <div class="col-span-2">
                    <p class="text-slate-600 font-medium">Phone</p>
                    <p class="text-slate-900">{{ reservation.guest?.phone }}</p>
                  </div>
                </div>
              </div>

              <!-- Room Info -->
              <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="font-semibold text-slate-900 mb-2 flex items-center gap-2 text-sm">
                  <span class="material-symbols-rounded text-green-600 text-lg">bed</span>
                  Room & Dates
                </h3>
                <div class="grid grid-cols-2 gap-3 text-xs">
                  <div>
                    <p class="text-slate-600 font-medium">Room Number</p>
                    <p class="text-slate-900 font-semibold">{{ reservation.room?.room_number }}</p>
                  </div>
                  <div>
                    <p class="text-slate-600 font-medium">Status</p>
                    <p class="text-slate-900 capitalize">{{ reservation.room?.status }}</p>
                  </div>
                  <div>
                    <p class="text-slate-600 font-medium">Check-In</p>
                    <p class="text-slate-900">
                      {{ new Date(reservation.check_in_date).toLocaleDateString() }}
                    </p>
                  </div>
                  <div>
                    <p class="text-slate-600 font-medium">Check-Out</p>
                    <p class="text-slate-900">
                      {{ new Date(reservation.check_out_date).toLocaleDateString() }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Info Alert -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start gap-3">
                <span class="material-symbols-rounded text-blue-600 flex-shrink-0 text-lg"
                  >info</span
                >
                <div class="text-xs">
                  <p class="font-semibold text-blue-900">Confirm Check-In</p>
                  <p class="text-blue-700 mt-1">
                    This will change reservation status to <strong>Checked In</strong> and room to
                    <strong>Occupied</strong>.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div
            class="border-t border-slate-200 bg-slate-50 px-6 py-3 rounded-b-2xl flex justify-end gap-3 flex-shrink-0"
          >
            <button
              @click="closeDialog"
              class="px-5 py-2 text-sm text-slate-700 hover:bg-slate-200 rounded-lg transition font-medium"
            >
              Cancel
            </button>
            <button
              @click="confirmCheckIn"
              :disabled="!reservation || loading"
              :class="[
                'px-5 py-2 bg-green-600 text-white rounded-lg transition font-semibold flex items-center gap-2 text-sm',
                !reservation || loading ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700',
              ]"
            >
              <span v-if="loading" class="animate-spin">⏳</span>
              <span v-else class="material-symbols-rounded text-lg">check_circle</span>
              {{ loading ? 'Processing...' : 'Confirm Check-In' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>
