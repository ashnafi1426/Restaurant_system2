<script setup lang="ts">
import { useLanguageStore } from '@/stores/language'

defineProps<{ open: boolean }>()
const emit = defineEmits(['close', 'confirm'])
const languageStore = useLanguageStore()
</script>

<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/80" @click="emit('close')"></div>

    <div
      class="relative w-96 bg-slate-900 text-white rounded-xl shadow-2xl p-6 border border-slate-700 font-sans"
    >
      <h2 class="text-lg font-semibold mb-3">
        {{ languageStore.t('delete_room_type', 'Delete Room Type') }}
      </h2>

      <p class="text-slate-300 mb-6 text-sm">
        {{
          languageStore.t(
            'delete_room_type_confirm',
            'Are you sure you want to delete this room type? This action cannot be undone.',
          )
        }}
      </p>

      <div class="flex justify-end gap-3">
        <button
          class="px-4 py-2 rounded-md border border-slate-600 hover:bg-slate-800 transition text-sm cursor-pointer"
          @click="emit('close')"
        >
          {{ languageStore.t('cancel', 'Cancel') }}
        </button>

        <button
          class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 transition text-sm cursor-pointer"
          @click="emit('confirm')"
        >
          {{ languageStore.t('delete', 'Delete') }}
        </button>
      </div>
    </div>
  </div>
</template>
