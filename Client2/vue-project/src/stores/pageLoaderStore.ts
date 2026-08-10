import { defineStore } from 'pinia'
import { ref } from 'vue'

export const usePageLoaderStore = defineStore('pageLoader', () => {
  const isLoading = ref(false)
  const loadingText = ref('Loading...')
  const progress = ref(0)

  const showLoader = (text = 'Loading...') => {
    isLoading.value = true
    loadingText.value = text
    progress.value = 0
  }

  const hideLoader = () => {
    progress.value = 100
    setTimeout(() => {
      isLoading.value = false
      progress.value = 0
    }, 300) // Short delay to show 100% completion
  }

  const updateProgress = (value: number) => {
    progress.value = Math.min(100, Math.max(0, value))
  }

  const updateText = (text: string) => {
    loadingText.value = text
  }

  return {
    isLoading,
    loadingText,
    progress,
    showLoader,
    hideLoader,
    updateProgress,
    updateText,
  }
})
