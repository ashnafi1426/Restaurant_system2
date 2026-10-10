<script setup lang="ts">
import { AlertTriangle, Loader2, Trash2, X } from 'lucide-vue-next'

const props = defineProps<{
  show: boolean
  title: string
  message: string
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'confirm'): void
}>()
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center p-4 overflow-hidden"
    >
      <div
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity"
        @click="emit('close')"
      ></div>

      <div
        class="relative z-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5 my-auto animate-in fade-in zoom-in duration-150"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="p-3.5 rounded-2xl bg-rose-500/10 text-rose-500 border border-rose-500/20">
            <AlertTriangle class="w-7 h-7" />
          </div>
          <button
            @click="emit('close')"
            class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-2">
          <h3 class="text-lg font-black text-slate-900 dark:text-white">
            {{ title }}
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium">
            {{ message }}
          </p>
        </div>

        <div
          class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800"
        >
          <button
            @click="emit('close')"
            :disabled="loading"
            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            @click="emit('confirm')"
            :disabled="loading"
            class="px-5 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white text-xs font-extrabold shadow-md shadow-rose-500/20 transition cursor-pointer flex items-center gap-2"
          >
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <Trash2 v-else class="w-4 h-4" />
            <span>{{ loading ? 'Deleting...' : 'Delete Role' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
