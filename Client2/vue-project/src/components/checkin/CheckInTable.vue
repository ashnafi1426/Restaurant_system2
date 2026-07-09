<script setup lang="ts">
import { ref } from 'vue'
import type { CheckIn } from '@/types/checkIn'

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

function getStatusColor(checkIn: CheckIn) {
  return checkIn.checked_out_at ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-700'
}

function getStatusLabel(checkIn: CheckIn) {
  return checkIn.checked_out_at ? '✓ Checked Out' : '🟢 Checked In'
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
  <div class="overflow-hidden">
    <!-- Loading State -->
    <div v-if="loading" class="p-8 text-center">
      <div class="inline-block animate-spin">⏳</div>
      <p class="mt-2 text-slate-600">Loading check-ins...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="checkIns.length === 0" class="p-12 text-center">
      <div class="text-4xl mb-3">🛏️</div>
      <h3 class="text-lg font-semibold text-slate-700 mb-1">No Check-In Records</h3>
      <p class="text-slate-500">Start checking in guests to see them here</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
      <table class="w-full" @click="closeMenu">
        <thead>
          <tr class="border-b border-slate-200 bg-slate-50">
            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Reservation</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Guest</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Room</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Check In</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">
              Expected Check Out
            </th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Status</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="checkIn in checkIns"
            :key="checkIn.id"
            class="border-b border-slate-200 hover:bg-slate-50 transition"
          >
            <!-- Reservation -->
            <td class="px-6 py-4 text-sm">
              <span class="font-medium text-slate-900">{{
                checkIn.reservation.booking_reference
              }}</span>
            </td>

            <!-- Guest -->
            <td class="px-6 py-4 text-sm">
              <div>
                <div class="font-medium text-slate-900">{{ checkIn.guest.full_name }}</div>
                <div class="text-xs text-slate-500">{{ checkIn.guest.phone }}</div>
              </div>
            </td>

            <!-- Room -->
            <td class="px-6 py-4 text-sm">
              <span
                class="inline-block rounded-full bg-blue-100 px-3 py-1 text-blue-700 font-medium"
              >
                Room {{ checkIn.room.room_number }}
              </span>
            </td>

            <!-- Check In Time -->
            <td class="px-6 py-4 text-sm text-slate-700">
              {{ formatDate(checkIn.checked_in_at) }}
            </td>

            <!-- Expected Check Out -->
            <td class="px-6 py-4 text-sm text-slate-700">
              {{ formatDate(checkIn.expected_check_out_at) }}
            </td>

            <!-- Status -->
            <td class="px-6 py-4 text-sm">
              <span
                :class="[
                  'inline-block rounded-full px-3 py-1 font-medium text-xs',
                  getStatusColor(checkIn),
                ]"
              >
                {{ getStatusLabel(checkIn) }}
              </span>
            </td>

            <!-- Actions -->
            <td class="px-6 py-4 text-sm">
              <div class="relative">
                <button
                  @click.stop="toggleMenu(checkIn.id)"
                  class="rounded-lg p-2 hover:bg-slate-200 text-slate-600 transition"
                  title="More Actions"
                >
                  <span class="text-xl">⋮</span>
                </button>

                <!-- Dropdown Menu -->
                <div
                  v-if="openMenuId === checkIn.id"
                  class="absolute right-0 top-full mt-1 z-10 bg-white rounded-lg shadow-lg border border-slate-200 min-w-max"
                  @click.stop
                >
                  <!-- View Action -->
                  <button
                    @click="handleView(checkIn)"
                    class="w-full text-left px-4 py-2 hover:bg-blue-50 text-slate-700 flex items-center gap-3 border-b border-slate-100 first:rounded-t-lg"
                  >
                    <span class="text-lg">👁️</span>
                    <span class="text-sm font-medium">View Details</span>
                  </button>

                  <!-- Checkout Action (only if not checked out) -->
                  <button
                    v-if="!checkIn.checked_out_at"
                    @click="handleCheckout(checkIn)"
                    class="w-full text-left px-4 py-2 hover:bg-green-50 text-slate-700 flex items-center gap-3 border-b border-slate-100"
                  >
                    <span class="text-lg">🚪</span>
                    <span class="text-sm font-medium">Check Out</span>
                  </button>

                  <!-- Delete Action -->
                  <button
                    @click="handleDelete(checkIn)"
                    class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 flex items-center gap-3 last:rounded-b-lg"
                  >
                    <span class="text-lg">🗑️</span>
                    <span class="text-sm font-medium">Delete</span>
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

<style scoped>
table {
  border-collapse: collapse;
}
</style>
