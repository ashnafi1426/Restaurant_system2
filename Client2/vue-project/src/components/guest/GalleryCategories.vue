<script setup lang="ts">
import { Image, Bed, Zap, UtensilsCrossed, Trees } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const categories = [
  {
    name: 'All',
    icon: Image,
    description: 'View All',
  },
  {
    name: 'Rooms',
    icon: Bed,
    description: 'Luxury Rooms',
  },
  {
    name: 'Facilities',
    icon: Zap,
    description: 'All Facilities',
  },
  {
    name: 'Restaurant',
    icon: UtensilsCrossed,
    description: 'Fine Dining',
  },
  {
    name: 'Outdoor',
    icon: Trees,
    description: 'Outdoor Spaces',
  },
]
</script>

<template>
  <section class="relative py-20 bg-white overflow-hidden">
    <div class="mx-auto max-w-7xl px-6">
      <!-- Heading -->
      <div class="text-center mb-16">
        <h2 class="text-4xl font-bold text-slate-900">Browse by Category</h2>
        <p class="mt-4 text-lg text-slate-500">Discover every part of our luxury hotel.</p>
      </div>

      <!-- Categories Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
        <button
          v-for="category in categories"
          :key="category.name"
          @click="emit('update:modelValue', category.name)"
          class="group relative h-40 rounded-2xl overflow-hidden transition-all duration-300 transform hover:scale-105 bg-white border-2 border-slate-200"
          :class="
            modelValue === category.name
              ? 'ring-4 ring-amber-500 border-amber-500 shadow-xl bg-amber-50'
              : 'shadow-lg hover:shadow-xl hover:border-amber-300'
          "
        >
          <!-- Content -->
          <div class="absolute inset-0 flex flex-col items-center justify-center p-4 space-y-4">
            <!-- Icon from lucide-vue-next -->
            <div
              class="transition-all duration-300"
              :class="
                modelValue === category.name
                  ? 'text-amber-500 w-12 h-12'
                  : 'text-slate-600 w-10 h-10 group-hover:text-amber-500 group-hover:w-12 group-hover:h-12'
              "
            >
              <component :is="category.icon" class="w-full h-full" stroke-width="1.5" />
            </div>

            <!-- Category Name -->
            <h3
              class="text-lg font-bold transition-colors duration-300"
              :class="
                modelValue === category.name
                  ? 'text-amber-600'
                  : 'text-slate-700 group-hover:text-amber-600'
              "
            >
              {{ category.name }}
            </h3>

            <!-- Description -->
            <p
              class="text-xs transition-colors duration-300"
              :class="
                modelValue === category.name
                  ? 'text-amber-500'
                  : 'text-slate-500 group-hover:text-amber-500'
              "
            >
              {{ category.description }}
            </p>

            <!-- Active Checkmark -->
            <div
              v-if="modelValue === category.name"
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
