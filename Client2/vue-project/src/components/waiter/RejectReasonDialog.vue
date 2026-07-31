<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
      <!-- Header -->
      <div class="border-b border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900">Reject Assignment</h2>
        <p class="text-sm text-gray-600 mt-1">Please provide a reason for rejection</p>
      </div>

      <!-- Content -->
      <form @submit.prevent="handleConfirm" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Reason (optional)
          </label>
          <textarea
            v-model="reason"
            rows="4"
            maxlength="500"
            placeholder="Why are you rejecting this assignment?"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">{{ reason.length }}/500 characters</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="$emit('cancel')"
            class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 font-medium rounded-lg hover:bg-gray-300 transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="flex-1 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
          >
            Reject
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const reason = ref('')

const emit = defineEmits<{
  confirm: [reason: string]
  cancel: []
}>()

const handleConfirm = () => {
  emit('confirm', reason.value)
  reason.value = ''
}
</script>
