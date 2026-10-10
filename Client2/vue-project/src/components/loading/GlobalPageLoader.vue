<template>
  <Transition name="loader-fade">
    <div
      v-if="isLoading"
      class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white dark:bg-slate-950"
    >
      <div class="relative mb-8">
        <div class="absolute inset-0 flex items-center justify-center">
          <div
            class="w-48 h-48 rounded-full border-4 border-amber-100 dark:border-amber-900/30"
          ></div>
          <div
            class="absolute w-48 h-48 rounded-full border-4 border-t-amber-500 dark:border-t-amber-400 animate-spin"
          ></div>
        </div>

        <div class="relative flex items-center justify-center w-48 h-48">
          <img
            src="/images/Hotel logo.png"
            alt="Metropolitan Hotels Logo"
            class="w-32 h-32 object-contain animate-pulse drop-shadow-md"
          />
        </div>
      </div>

      <h1
        class="text-3xl font-black text-slate-900 dark:text-white tracking-wider mb-1 animate-fade-in uppercase"
      >
        Metropolitan Hotels
      </h1>
      <p
        class="text-sm font-bold tracking-widest text-amber-600 dark:text-amber-400 mb-6 animate-fade-in-delay uppercase"
      >
        Crafting Premier Stays
      </p>

      <div class="flex gap-1 mb-8 animate-fade-in-delay-2">
        <span v-for="i in 5" :key="i" class="text-amber-500 dark:text-amber-400 text-xl">★</span>
      </div>

      <div class="w-80 space-y-4">
        <div class="text-center">
          <p
            class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider animate-pulse"
          >
            {{ loadingText }}
          </p>
        </div>

        <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-red-600 to-red-800 dark:from-red-500 dark:to-red-700 rounded-full transition-all duration-300 ease-out"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>

      <div class="flex gap-2 mt-6">
        <span
          v-for="i in 3"
          :key="i"
          class="w-2 h-2 bg-red-600 dark:bg-red-500 rounded-full animate-bounce"
          :style="{ animationDelay: `${i * 0.15}s` }"
        ></span>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { usePageLoaderStore } from '@/stores/pageLoaderStore'

const loaderStore = usePageLoaderStore()
const isLoading = ref(loaderStore.isLoading)
const loadingText = ref('Loading...')
const progress = ref(0)

watch(
  () => loaderStore.isLoading,
  (newValue) => {
    isLoading.value = newValue
    if (newValue) {
      startLoadingAnimation()
    } else {
      progress.value = 100
    }
  },
)

watch(
  () => loaderStore.loadingText,
  (newValue) => {
    loadingText.value = newValue
  },
)

watch(
  () => loaderStore.progress,
  (newValue) => {
    progress.value = newValue
  },
)

const startLoadingAnimation = () => {
  progress.value = 0
  const interval = setInterval(() => {
    if (progress.value < 90 && isLoading.value) {
      progress.value += Math.random() * 10
    } else {
      clearInterval(interval)
    }
  }, 200)
}

onMounted(() => {
  if (isLoading.value) {
    startLoadingAnimation()
  }
})
</script>

<style scoped>
.loader-fade-enter-active,
.loader-fade-leave-active {
  transition: opacity 0.5s ease;
}

.loader-fade-enter-from,
.loader-fade-leave-to {
  opacity: 0;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.8s ease-out forwards;
  animation-delay: 0.3s;
  opacity: 0;
}

.animate-fade-in-delay {
  animation: fadeIn 0.8s ease-out forwards;
  animation-delay: 0.5s;
  opacity: 0;
}

.animate-fade-in-delay-2 {
  animation: fadeIn 0.8s ease-out forwards;
  animation-delay: 0.7s;
  opacity: 0;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(0.95);
  }
}

.animate-pulse {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes bounce {
  0%,
  80%,
  100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
}

.animate-bounce {
  animation: bounce 1.4s ease-in-out infinite;
}
</style>
