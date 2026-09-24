<template>
  <div
    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5"
  >
    <div>
      <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ title }}</h1>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ subtitle }}</p>
    </div>
    <div class="flex items-center gap-3 self-start md:self-auto">
      <button
        @click="$emit('export')"
        class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 dark:border-slate-800 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer"
      >
        <Download class="w-4 h-4" />
        <span>{{ languageStore.t('export_menu', 'Export Menu') }}</span>
      </button>
      <button
        @click="$emit('manage-categories')"
        class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 dark:border-slate-800 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer"
      >
        <FolderTree class="w-4 h-4" />
        <span>{{ languageStore.t('manage_categories', 'Manage Categories') }}</span>
      </button>
      <button
        @click="$emit('add-item')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white rounded-lg text-sm font-medium transition shadow-sm cursor-pointer"
      >
        <Plus class="w-4 h-4 stroke-[3]" />
        <span>{{ languageStore.t('add_new_item', 'Add New Item') }}</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Download, FolderTree, Plus } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

defineProps<{ title: string; subtitle: string }>()
defineEmits(['add-item', 'export', 'manage-categories'])
</script>
