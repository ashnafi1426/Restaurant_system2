<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import ReservationStatusBadge from './ReservationStatusBadge.vue'
import type { Reservation } from '@/types/reservation'
import {
  Calendar,
  BedDouble,
  MoreVertical,
  Eye,
  Edit,
  Trash2,
  CheckCircle2,
  LogIn,
  LogOut,
  XCircle,
  Loader2,
  Users,
  CalendarOff
} from 'lucide-vue-next'

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
  if (openedMenu.value === id) {
    openedMenu.value = null
  } else {
    openedMenu.value = id
  }
}

const closeMenu = () => {
  openedMenu.value = null
}

const handleAction = (action: string, reservation: Reservation) => {
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
      setTimeout(() => closeMenu(), 400)
      break
    case 'check-in':
      emit('check-in', reservation)
      setTimeout(() => closeMenu(), 400)
      break
    case 'check-out':
      emit('check-out', reservation)
      setTimeout(() => closeMenu(), 400)
      break
    case 'cancel':
      emit('cancel', reservation)
      setTimeout(() => closeMenu(), 400)
      break
    case 'delete':
      emit('delete', reservation)
      closeMenu()
      break
  }

  setTimeout(() => {
    loadingActionId.value = null
  }, 1500)
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
  return Math.max(1, Math.ceil(diff / (1000 * 60 * 60 * 24)))
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
  <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs font-sans w-full">
    
    <!-- Table Header Bar -->
    <div
      class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 px-4 py-3 backdrop-blur-xs"
    >
      <div class="flex items-center gap-2.5">
        <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
          <Calendar class="w-4 h-4" />
        </div>
        <div>
          <h2 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight">Reservations Catalog</h2>
        </div>
      </div>
      <div v-if="!loading && reservations.length > 0" class="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-black border border-slate-200 dark:border-slate-700">
        {{ reservations.length }} total
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center p-8 text-slate-500 dark:text-slate-400 space-y-2">
      <Loader2 class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" />
      <p class="font-bold text-xs">Loading...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="reservations.length === 0" class="p-8 text-center space-y-2">
      <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
        <CalendarOff class="w-5 h-5" />
      </div>
      <h3 class="text-xs font-black text-slate-900 dark:text-white">No Reservations Found</h3>
    </div>

    <!-- Compact Table View -->
    <div v-else class="overflow-x-auto w-full">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50/90 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
          <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider select-none">
            <th class="px-3 py-2.5 whitespace-nowrap">Booking Ref</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Guest</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Room</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Check-In</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Check-Out</th>
            <th class="px-2 py-2.5 text-center whitespace-nowrap">Guests</th>
            <th class="px-3 py-2.5 text-center whitespace-nowrap">Status</th>
            <th class="px-3 py-2.5 text-right whitespace-nowrap pr-4">Actions</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
          <tr
            v-for="reservation in reservations"
            :key="reservation.id"
            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors duration-150 group"
          >
            <!-- Booking Reference -->
            <td class="px-3 py-2.5 whitespace-nowrap">
              <span class="font-mono font-extrabold text-blue-600 dark:text-blue-400 text-[11px]">
                {{ reservation.booking_reference }}
              </span>
            </td>

            <!-- Guest -->
            <td class="px-3 py-2.5">
              <div v-if="reservation.guest" class="flex items-center gap-2 max-w-[160px]">
                <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-black text-[10px] flex items-center justify-center flex-shrink-0">
                  {{ (reservation.guest.first_name?.[0] || 'G').toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                  <div class="font-bold text-slate-900 dark:text-white truncate text-xs">
                    {{ reservation.guest.first_name }} {{ reservation.guest.last_name }}
                  </div>
                  <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate">
                    {{ reservation.guest.email || reservation.guest.phone || '-' }}
                  </div>
                </div>
              </div>
              <span v-else class="text-slate-400 text-xs italic">No guest assigned</span>
            </td>

            <!-- Room -->
            <td class="px-3 py-2.5 whitespace-nowrap">
              <div v-if="reservation.room" class="flex items-center gap-1">
                <BedDouble class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">Room {{ reservation.room.room_number }}</span>
              </div>
              <span v-else-if="reservation.room_type" class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                {{ reservation.room_type }}
              </span>
              <span v-else class="text-slate-400 text-xs italic">Unassigned</span>
            </td>

            <!-- Check-In Date -->
            <td class="px-3 py-2.5 whitespace-nowrap">
              <div class="font-semibold text-slate-900 dark:text-slate-100 text-xs">
                {{ formatDate(reservation.check_in_date) }}
              </div>
            </td>

            <!-- Check-Out Date -->
            <td class="px-3 py-2.5 whitespace-nowrap">
              <div>
                <div class="font-semibold text-slate-900 dark:text-slate-100 text-xs">
                  {{ formatDate(reservation.check_out_date) }}
                </div>
                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500">
                  {{ calculateNights(reservation.check_in_date, reservation.check_out_date) }} night(s)
                </div>
              </div>
            </td>

            <!-- Number of Guests -->
            <td class="px-2 py-2.5 text-center whitespace-nowrap">
              <span class="inline-flex items-center gap-0.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-md text-[11px] font-bold border border-slate-200 dark:border-slate-700">
                <Users class="w-3 h-3 text-slate-400" />
                {{ reservation.number_of_guests }}
              </span>
            </td>

            <!-- Status Badge -->
            <td class="px-3 py-2.5 text-center whitespace-nowrap">
              <ReservationStatusBadge :status="reservation.status" />
            </td>

            <!-- Action Menu Dropdown -->
            <td class="px-3 py-2.5 text-right whitespace-nowrap pr-4 relative">
              <div class="action-menu inline-block relative">
                <button
                  @click="toggleMenu(reservation.id, $event)"
                  class="p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                  :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': openedMenu === reservation.id }"
                  title="Actions"
                >
                  <MoreVertical class="w-3.5 h-3.5" />
                </button>

                <!-- Dropdown Card Popup -->
                <Transition
                  enter-active-class="transition duration-100 ease-out"
                  leave-active-class="transition duration-75 ease-in"
                  enter-from-class="opacity-0 scale-95 -translate-y-2"
                  enter-to-class="opacity-100 scale-100 translate-y-0"
                  leave-from-class="opacity-100 scale-100 translate-y-0"
                  leave-to-class="opacity-0 scale-95 -translate-y-2"
                >
                  <div
                    v-if="openedMenu === reservation.id"
                    class="absolute right-0 top-8 z-50 w-40 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl p-1 space-y-0.5 text-left"
                  >
                    <!-- View -->
                    <button
                      @click="handleAction('view', reservation)"
                      class="flex w-full items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                      <Eye class="w-3.5 h-3.5 text-blue-500" />
                      <span>View</span>
                    </button>

                    <!-- Edit -->
                    <button
                      @click="handleAction('edit', reservation)"
                      class="flex w-full items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                    >
                      <Edit class="w-3.5 h-3.5 text-amber-500" />
                      <span>Edit</span>
                    </button>

                    <div class="border-t border-slate-100 dark:border-slate-800 my-0.5"></div>

                    <!-- Confirm -->
                    <button
                      v-if="canConfirm(reservation)"
                      @click="handleAction('confirm', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition cursor-pointer disabled:opacity-50"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500" />
                      <span>{{ loadingActionId === reservation.id ? 'Confirming...' : 'Confirm' }}</span>
                    </button>

                    <!-- Check In -->
                    <button
                      v-if="canCheckIn(reservation)"
                      @click="handleAction('check-in', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 transition cursor-pointer disabled:opacity-50"
                    >
                      <LogIn class="w-3.5 h-3.5 text-blue-500" />
                      <span>{{ loadingActionId === reservation.id ? 'Checking In...' : 'Check In' }}</span>
                    </button>

                    <!-- Check Out -->
                    <button
                      v-if="canCheckOut(reservation)"
                      @click="handleAction('check-out', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-500/10 transition cursor-pointer disabled:opacity-50"
                    >
                      <LogOut class="w-3.5 h-3.5 text-purple-500" />
                      <span>{{ loadingActionId === reservation.id ? 'Checking Out...' : 'Check Out' }}</span>
                    </button>

                    <!-- Cancel -->
                    <button
                      v-if="canCancel(reservation)"
                      @click="handleAction('cancel', reservation)"
                      :disabled="loadingActionId === reservation.id"
                      class="flex w-full items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer disabled:opacity-50"
                    >
                      <XCircle class="w-3.5 h-3.5 text-rose-500" />
                      <span>{{ loadingActionId === reservation.id ? 'Cancelling...' : 'Cancel' }}</span>
                    </button>

                    <div class="border-t border-slate-100 dark:border-slate-800 my-0.5"></div>

                    <!-- Delete -->
                    <button
                      @click="handleAction('delete', reservation)"
                      class="flex w-full items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                    >
                      <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                      <span>Delete</span>
                    </button>
                  </div>
                </Transition>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
