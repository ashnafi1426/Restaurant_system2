<!-- DeleteMenuDialog.vue - Delete Confirmation Dialog with Material Icons -->
<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4"
  >
    <div
      class="bg-white rounded-lg sm:rounded-xl shadow-xl border border-slate-200 w-full max-w-sm overflow-hidden px-4 sm:px-6 py-4 sm:py-6 text-center"
    >
      <!-- Warning Icon -->
      <div class="flex justify-center mb-3 sm:mb-4">
        <div class="w-14 sm:w-16 h-14 sm:h-16 rounded-full bg-red-50 flex items-center justify-center">
          <v-icon size="28" sm:size="32" color="red">mdi-alert-circle</v-icon>
        </div>
      </div>
      <!-- Title -->
      <h3 class="text-base sm:text-lg font-bold text-slate-800 mb-1.5 sm:mb-2">Delete Menu Item?</h3>

      <!-- Description -->
      <p class="text-xs sm:text-sm text-slate-600 mb-4 sm:mb-6 leading-relaxed">
        This action cannot be undone. This will permanently remove
        <span class="font-semibold text-slate-900">{{ menu?.name }}</span>
        from customer visibility.
      </p>

      <!-- Actions -->
      <div class="flex justify-center gap-2 sm:gap-3">
        <button
          @click="$emit('close')"
          class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors flex items-center justify-center gap-1 sm:gap-1.5 min-h-9 sm:min-h-10"
        >
          <v-icon size="14" sm:size="16">mdi-close</v-icon>
          <span class="hidden sm:inline">Keep Item</span><span class="sm:hidden">Keep</span>
        </button>
        <button
          @click="$emit('confirm')"
          class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center justify-center gap-1 sm:gap-1.5 min-h-9 sm:min-h-10"
        >
          <v-icon size="14" sm:size="16">mdi-trash-outline</v-icon>
          <span class="hidden sm:inline">Confirm Delete</span><span class="sm:hidden">Delete</span>
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
