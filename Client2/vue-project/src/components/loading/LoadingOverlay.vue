<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isVisible"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
      >
        <div class="bg-white rounded-2xl p-8 shadow-2xl flex flex-col items-center gap-4">
          <!-- Animated loader -->
          <div class="relative w-16 h-16">
            <!-- Outer ring -->
            <div
              class="absolute inset-0 rounded-full border-4 border-slate-200"
            ></div>
            <!-- Inner spinning ring -->
            <div
              class="absolute inset-0 rounded-full border-4 border-transparent border-t-blue-600 border-r-blue-600 animate-spin"
            ></div>
          </div>

          <!-- Loading text -->
          <div class="text-center">
            <p class="text-slate-900 font-semibold">{{ title }}</p>
            <p class="text-slate-500 text-sm mt-1">{{ message }}</p>
          </div>

          <!-- Progress bar (optional) -->
          <div v-if="showProgress" class="w-32 h-1 bg-slate-200 rounded-full overflow-hidden mt-2">
            <div
              class="h-full bg-blue-600 rounded-full"
              :style="{ width: progress + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  isVisible: boolean
  title?: string
  message?: string
  showProgress?: boolean
  progress?: number
}

withDefaults(defineProps<Props>(), {
  title: 'Loading...',
  message: 'Please wait',
  showProgress: false,
  progress: 0,
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
