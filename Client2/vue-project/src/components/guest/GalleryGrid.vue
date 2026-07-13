<script setup lang="ts">
import { computed, ref } from 'vue'
import GalleryCard from './GalleryCard.vue'
import GalleryLightbox from './GalleryLightbox.vue'

interface GalleryImage {
  id: number
  title: string
  category: string
  src: string
}

const props = defineProps<{
  category: string
  items: GalleryImage[]
}>()

const selectedImage = ref<GalleryImage | null>(null)

const filteredItems = computed(() => {
  if (props.category === 'All') {
    return props.items
  }
  return props.items.filter((item) => item.category === props.category)
})

function openImage(item: GalleryImage) {
  selectedImage.value = item
}

function closeImage() {
  selectedImage.value = null
}
</script>

<template>
  <section class="bg-slate-50 py-20">
    <div class="mx-auto max-w-7xl px-6">
      <!-- Empty State -->
      <div v-if="filteredItems.length === 0" class="py-24 text-center">
        <div class="text-7xl">📷</div>
        <h3 class="mt-6 text-3xl font-bold">No Images Found</h3>
        <p class="mt-3 text-slate-500">There are currently no images in this category.</p>
      </div>

      <!-- Gallery Grid -->
      <div v-else class="grid gap-8 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <GalleryCard
          v-for="item in filteredItems"
          :key="item.id"
          :item="item"
          @click="openImage(item)"
        />
      </div>
    </div>

    <!-- Lightbox -->
    <GalleryLightbox v-if="selectedImage" :image="selectedImage" @close="closeImage" />
  </section>
</template>
