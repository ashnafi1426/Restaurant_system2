<script setup lang="ts">
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

defineProps<{
  open: boolean
}>()

const emit = defineEmits(['close', 'delete'])
</script>

<template>
  <div v-if="open" class="fixed inset-0 bg-black/40 flex justify-center items-center z-50">
    <div class="bg-white dark:bg-slate-900 rounded-xl w-96 p-6 border border-slate-200 dark:border-slate-800 shadow-2xl">
      <h2 class="text-xl font-bold mb-3 text-slate-900 dark:text-white">{{ languageStore.t('delete_room', 'Delete Room') }}</h2>

      <p class="text-slate-600 dark:text-slate-400 text-sm">{{ languageStore.t('delete_room_confirm', 'Are you sure you want to delete this room?') }}</p>

      <div class="flex justify-end gap-3 mt-6">
        <button @click="emit('close')" class="border border-slate-200 dark:border-slate-700 px-4 py-2 rounded-lg text-slate-700 dark:text-slate-300 font-bold text-xs cursor-pointer">
          {{ languageStore.t('cancel', 'Cancel') }}
        </button>

        <button @click="emit('delete')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-bold text-xs cursor-pointer">
          {{ languageStore.t('delete', 'Delete') }}
        </button>
      </div>
    </div>
  </div>
</template>
