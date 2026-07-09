<script setup lang="ts">
defineProps<{
  open: boolean
}>()

const emit = defineEmits(['close', 'delete'])
</script>

<template>
  <div v-if="open" class="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
    <div class="bg-white rounded-xl w-96 p-6">
      <h2 class="text-xl font-bold mb-3">Delete Room</h2>

      <p class="text-slate-600">Are you sure you want to delete this room?</p>

      <div class="flex justify-end gap-3 mt-6">
        <button @click="emit('close')" class="border px-4 py-2 rounded-lg">Cancel</button>

        <button @click="emit('delete')" class="bg-red-600 text-white px-4 py-2 rounded-lg">
          Delete
        </button>
      </div>
    </div>
  </div>
</template>
