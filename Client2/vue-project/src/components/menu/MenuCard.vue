<script setup lang="ts">
// MenuCard - Pure Presentation Component
// No mock data - all properties from MenuItem props
// MenuItem comes from store.menuItems array
import type { MenuItem } from '@/types/menu'

defineProps<{
  item: MenuItem // ← Always from store.menuItems
}>()

const emit = defineEmits<{
  (e: 'edit', item: MenuItem): void
  (e: 'delete', item: MenuItem): void
  (e: 'toggle', item: MenuItem): void
}>()
</script>

<template>
  <div
    class="group rounded-lg sm:rounded-xl md:rounded-2xl bg-white border border-slate-200 overflow-hidden shadow-sm hover:shadow-lg hover:border-purple-300 transition-all duration-300"
  >
    <!-- Image Container -->
    <div class="relative h-32 sm:h-40 md:h-48 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
      <!-- Image -->
      <img
        v-if="item.image_url"
        :src="item.image_url"
        :alt="item.name"
        class="w-full h-full object-cover"
      />

      <!-- Placeholder when no image -->
      <div v-else class="absolute inset-0 flex items-center justify-center">
        <v-icon size="40" sm:size="50" md:size="64" color="slate-300">mdi-silverware-fork-knife</v-icon>
      </div>

      <!-- Availability Badge -->
      <div class="absolute top-2 sm:top-3 right-2 sm:right-3 z-10">
        <div
          :class="[
            'inline-flex items-center gap-1 px-2 sm:px-3 py-1 sm:py-1.5 rounded-full font-bold text-xs',
            item.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
          ]"
        >
          <div
            :class="['w-1.5 sm:w-2 h-1.5 sm:h-2 rounded-full', item.is_available ? 'bg-green-600' : 'bg-red-600']"
          />
          <span class="hidden sm:inline">{{ item.is_available ? 'Available' : 'Unavailable' }}</span>
          <span class="sm:hidden">{{ item.is_available ? 'In' : 'Out' }}</span>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-3 sm:p-4 md:p-5 flex flex-col h-full">
      <!-- Category Badge -->
      <div class="mb-2 sm:mb-3">
        <span
          class="inline-flex items-center gap-0.5 sm:gap-1 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg bg-purple-50 text-purple-700 text-xs font-bold uppercase tracking-wide"
        >
          <v-icon size="10" sm:size="12">mdi-tag</v-icon>
          <span class="truncate">{{ item.category }}</span>
        </span>
      </div>
      <!-- Title -->
      <h3 class="text-xs sm:text-sm md:text-base font-bold text-slate-900 line-clamp-2 mb-1 sm:mb-2">{{ item.name }}</h3>
      <!-- Description -->
      <p class="text-xs text-slate-600 line-clamp-2 mb-2 sm:mb-3 flex-1">
        {{ item.description || 'No description provided' }}
      </p>

      <!-- Footer: Price & Actions -->
      <div class="pt-2 sm:pt-3 border-t border-slate-200 flex items-center justify-between gap-2">
        <!-- Price -->
        <div>
          <p class="text-lg sm:text-xl md:text-2xl font-black text-purple-600">${{ item.price.toFixed(2) }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-1 sm:gap-2">
          <!-- Toggle Availability -->
          <v-btn
            icon
            size="x-small"
            sm:size="small"
            variant="text"
            :color="item.is_available ? 'green' : 'slate'"
            @click="emit('toggle', item)"
            class="hover:bg-green-100 rounded-lg min-h-9 w-9 sm:min-h-10 sm:w-10"
            :title="item.is_available ? 'Mark as unavailable' : 'Mark as available'"
          >
            <v-icon size="16" sm:size="18">
              {{ item.is_available ? 'mdi-check-circle' : 'mdi-alert-circle' }}
            </v-icon>
          </v-btn>

          <!-- Edit -->
          <v-btn
            icon
            size="x-small"
            sm:size="small"
            variant="text"
            color="indigo"
            @click="emit('edit', item)"
            class="hover:bg-indigo-100 rounded-lg min-h-9 w-9 sm:min-h-10 sm:w-10"
            title="Edit item"
          >
            <v-icon size="16" sm:size="18">mdi-pencil</v-icon>
          </v-btn>

          <!-- Delete -->
          <v-btn
            icon
            size="x-small"
            sm:size="small"
            variant="text"
            color="red"
            @click="emit('delete', item)"
            class="hover:bg-red-100 rounded-lg min-h-9 w-9 sm:min-h-10 sm:w-10"
            title="Delete item"
          >
            <v-icon size="16" sm:size="18">mdi-trash-outline</v-icon>
          </v-btn>
        </div>
      </div>
    </div>
  </div>
</template>
