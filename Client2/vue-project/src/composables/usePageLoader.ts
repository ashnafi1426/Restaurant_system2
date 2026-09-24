import { usePageLoaderStore } from '@/stores/pageLoaderStore'

export const usePageLoader = () => {
  const loaderStore = usePageLoaderStore()

  const showLoader = (text?: string) => {
    loaderStore.showLoader(text)
  }

  const hideLoader = () => {
    loaderStore.hideLoader()
  }

  const setProgress = (progress: number) => {
    loaderStore.updateProgress(progress)
  }

  const setLoadingText = (text: string) => {
    loaderStore.updateText(text)
  }

  const withLoader = async <T>(
    operation: () => Promise<T>,
    text?: string
  ): Promise<T> => {
    try {
      showLoader(text)
      const result = await operation()
      return result
    } finally {
      hideLoader()
    }
  }

  return {
    showLoader,
    hideLoader,
    setProgress,
    setLoadingText,
    withLoader,
  }
}
