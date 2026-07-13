<script setup lang="ts">
import { ref } from 'vue'

interface GalleryImage {
  id: number
  title: string
  category: string
  src: string
}

defineProps<{
  item: GalleryImage
}>()

defineEmits<{
  (e: 'click'): void
}>()

const imageLoaded = ref(false)
</script>

<template>
  <article
    @click="$emit('click')"
    class="group cursor-pointer overflow-hidden rounded-3xl bg-white shadow-lg transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl"
  >
    <!-- Image Container -->
    <div class="relative overflow-hidden bg-slate-100 h-64 sm:h-72">
      <!-- Image -->
      <img
        :src="item.src"
        :alt="item.title"
        class="h-full w-full object-cover transition duration-700 group-hover:scale-110"
        @load="imageLoaded = true"
      />

      <!-- Overlay -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 transition duration-500 group-hover:opacity-100"
      />

      <!-- Category Badge -->
      <!-- <div
        class="absolute left-5 top-5 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-lg"
      >
        {{ item.category }}
      </div> -->
    </div>

    <!-- Content -->
    <div class="p-6">
      <h3 class="text-xl font-bold text-slate-900 transition group-hover:text-amber-600">
        {{ item.title }}
      </h3>

      <div class="mt-4 flex items-center justify-between">
        <span class="text-sm text-slate-500">Luxury Hotel Gallery</span>

        <span class="font-semibold text-amber-600 transition group-hover:translate-x-2">
          View →
        </span>
      </div>
    </div>
  </article>
</template>
