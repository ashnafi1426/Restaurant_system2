<script setup lang="ts">
import { computed } from 'vue'
import { Image, Bed, Zap, UtensilsCrossed, Trees } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const languageStore = useLanguageStore()

const categories = computed(() => [
  {
    key: 'All',
    name: languageStore.t('all', 'All'),
    icon: Image,
    description: languageStore.t('view_all', 'View All'),
  },
  {
    key: 'Rooms',
    name: languageStore.t('rooms', 'Rooms'),
    icon: Bed,
    description: languageStore.t('luxury_rooms', 'Luxury Rooms'),
  },
  {
    key: 'Facilities',
    name: languageStore.t('facilities', 'Facilities'),
    icon: Zap,
    description: languageStore.t('all_facilities', 'All Facilities'),
  },
  {
    key: 'Restaurant',
    name: languageStore.t('restaurant', 'Restaurant'),
    icon: UtensilsCrossed,
    description: languageStore.t('fine_dining', 'Fine Dining'),
  },
  {
    key: 'Outdoor',
    name: languageStore.t('outdoor', 'Outdoor'),
    icon: Trees,
    description: languageStore.t('outdoor_spaces', 'Outdoor Spaces'),
  },
])
</script>

<template>
  <section
    class="relative py-20 bg-white dark:bg-slate-900 overflow-hidden transition-colors duration-300"
  >
    <div class="mx-auto max-w-7xl px-6">
      <!-- Heading -->
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-slate-900 dark:text-white">
          {{ languageStore.t('browse_by_category', 'Browse by Category') }}
        </h2>
        <p class="mt-4 text-lg text-slate-500 dark:text-slate-400">
          {{
            languageStore.t('browse_by_category_desc', 'Discover every part of our luxury hotel.')
          }}
        </p>
      </div>

      <!-- Categories Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
        <button
          v-for="category in categories"
          :key="category.key"
          @click="emit('update:modelValue', category.key)"
          class="group relative h-40 rounded-2xl overflow-hidden transition-all duration-300 transform hover:scale-105 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 cursor-pointer"
          :class="
            modelValue === category.key
              ? 'ring-4 ring-amber-500 border-amber-500 shadow-xl bg-amber-50 dark:bg-amber-950/30'
              : 'shadow-lg hover:shadow-xl hover:border-amber-300'
          "
        >
          <!-- Content -->
          <div class="absolute inset-0 flex flex-col items-center justify-center p-4 space-y-4">
            <!-- Icon from lucide-vue-next -->
            <div
              class="transition-all duration-300"
              :class="
                modelValue === category.key
                  ? 'text-amber-500 w-12 h-12'
                  : 'text-slate-600 dark:text-slate-300 w-10 h-10 group-hover:text-amber-500 group-hover:w-12 group-hover:h-12'
              "
            >
              <component :is="category.icon" class="w-full h-full" stroke-width="1.5" />
            </div>

            <!-- Category Name -->
            <h3
              class="text-lg font-bold transition-colors duration-300"
              :class="
                modelValue === category.key
                  ? 'text-amber-600 dark:text-amber-400'
                  : 'text-slate-700 dark:text-slate-200 group-hover:text-amber-600 dark:group-hover:text-amber-400'
              "
            >
              {{ category.name }}
            </h3>

            <!-- Description -->
            <p
              class="text-xs transition-colors duration-300"
              :class="
                modelValue === category.key
                  ? 'text-amber-500 dark:text-amber-400/80'
                  : 'text-slate-500 dark:text-slate-400 group-hover:text-amber-500'
              "
            >
              {{ category.description }}
            </p>

            <!-- Active Checkmark -->
            <div
              v-if="modelValue === category.key"
              class="mt-2 flex items-center justify-center w-6 h-6 rounded-full bg-amber-500 text-white text-xs font-bold animate-pulse"
            >
              ✓
            </div>
          </div>
        </button>
      </div>
    </div>
  </section>
</template>
