<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import ReservationStatusBadge from './ReservationStatusBadge.vue'
import type { Reservation } from '@/types/reservation'

interface Props {
  reservations: Reservation[]
  loading: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'view', reservation: Reservation): void
  (e: 'edit', reservation: Reservation): void
  (e: 'delete', reservation: Reservation): void
  (e: 'confirm', reservation: Reservation): void
  (e: 'check-in', reservation: Reservation): void
  (e: 'check-out', reservation: Reservation): void
  (e: 'cancel', reservation: Reservation): void
}>()

const openedMenu = ref<string | null>(null)
const loadingActionId = ref<string | null>(null)

const toggleMenu = (id: string, event: Event) => {
  event.stopPropagation()
  console.log('🔘 [MENU] Toggle clicked for reservation:', id)
  console.log('🔘 [MENU] Current openedMenu:', openedMenu.value)
  console.log('🔘 [MENU] Is same ID?', openedMenu.value === id)

  if (openedMenu.value === id) {
    openedMenu.value = null
    console.log('🔘 [MENU] Menu closed')
  } else {
    openedMenu.value = id
    console.log('🔘 [MENU] Menu opened for:', id)
  }
}

const closeMenu = () => {
  console.log('🔘 [MENU] Force close menu')
  openedMenu.value = null
}

const handleAction = (action: string, reservation: Reservation) => {
  console.log('⚙️ [ACTION] Triggered:', action, 'for reservation:', reservation.id)
  loadingActionId.value = reservation.id

  switch (action) {
    case 'view':
      emit('view', reservation)
      closeMenu()
      break
    case 'edit':
      emit('edit', reservation)
      closeMenu()
      break
    case 'confirm':
      emit('confirm', reservation)
      setTimeout(() => closeMenu(), 500)
      break
    case 'check-in':
      emit('check-in', reservation)
      setTimeout(() => closeMenu(), 500)
      break
    case 'check-out':
      emit('check-out', reservation)
      setTimeout(() => closeMenu(), 500)
      break
    case 'cancel':
      emit('cancel', reservation)
      setTimeout(() => closeMenu(), 500)
      break
    case 'delete':
      emit('delete', reservation)
      closeMenu()
      break
  }

  setTimeout(() => {
    loadingActionId.value = null
  }, 2000)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const calculateNights = (checkIn: string, checkOut: string) => {
  if (!checkIn || !checkOut) return 0
  const start = new Date(checkIn)
  const end = new Date(checkOut)
  const diff = end.getTime() - start.getTime()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

const canCheckIn = (reservation: Reservation) => {
  return reservation.status === 'confirmed'
}

const canCheckOut = (reservation: Reservation) => {
  return reservation.status === 'checked_in'
}

const canCancel = (reservation: Reservation) => {
  return ['pending', 'confirmed'].includes(reservation.status)
}

const canConfirm = (reservation: Reservation) => {
  return reservation.status === 'pending'
}

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  if (!target.closest('.action-menu')) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-3 py-2"
    >
      <div class="flex items-center gap-2">
        <div class="rounded-lg bg-purple-100 p-1">
          <span class="material-symbols-rounded text-xs text-purple-600">event</span>
        </div>
        <div>
          <h2 class="text-sm font-semibold text-slate-800">Reservations</h2>
        </div>
      </div>
      <div v-if="!loading && reservations.length > 0" class="text-xs text-slate-500">
        {{ reservations.length }}
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center p-6 text-slate-500">
      <!-- UNIFIED CYAN + YELLOW SPINNER (size: w-8 h-8) -->
      <div class="relative w-8 h-8 mb-2">
        <!-- Static background - BRIGHT CYAN -->
        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="40" fill="none" stroke="#0EA5E9" stroke-width="5" opacity="0.3" />
        </svg>
        
        <!-- Animated spinner - BRIGHT YELLOW -->
        <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
          <svg viewBox="0 0 100 100" class="w-full h-full">
            <circle cx="50" cy="50" r="40" fill="none" stroke="#FBBF24" stroke-width="6" stroke-linecap="round" stroke-dasharray="60 240" />
          </svg>
        </div>
      </div>
      <p class="font-medium text-xs">Loading...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="reservations.length === 0" class="p-6 text-center">
      <div
        class="mx-auto w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-2"
      >
        <span class="material-symbols-rounded text-lg text-slate-400">event_busy</span>
      </div>
      <h3 class="text-sm font-semibold text-slate-700">No Reservations</h3>
    </div>

    <!-- Table - Compact -->
    <div v-else class="overflow-x-auto">
      <table class="w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th
              class="px-2 py-1.5 text-left font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Booking Ref
            </th>
            <th
              class="px-2 py-1.5 text-left font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Guest
            </th>
            <th
              class="px-2 py-1.5 text-left font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Room
            </th>
            <th
              class="px-2 py-1.5 text-left font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Check-In
            </th>
            <th
              class="px-2 py-1.5 text-left font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Check-Out
            </th>
            <th
              class="px-2 py-1.5 text-center font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Guests
            </th>
            <th
              class="px-2 py-1.5 text-center font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Status
            </th>
            <th
              class="px-2 py-1.5 text-center font-semibold text-slate-600 text-xs whitespace-nowrap"
            >
              Actions
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="reservation in reservations"
            :key="reservation.id"
            class="hover:bg-slate-50 transition-colors"
          >
            <!-- Booking Reference -->
            <td class="px-2 py-1.5 text-xs">
              <span class="font-mono font-semibold text-blue-600">
                {{ reservation.booking_reference }}
              </span>
            </td>

            <!-- Guest -->
            <td class="px-2 py-1.5 text-xs">
              <div v-if="reservation.guest" class="truncate min-w-0">
                <div class="font-semibold text-slate-900 truncate text-xs">
                  {{ reservation.guest.first_name }} {{ reservation.guest.last_name }}
                </div>
                <div class="text-xs text-slate-500 truncate">
                  {{ reservation.guest.email || reservation.guest.phone || '-' }}
                </div>
              </div>
              <span v-else class="text-slate-400 text-xs">No guest</span>
            </td>

            <!-- Room -->
            <td class="px-2 py-1.5 text-xs">
              <div v-if="reservation.room" class="text-xs">
                <div class="font-semibold">Room {{ reservation.room.room_number }}</div>
              </div>
            </td>

            <!-- Check-In Date -->
            <td class="px-2 py-1.5 text-xs">
              <div class="text-slate-700 font-medium">
                {{ formatDate(reservation.check_in_date) }}
              </div>
            </td>

            <!-- Check-Out Date -->
            <td class="px-2 py-1.5 text-xs">
              <div class="text-slate-700 font-medium">
                {{ formatDate(reservation.check_out_date) }}
              </div>
              <div class="text-xs text-slate-500">
                {{ calculateNights(reservation.check_in_date, reservation.check_out_date) }}n
              </div>
            </td>

            <!-- Number of Guests -->
            <td class="px-2 py-1.5 text-center">
              <div
                class="inline-flex items-center gap-0.5 bg-slate-100 px-1.5 py-0.5 rounded text-xs font-semibold"
              >
                {{ reservation.number_of_guests }}
              </div>
            </td>

            <!-- Status -->
            <td class="px-2 py-1.5 text-center">
              <ReservationStatusBadge :status="reservation.status" />
            </td>

            <!-- Actions -->
            <td class="relative px-2 py-1.5 text-center">
              <div class="action-menu inline-block relative">
                <button
                  @click="toggleMenu(reservation.id, $event)"
                  class="flex h-7 w-7 items-center justify-center rounded hover:bg-slate-100 transition"
                  :class="{ 'bg-slate-100': openedMenu === reservation.id }"
                  title="Actions menu"
                >
                  <span class="material-symbols-rounded text-sm">more_vert</span>
                </button>

                <transition
                  enter-active-class="transition duration-100 ease-out"
                  leave-active-class="transition duration-75 ease-in"
                  enter-from-class="opacity-0 scale-95 -translate-y-2"
                  enter-to-class="opacity-100 scale-100 translate-y-0"
                  leave-from-class="opacity-100 scale-100 translate-y-0"
                  leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                  <div
                    v-if="openedMenu === reservation.id"
                    class="absolute right-0 top-8 z-50 w-40 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
                  >
                    <!-- View -->
                    <button
                      @click="handleAction('view', reservation)"
                      class="flex w-full items-center gap-2 px-3 py-2 hover:bg-blue-50 text-left text-xs text-slate-700 transition"
                    >
                      <span class="material-symbols-rounded text-blue-600 text-sm">visibility</span>
                      <span class="font-medium">View</span>
                    </button>

                    <!-- Edit -->
                    <button
                      @click="handleAction('edit', reservation)"
                      class="flex w-full items-center gap-2 px-3 py-2 hover:bg-green-50 text-left text-xs text-slate-700 transition"
                    >
                      <span class="material-symbols-rounded text-green-600 text-sm">edit</span>
                      <span class="font-medium">Edit</span>
                    </button>

                    <div class="border-t border-slate-200"></div>

                    <!-- Confirm -->
                    <button
                      v-if="canConfirm(reservation)"
                      @click="handleAction('confirm', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-3 py-2 hover:bg-purple-50 text-left text-xs text-slate-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span
                        v-if="loadingActionId === reservation.id"
                        class="material-symbols-rounded text-purple-600 text-sm animate-spin"
                        >hourglass_bottom</span
                      >
                      <span v-else class="material-symbols-rounded text-purple-600 text-sm"
                        >verified_user</span
                      >
                      <span class="font-medium">{{
                        loadingActionId === reservation.id ? 'Confirming...' : 'Confirm'
                      }}</span>
                    </button>

                    <!-- Check In -->
                    <button
                      v-if="canCheckIn(reservation)"
                      @click="handleAction('check-in', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-3 py-2 hover:bg-green-50 text-left text-xs text-slate-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span
                        v-if="loadingActionId === reservation.id"
                        class="material-symbols-rounded text-green-600 text-sm animate-spin"
                        >hourglass_bottom</span
                      >
                      <span v-else class="material-symbols-rounded text-green-600 text-sm"
                        >login</span
                      >
                      <span class="font-medium">{{
                        loadingActionId === reservation.id ? 'Checking in...' : 'Check In'
                      }}</span>
                    </button>

                    <!-- Check Out -->
                    <button
                      v-if="canCheckOut(reservation)"
                      @click="handleAction('check-out', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-3 py-2 hover:bg-cyan-50 text-left text-xs text-slate-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span
                        v-if="loadingActionId === reservation.id"
                        class="material-symbols-rounded text-cyan-600 text-sm animate-spin"
                        >hourglass_bottom</span
                      >
                      <span v-else class="material-symbols-rounded text-cyan-600 text-sm"
                        >logout</span
                      >
                      <span class="font-medium">{{
                        loadingActionId === reservation.id ? 'Checking out...' : 'Check Out'
                      }}</span>
                    </button>

                    <!-- Cancel -->
                    <button
                      v-if="canCancel(reservation)"
                      @click="handleAction('cancel', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-3 py-2 hover:bg-amber-50 text-left text-xs text-slate-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <span
                        v-if="loadingActionId === reservation.id"
                        class="material-symbols-rounded text-amber-600 text-sm animate-spin"
                        >hourglass_bottom</span
                      >
                      <span v-else class="material-symbols-rounded text-amber-600 text-sm"
                        >cancel</span
                      >
                      <span class="font-medium">{{
                        loadingActionId === reservation.id ? 'Cancelling...' : 'Cancel'
                      }}</span>
                    </button>

                    <div class="border-t border-slate-200"></div>

                    <!-- Delete -->
                    <button
                      @click="handleAction('delete', reservation)"
                      class="flex w-full items-center gap-2 px-3 py-2 text-red-600 hover:bg-red-50 text-left text-xs transition"
                    >
                      <span class="material-symbols-rounded text-sm">delete</span>
                      <span class="font-medium">Delete</span>
                    </button>
                  </div>
                </transition>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
