<script setup lang="ts">
import { ref } from 'vue'
import type { CheckIn } from '@/types/checkIn'
import {
  BedDouble,
  MoreVertical,
  Eye,
  LogOut,
  Trash2,
  Loader2,
  Calendar,
  User,
  CalendarOff
} from 'lucide-vue-next'

const props = defineProps<{
  loading: boolean
  checkIns: CheckIn[]
}>()

const emit = defineEmits<{
  (e: 'view', item: CheckIn): void
  (e: 'checkout', item: CheckIn): void
  (e: 'delete', item: CheckIn): void
}>()

const openMenuId = ref<string | null>(null)

function formatDate(date: string) {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function toggleMenu(id: string) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function closeMenu() {
  openMenuId.value = null
}

function handleView(item: CheckIn) {
  emit('view', item)
  closeMenu()
}

function handleCheckout(item: CheckIn) {
  emit('checkout', item)
  closeMenu()
}

function handleDelete(item: CheckIn) {
  emit('delete', item)
  closeMenu()
}
</script>

<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs font-sans w-full">
    <!-- Loading State -->
    <div v-if="loading" class="p-12 text-center flex flex-col items-center justify-center space-y-2">
      <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
      <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading check-in records...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="checkIns.length === 0" class="p-12 text-center space-y-2">
      <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
        <CalendarOff class="w-6 h-6" />
      </div>
      <h3 class="text-sm font-black text-slate-900 dark:text-white">No Check-In Records</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400">Start checking in guests to see them here.</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto w-full">
      <table class="w-full text-left border-collapse min-w-[850px]" @click="closeMenu">
        <thead class="bg-slate-50/90 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
          <tr class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider select-none">
            <th class="px-3 py-2.5 whitespace-nowrap">Reservation</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Guest</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Room</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Check In</th>
            <th class="px-3 py-2.5 whitespace-nowrap">Expected Check Out</th>
            <th class="px-3 py-2.5 text-center whitespace-nowrap">Status</th>
            <th class="px-3 py-2.5 text-right whitespace-nowrap pr-4">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
          <tr
            v-for="checkIn in checkIns"
            :key="checkIn.id"
            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors duration-150 group"
          >
            <!-- Reservation -->
            <td class="px-3 py-2.5 whitespace-nowrap">
              <span class="font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs">
                {{ checkIn.reservation?.booking_reference || checkIn.id }}
              </span>
            </td>

            <!-- Guest -->
            <td class="px-3 py-2.5">
              <div class="flex items-center gap-2 max-w-[170px]">
                <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-black text-[10px] flex items-center justify-center flex-shrink-0">
                  {{ (checkIn.guest?.full_name?.[0] || 'G').toUpperCase() }}
                </div>
                <div class="min-w-0 flex-1">
                  <div class="font-bold text-slate-900 dark:text-white truncate text-xs">
                    {{ checkIn.guest?.full_name || 'Guest' }}
                  </div>
                  <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate">
                    {{ checkIn.guest?.phone || checkIn.guest?.email || '-' }}
                  </div>
                </div>
              </div>
            </td>

            <!-- Room -->
            <td class="px-3 py-2.5 whitespace-nowrap">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-extrabold text-xs">
                <BedDouble class="w-3.5 h-3.5" />
                Room {{ checkIn.room?.room_number || checkIn.room_id }}
              </span>
            </td>

            <!-- Check In Time -->
            <td class="px-3 py-2.5 whitespace-nowrap font-bold text-slate-800 dark:text-slate-200 text-xs">
              {{ formatDate(checkIn.checked_in_at) }}
            </td>

            <!-- Expected Check Out -->
            <td class="px-3 py-2.5 whitespace-nowrap font-bold text-slate-800 dark:text-slate-200 text-xs">
              {{ formatDate(checkIn.expected_check_out_at) }}
            </td>

            <!-- Status -->
            <td class="px-3 py-2.5 text-center whitespace-nowrap">
              <span
                v-if="checkIn.checked_out_at"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                <span>Checked Out</span>
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Checked In</span>
              </span>
            </td>

            <!-- Actions -->
            <td class="px-3 py-2.5 text-right whitespace-nowrap pr-4 relative">
              <div class="relative inline-block">
                <button
                  @click.stop="toggleMenu(checkIn.id)"
                  class="p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                  :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white': openMenuId === checkIn.id }"
                  title="Actions"
                >
                  <MoreVertical class="w-3.5 h-3.5" />
                </button>

                <!-- Dropdown Menu Popup -->
                <div
                  v-if="openMenuId === checkIn.id"
                  class="absolute right-0 top-8 z-50 w-40 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-1.5 space-y-1 text-left"
                  @click.stop
                >
                  <!-- View Action -->
                  <button
                    @click="handleView(checkIn)"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                  >
                    <Eye class="w-3.5 h-3.5 text-blue-500" />
                    <span>View Details</span>
                  </button>

                  <!-- Checkout Action (only if not checked out) -->
                  <button
                    v-if="!checkIn.checked_out_at"
                    @click="handleCheckout(checkIn)"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition cursor-pointer"
                  >
                    <LogOut class="w-3.5 h-3.5 text-emerald-500" />
                    <span>Check Out</span>
                  </button>

                  <div class="border-t border-slate-100 dark:border-slate-800 my-0.5"></div>

                  <!-- Delete Action -->
                  <button
                    @click="handleDelete(checkIn)"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition cursor-pointer"
                  >
                    <Trash2 class="w-3.5 h-3.5 text-rose-500" />
                    <span>Delete</span>
                  </button>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
