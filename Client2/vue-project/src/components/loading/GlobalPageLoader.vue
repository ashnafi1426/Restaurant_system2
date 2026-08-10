<template>
  <Transition name="loader-fade">
    <div 
      v-if="isLoading" 
      class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white dark:bg-slate-950"
    >
      <!-- Logo Container -->
      <div class="relative mb-8">
        <!-- Animated Ring -->
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="w-48 h-48 rounded-full border-4 border-red-100 dark:border-red-900/30"></div>
          <div class="absolute w-48 h-48 rounded-full border-4 border-t-red-600 dark:border-t-red-500 animate-spin"></div>
        </div>
        
        <!-- Logo -->
        <div class="relative flex items-center justify-center w-48 h-48">
          <img 
            src="/images/Hotel logo.png" 
            alt="Hotel Logo" 
            class="w-32 h-32 object-contain animate-pulse"
          />
        </div>
      </div>

      <!-- Hotel Name -->
      <h1 class="text-3xl font-bold text-red-800 dark:text-red-500 mb-2 animate-fade-in">
        LUXURY HOTEL
      </h1>
      <p class="text-xl text-gray-600 dark:text-gray-400 mb-6 animate-fade-in-delay">
        Experience Excellence
      </p>

      <!-- Stars Rating -->
      <div class="flex gap-1 mb-8 animate-fade-in-delay-2">
        <span v-for="i in 5" :key="i" class="text-red-600 dark:text-red-500 text-2xl">★</span>
      </div>

      <!-- Loading Text with Progress Bar -->
      <div class="w-80 space-y-4">
        <div class="text-center">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider animate-pulse">
            {{ loadingText }}
          </p>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-red-600 to-red-800 dark:from-red-500 dark:to-red-700 rounded-full transition-all duration-300 ease-out"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>

      <!-- Dots Animation -->
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

// Watch for changes in the store
watch(() => loaderStore.isLoading, (newValue) => {
  isLoading.value = newValue
  if (newValue) {
    startLoadingAnimation()
  } else {
    progress.value = 100
  }
})

watch(() => loaderStore.loadingText, (newValue) => {
  loadingText.value = newValue
})

watch(() => loaderStore.progress, (newValue) => {
  progress.value = newValue
})

// Simulate loading progress
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
/* Fade transition for loader */
.loader-fade-enter-active,
.loader-fade-leave-active {
  transition: opacity 0.5s ease;
}

.loader-fade-enter-from,
.loader-fade-leave-to {
  opacity: 0;
}

/* Animations */
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

/* Spin animation */
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

/* Pulse animation */
@keyframes pulse {
  0%, 100% {
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

/* Bounce animation for dots */
@keyframes bounce {
  0%, 80%, 100% {
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
