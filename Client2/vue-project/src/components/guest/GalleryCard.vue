<script setup lang="ts">
import { ref } from 'vue'
import { useLanguageStore } from '@/stores/language'

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

const languageStore = useLanguageStore()
const imageLoaded = ref(false)
</script>

<template>
  <article
    @click="$emit('click')"
    class="group cursor-pointer overflow-hidden rounded-3xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-lg transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl"
  >
    <!-- Image Container -->
    <div class="relative overflow-hidden bg-slate-100 dark:bg-slate-800 h-64 sm:h-72">
      <!-- Image -->
      <img
        :src="item.src"
        :alt="languageStore.t(item.title, item.title)"
        class="h-full w-full object-cover transition duration-700 group-hover:scale-110"
        @load="imageLoaded = true"
      />

      <!-- Overlay -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 transition duration-500 group-hover:opacity-100"
      />
    </div>

    <!-- Content -->
    <div class="p-6">
      <h3 class="text-xl font-bold text-slate-900 dark:text-white transition group-hover:text-amber-600 dark:group-hover:text-amber-400 line-clamp-1">
        {{ languageStore.t(item.title, item.title) }}
      </h3>

      <div class="mt-4 flex items-center justify-between">
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('luxury_hotel_gallery', 'Luxury Hotel Gallery') }}</span>

        <span class="font-semibold text-amber-600 dark:text-amber-400 transition group-hover:translate-x-2">
          {{ languageStore.t('view', 'View') }} →
        </span>
      </div>
    </div>
  </article>
</template>

