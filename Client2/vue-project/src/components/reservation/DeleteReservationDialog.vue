<script setup lang="ts">
import { computed } from 'vue'

import type { Reservation } from '@/types/reservation'

interface Props {
  modelValue: boolean

  reservation: Reservation | null

  loading?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void

  (e: 'confirm'): void
}>()

const visible = computed({
  get: () => props.modelValue,

  set: (value: boolean) => emit('update:modelValue', value),
})

const close = () => {
  visible.value = false
}

const confirmDelete = () => {
  emit('confirm')
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-200"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-150"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
        <!-- Header -->

        <div class="flex items-center gap-3 border-b px-6 py-5">
          <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
            <span class="material-icons text-red-600"> warning </span>
          </div>

          <div>
            <h2 class="text-xl font-bold text-gray-900">Delete Reservation</h2>

            <p class="text-sm text-gray-500">This action cannot be undone.</p>
          </div>
        </div>

        <!-- Body -->

        <div v-if="reservation" class="space-y-5 px-6 py-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Booking Reference</p>

              <p class="font-semibold">
                {{ reservation.booking_reference }}
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-500">Room</p>

              <p class="font-semibold">
                {{ reservation.room?.room_number }}
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-500">Guest</p>

              <p class="font-semibold">
                {{ reservation.guest?.first_name }}

                {{ reservation.guest?.last_name }}
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-500">Status</p>

              <p class="font-semibold capitalize">
                {{ reservation.status }}
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-500">Check In</p>

              <p class="font-semibold">
                {{ reservation.check_in_date }}
              </p>
            </div>

            <div>
              <p class="text-sm text-gray-500">Check Out</p>

              <p class="font-semibold">
                {{ reservation.check_out_date }}
              </p>
            </div>
          </div>

          <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm text-red-700">
              Deleting this reservation permanently removes it from the system. Ensure the
              reservation is no longer needed before continuing.
            </p>
          </div>
        </div>

        <!-- Footer -->

        <div class="flex justify-end gap-3 border-t px-6 py-5">
          <button @click="close" class="rounded-lg border px-5 py-2 hover:bg-gray-100">
            Cancel
          </button>

          <button
            @click="confirmDelete"
            :disabled="loading"
            class="rounded-lg bg-red-600 px-5 py-2 text-white hover:bg-red-700 disabled:opacity-60"
          >
            <span v-if="loading"> Deleting... </span>

            <span v-else> Delete Reservation </span>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>
