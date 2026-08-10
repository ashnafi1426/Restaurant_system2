import { usePageLoaderStore } from '@/stores/pageLoaderStore'

export const usePageLoader = () => {
  const loaderStore = usePageLoaderStore()

  /**
   * Show the global page loader
   * @param text - Custom loading text (optional)
   */
  const showLoader = (text?: string) => {
    loaderStore.showLoader(text)
  }

  /**
   * Hide the global page loader
   */
  const hideLoader = () => {
    loaderStore.hideLoader()
  }

  /**
   * Update the loading progress (0-100)
   * @param progress - Progress value between 0 and 100
   */
  const setProgress = (progress: number) => {
    loaderStore.updateProgress(progress)
  }

  /**
   * Update the loading text
   * @param text - New loading text
   */
  const setLoadingText = (text: string) => {
    loaderStore.updateText(text)
  }

  /**
   * Execute an async operation with loading state
   * @param operation - Async function to execute
   * @param text - Custom loading text (optional)
   */
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
