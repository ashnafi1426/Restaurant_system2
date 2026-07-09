<script setup lang="ts">
defineProps<{ open: boolean }>()

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- DARK OVERLAY -->
    <div class="absolute inset-0 bg-black/80" @click="emit('close')"></div>

    <!-- MODAL -->
    <div
      class="relative w-96 bg-slate-900 text-white rounded-xl shadow-2xl p-6 border border-slate-700"
    >
      <h2 class="text-lg font-semibold mb-3">Delete User</h2>

      <p class="text-slate-300 mb-6">
        Are you sure you want to delete this user? This action cannot be undone.
      </p>

      <div class="flex justify-end gap-3">
        <button
          class="px-4 py-2 rounded-md border border-slate-600 hover:bg-slate-800"
          @click="emit('close')"
        >
          Cancel
        </button>

        <button class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700" @click="emit('confirm')">
          Delete
        </button>
      </div>
    </div>
  </div>
</template>
