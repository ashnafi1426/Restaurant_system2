<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
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

const toggleMenu = (id: string) => {
  openedMenu.value = openedMenu.value === id ? null : id
}

const closeMenu = () => {
  openedMenu.value = null
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
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-4 sm:px-5 md:px-6 lg:px-8 py-3 sm:py-4 md:py-5"
    >
      <div class="flex items-center gap-2 sm:gap-3 md:gap-4">
        <div class="rounded-lg bg-purple-100 p-2">
          <span class="material-symbols-rounded text-sm md:text-base text-purple-600">event</span>
        </div>
        <div>
          <h2 class="text-base sm:text-lg md:text-xl font-semibold text-slate-800">
            Reservation Records
          </h2>
          <p class="hidden sm:block text-xs md:text-sm text-slate-500">
            Complete list of hotel reservations
          </p>
        </div>
      </div>
      <div
        v-if="!loading && reservations.length > 0"
        class="text-xs md:text-sm text-slate-500 whitespace-nowrap"
      >
        {{ reservations.length }} reservation{{ reservations.length !== 1 ? 's' : '' }}
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="loading"
      class="flex flex-col items-center justify-center p-8 sm:p-12 md:p-16 text-slate-500"
    >
      <div
        class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 md:h-14 md:w-14 border-b-2 border-purple-600 mb-3 md:mb-4"
      ></div>
      <p class="font-medium text-sm md:text-base">Loading reservations...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="reservations.length === 0" class="p-8 sm:p-12 md:p-16 text-center">
      <div
        class="mx-auto w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-full bg-slate-100 flex items-center justify-center mb-3 md:mb-4"
      >
        <span class="material-symbols-rounded text-2xl sm:text-3xl md:text-4xl text-slate-400"
          >event_busy</span
        >
      </div>
      <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-700 mb-2">
        No Reservations Found
      </h3>
      <p class="text-xs sm:text-sm md:text-base text-slate-500">
        No reservation records match your current filters
      </p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto -mx-4 sm:mx-0">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider"
            >
              Booking Reference
            </th>
            <th
              class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider"
            >
              Guest
            </th>
            <th
              class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider"
            >
              Room
            </th>
            <th
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-left text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider"
            >
              Dates
            </th>
            <th
              class="hidden lg:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider"
            >
              Guests
            </th>
            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider"
            >
              Status
            </th>
            <th
              class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 md:py-4 text-center text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider w-10 md:w-24"
            >
              Actions
            </th>
          </tr>
        </thead>

        <tbody class="bg-white divide-y divide-slate-200">
          <tr
            v-for="reservation in reservations"
            :key="reservation.id"
            class="hover:bg-slate-50 transition-colors"
          >
            <!-- Booking Reference -->
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <span class="material-symbols-rounded text-sm text-slate-400"
                  >confirmation_number</span
                >
                <span class="font-mono font-semibold text-blue-600">
                  {{ reservation.booking_reference }}
                </span>
              </div>
            </td>

            <!-- Guest -->
            <td class="px-6 py-4">
              <div v-if="reservation.guest" class="flex items-center gap-3">
                <div
                  class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white font-semibold"
                >
                  {{ reservation.guest.first_name?.charAt(0) || '?'
                  }}{{ reservation.guest.last_name?.charAt(0) || '?' }}
                </div>
                <div>
                  <div class="font-semibold text-slate-900">
                    {{ reservation.guest.first_name }} {{ reservation.guest.last_name }}
                  </div>
                  <div class="text-sm text-slate-500">
                    {{ reservation.guest.email || reservation.guest.phone || 'No contact info' }}
                  </div>
                </div>
              </div>
              <div v-else class="flex items-center gap-2 text-slate-400 text-sm">
                <span class="material-symbols-rounded">person_off</span>
                <span>Guest info unavailable</span>
              </div>
            </td>

            <!-- Room -->
            <td class="px-6 py-4">
              <div v-if="reservation.room" class="flex items-center gap-2">
                <span class="material-symbols-rounded text-sm text-slate-400">meeting_room</span>
                <div>
                  <div class="font-semibold text-slate-900">
                    Room {{ reservation.room.room_number }}
                  </div>
                  <div v-if="reservation.room.room_type" class="text-sm text-slate-500">
                    {{ reservation.room.room_type }}
                  </div>
                </div>
              </div>
              <span v-else class="text-slate-400 text-sm">-</span>
            </td>

            <!-- Dates -->
            <td class="px-6 py-4">
              <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm">
                  <span class="material-symbols-rounded text-xs text-green-600">login</span>
                  <span class="text-slate-700">{{ formatDate(reservation.check_in_date) }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                  <span class="material-symbols-rounded text-xs text-red-600">logout</span>
                  <span class="text-slate-700">{{ formatDate(reservation.check_out_date) }}</span>
                </div>
                <div class="text-xs text-slate-500 mt-1">
                  {{ calculateNights(reservation.check_in_date, reservation.check_out_date) }}
                  night(s)
                </div>
              </div>
            </td>

            <!-- Number of Guests -->
            <td class="px-6 py-4 text-center">
              <div class="inline-flex items-center gap-1 bg-slate-100 px-3 py-1 rounded-full">
                <span class="material-symbols-rounded text-sm text-slate-600">groups</span>
                <span class="font-semibold text-slate-900">{{ reservation.number_of_guests }}</span>
              </div>
            </td>

            <!-- Status -->
            <td class="px-6 py-4 text-center">
              <ReservationStatusBadge :status="reservation.status" />
            </td>

            <!-- Actions -->
            <td class="relative px-6 py-4 text-center">
              <div class="action-menu inline-block relative">
                <button
                  @click.stop="toggleMenu(reservation.id)"
                  class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-slate-100 transition-colors mx-auto"
                  :class="{ 'bg-slate-100': openedMenu === reservation.id }"
                >
                  <span class="material-symbols-rounded text-slate-600">more_vert</span>
                </button>

                <transition
                  enter-active-class="transition duration-150 ease-out"
                  leave-active-class="transition duration-100 ease-in"
                  enter-from-class="opacity-0 scale-95"
                  enter-to-class="opacity-100 scale-100"
                  leave-from-class="opacity-100 scale-100"
                  leave-to-class="opacity-0 scale-95"
                >
                  <div
                    v-if="openedMenu === reservation.id"
                    class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
                  >
                    <!-- View -->
                    <button
                      @click="
                        () => {
                          emit('view', reservation)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-blue-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-blue-600">visibility</span>
                      <span class="font-medium text-slate-700">View Details</span>
                    </button>

                    <!-- Edit -->
                    <button
                      @click="
                        () => {
                          emit('edit', reservation)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-green-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-green-600">edit</span>
                      <span class="font-medium text-slate-700">Edit Reservation</span>
                    </button>

                    <div class="border-t border-slate-200"></div>

                    <!-- Confirm -->
                    <button
                      v-if="canConfirm(reservation)"
                      @click="
                        () => {
                          emit('confirm', reservation)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-purple-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-purple-600">verified_user</span>
                      <span class="font-medium text-slate-700">Confirm Reservation</span>
                    </button>

                    <!-- Check In -->
                    <button
                      v-if="canCheckIn(reservation)"
                      @click="
                        () => {
                          emit('check-in', reservation)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-green-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-green-600">login</span>
                      <span class="font-medium text-slate-700">Check In Guest</span>
                    </button>

                    <!-- Check Out -->
                    <button
                      v-if="canCheckOut(reservation)"
                      @click="
                        () => {
                          emit('check-out', reservation)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-blue-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-blue-600">logout</span>
                      <span class="font-medium text-slate-700">Check Out Guest</span>
                    </button>

                    <!-- Cancel -->
                    <button
                      v-if="canCancel(reservation)"
                      @click="
                        () => {
                          emit('cancel', reservation)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 hover:bg-amber-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded text-amber-600">cancel</span>
                      <span class="font-medium text-slate-700">Cancel Reservation</span>
                    </button>

                    <div class="border-t border-slate-200"></div>

                    <!-- Delete -->
                    <button
                      @click="
                        () => {
                          emit('delete', reservation)
                          closeMenu()
                        }
                      "
                      class="flex w-full items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 transition-colors text-left"
                    >
                      <span class="material-symbols-rounded">delete</span>
                      <span class="font-medium">Delete Reservation</span>
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
