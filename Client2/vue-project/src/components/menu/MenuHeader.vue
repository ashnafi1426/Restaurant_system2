<template>
  <div
    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 pb-5"
  >
    <div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ title }}</h1>
      <p class="text-sm text-slate-500 mt-1">{{ subtitle }}</p>
    </div>
    <div class="flex items-center gap-3 self-start md:self-auto">
      <button
        @click="$emit('export')"
        class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 bg-white hover:bg-slate-50 transition"
      >
        📥 Export Menu
      </button>
      <button
        @click="$emit('manage-categories')"
        class="inline-flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 bg-white hover:bg-slate-50 transition"
      >
        🗂️ Manage Categories
      </button>
      <button
        @click="$emit('add-item')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 hover:bg-teal-800 text-white rounded-lg text-sm font-medium transition shadow-sm"
      >
        ➕ Add New Item
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ title: string; subtitle: string }>()
defineEmits(['add-item', 'export', 'manage-categories'])
</script>
