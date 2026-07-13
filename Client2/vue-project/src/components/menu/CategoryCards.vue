<template>
  <div
    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 sm:gap-3 md:gap-4"
  >
    <!-- Category Card -->
    <div
      v-for="cat in categories"
      :key="cat.id"
      class="bg-white rounded-lg sm:rounded-xl p-3 sm:p-4 md:p-5 border transition hover:shadow-md cursor-pointer flex flex-col justify-between"
      :class="[
        'border-2',
        selectedCategory === cat.id
          ? 'border-purple-500 ring-1 ring-purple-500 bg-purple-50'
          : 'border-slate-200 hover:border-purple-300',
      ]"
      @click="$emit('select', cat.id)"
      role="button"
      tabindex="0"
    >
      <!-- Header: Icon & Label -->
      <div class="flex items-center justify-between gap-2">
        <span
          class="text-xs font-bold text-slate-500 tracking-wider uppercase truncate flex-1 line-clamp-2"
        >
          {{ cat.label }}
        </span>
        <div
          class="ml-1 w-6 sm:w-7 md:w-8 h-6 sm:h-7 md:h-8 rounded-lg flex items-center justify-center flex-shrink-0"
          :class="getCategoryBgColor(cat.id)"
        >
          <v-icon :size="14" sm:size="16" md:size="18" :color="getCategoryIconColor(cat.id)">
            {{ getCategoryIcon(cat.id) }}
          </v-icon>
        </div>
      </div>

      <!-- Item Count -->
      <div class="mt-3 sm:mt-4">
        <span class="text-xl sm:text-2xl font-extrabold text-slate-800">{{ cat.count }}</span>
        <span class="text-xs text-slate-500 ml-1">Items</span>
      </div>
    </div>

    <!-- Add New Category Card (Optional) -->
    <div
      class="bg-white rounded-lg sm:rounded-xl p-3 sm:p-4 md:p-5 border-2 border-dashed border-slate-300 hover:border-purple-300 transition cursor-pointer flex flex-col justify-between hover:bg-purple-50 group"
      role="button"
      tabindex="0"
    >
      <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-bold text-slate-500 tracking-wider uppercase">New</span>
        <v-icon size="18" sm:size="20" color="slate" class="group-hover:text-purple-600 transition">
          mdi-plus-circle
        </v-icon>
      </div>
      <div class="mt-3 sm:mt-4">
        <span
          class="text-xl sm:text-2xl font-extrabold text-slate-400 group-hover:text-purple-600 transition"
          >+</span
        >
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CATEGORY_TO_ICON, CATEGORY_COLORS } from '@/utils/menuIcons'
import type { MenuItem } from '@/types/menu'

interface Category {
  id: string
  label: string
  icon: string
  count: number
  color: string
}

defineProps<{
  categories: Category[]
  selectedCategory?: string
  loading?: boolean
}>()

defineEmits(['select', 'add-category'])

/**
 * Get background color class for category icon
 */
function getCategoryBgColor(categoryId: string): string {
  const colors: Record<string, string> = {
    breakfast: 'bg-amber-100',
    lunch: 'bg-emerald-100',
    dinner: 'bg-violet-100',
    drinks: 'bg-sky-100',
    dessert: 'bg-pink-100',
  }
  return colors[categoryId] || 'bg-slate-100'
}

/**
 * Get icon color class for category
 */
function getCategoryIconColor(categoryId: string): string {
  const colors: Record<string, string> = {
    breakfast: 'amber',
    lunch: 'emerald',
    dinner: 'violet',
    drinks: 'sky',
    dessert: 'pink',
  }
  return colors[categoryId] || 'slate'
}

/**
 * Get Material Design icon for category
 */
function getCategoryIcon(categoryId: string): string {
  return CATEGORY_TO_ICON[categoryId.toLowerCase()] || 'mdi-silverware-fork-knife'
}
</script>
