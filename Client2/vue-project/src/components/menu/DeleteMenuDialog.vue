<!-- DeleteMenuDialog.vue - Delete Confirmation Dialog with Material Icons -->
<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
  >
    <div
      class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-sm overflow-hidden p-6 text-center"
    >
      <!-- Warning Icon -->
      <div class="flex justify-center mb-4">
        <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center">
          <v-icon size="32" color="red">mdi-alert-circle</v-icon>
        </div>
      </div>
      <!-- Title -->
      <h3 class="text-lg font-bold text-slate-800 mb-2">Delete Menu Item?</h3>

      <!-- Description -->
      <p class="text-sm text-slate-600 mb-6 leading-relaxed">
        This action cannot be undone. This will permanently remove
        <span class="font-semibold text-slate-900">{{ menu?.name }}</span>
        from customer visibility.
      </p>

      <!-- Actions -->
      <div class="flex justify-center gap-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors flex items-center gap-1.5"
        >
          <v-icon size="16">mdi-close</v-icon>
          Keep Item
        </button>
        <button
          @click="$emit('confirm')"
          class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center gap-1.5"
        >
          <v-icon size="16">mdi-trash-outline</v-icon>
          Confirm Delete
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { MenuItem } from '@/types/menu'

defineProps<{
  modelValue: boolean
  menu: MenuItem | null
}>()

defineEmits(['update:modelValue', 'confirm', 'close'])
</script>
