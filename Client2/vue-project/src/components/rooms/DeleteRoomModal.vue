<script setup lang="ts">
import { useLanguageStore } from '@/stores/language'
import { AlertTriangle, Loader2 } from 'lucide-vue-next'

const languageStore = useLanguageStore()

defineProps<{
  open: boolean
  isDeleting?: boolean
  errorMessage?: string | null
  canForce?: boolean
}>()

const emit = defineEmits(['close', 'delete', 'force-delete', 'deactivate'])
</script>

<template>
  <div v-if="open" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex justify-center items-center z-50 p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
      <div class="flex items-center gap-3 text-red-600 dark:text-red-400">
        <div class="p-2.5 bg-red-100 dark:bg-red-950/50 rounded-xl">
          <AlertTriangle class="w-6 h-6" />
        </div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ languageStore.t('delete_room', 'Delete Room') }}</h2>
      </div>

      <p v-if="!errorMessage" class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
        {{ languageStore.t('delete_room_confirm', 'Are you sure you want to delete this room? This action cannot be undone.') }}
      </p>

      <!-- Server Warning / Error Alert -->
      <div v-if="errorMessage" class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 rounded-xl text-xs space-y-2 text-amber-900 dark:text-amber-200">
        <p class="font-medium leading-relaxed">{{ errorMessage }}</p>
      </div>

      <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
        <button
          @click="emit('close')"
          :disabled="isDeleting"
          class="border border-slate-200 dark:border-slate-700 px-4 py-2 rounded-xl text-slate-700 dark:text-slate-300 font-semibold text-xs cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition"
        >
          {{ languageStore.t('cancel', 'Cancel') }}
        </button>

        <button
          v-if="errorMessage"
          @click="emit('deactivate')"
          :disabled="isDeleting"
          class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl font-semibold text-xs cursor-pointer transition shadow-sm"
        >
          {{ languageStore.t('deactivate_room', 'Deactivate Room Instead') }}
        </button>

        <button
          v-if="canForce"
          @click="emit('force-delete')"
          :disabled="isDeleting"
          class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-xl font-bold text-xs cursor-pointer transition shadow-sm inline-flex items-center gap-1.5"
        >
          <Loader2 v-if="isDeleting" class="w-3.5 h-3.5 animate-spin" />
          {{ languageStore.t('force_delete', 'Force Delete') }}
        </button>

        <button
          v-else-if="!errorMessage"
          @click="emit('delete')"
          :disabled="isDeleting"
          class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl font-bold text-xs cursor-pointer transition shadow-sm inline-flex items-center gap-1.5"
        >
          <Loader2 v-if="isDeleting" class="w-3.5 h-3.5 animate-spin" />
          {{ languageStore.t('delete', 'Delete') }}
        </button>
      </div>
    </div>
  </div>
</template>
