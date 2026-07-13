<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'

interface GalleryImage {
  id: number
  title: string
  category: string
  src: string
}

defineProps<{
  image: GalleryImage
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

function close() {
  emit('close')
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    close()
  }
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300"
      leave-active-class="transition duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 p-6"
        @click.self="close"
      >
        <!-- Close Button -->

        <button
          @click="close"
          class="absolute right-8 top-8 flex h-12 w-12 items-center justify-center rounded-full bg-white text-2xl font-bold text-slate-900 shadow-xl transition hover:rotate-90 hover:bg-red-500 hover:text-white"
        >
          ×
        </button>

        <!-- Image Container -->
        <div class="mx-auto w-full max-w-6xl px-4 sm:px-6">
          <div class="bg-slate-100 rounded-2xl sm:rounded-3xl overflow-hidden">
            <img
              :src="image.src"
              :alt="image.title"
              class="mx-auto max-h-[70vh] sm:max-h-[80vh] w-auto"
              @error="
                (e) => {
                  e.target.src =
                    'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23e2e8f0%22 width=%22400%22 height=%22300%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%2364748b%22 font-family=%22Arial%22%3EImage Not Found%3C/text%3E%3C/svg%3E'
                }
              "
            />
          </div>

          <!-- Information -->

          <div class="mt-8 rounded-3xl bg-white p-8 shadow-2xl">
            <div class="flex flex-wrap items-center justify-between gap-6">
              <div>
                <span class="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white">
                  {{ image.category }}
                </span>

                <h2 class="mt-5 text-4xl font-bold text-slate-900">
                  {{ image.title }}
                </h2>

                <p class="mt-3 text-slate-500">Luxury Hotel Gallery Collection</p>
              </div>

              <button
                @click="close"
                class="rounded-xl bg-amber-500 px-8 py-4 font-semibold text-white transition hover:bg-amber-600"
              >
                Close Gallery
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
