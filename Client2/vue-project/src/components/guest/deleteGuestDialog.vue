<script setup lang="ts">
import type { Guest } from '../../types/guest'

interface Props {
  open: boolean
  guest: Guest | null
  loading?: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm'): void
}>()
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div v-if="open" class="w-full max-w-md rounded-xl bg-white shadow-2xl">
          <!-- Header -->
          <div class="border-b px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                <span class="material-icons text-red-600"> delete_forever </span>
              </div>

              <div>
                <h2 class="text-lg font-semibold text-gray-900">Delete Guest</h2>

                <p class="text-sm text-gray-500">This action cannot be undone.</p>
              </div>
            </div>
          </div>

          <!-- Body -->
          <div class="px-6 py-5">
            <p class="text-gray-700">
              Are you sure you want to delete
              <span class="font-semibold">
                {{ guest?.name }}
              </span>
              from the guest list?
            </p>

            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3">
              <p class="text-sm text-red-700">
                Deleting this guest permanently removes all guest information associated with this
                record.
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-3 border-t bg-gray-50 px-6 py-4">
            <button
              @click="emit('close')"
              class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-100"
            >
              Cancel
            </button>

            <button
              @click="emit('confirm')"
              :disabled="loading"
              class="rounded-lg bg-red-600 px-5 py-2 font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <span v-if="loading"> Deleting... </span>

              <span v-else> Delete Guest </span>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>
