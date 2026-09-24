import { useLanguageStore } from '@/stores/language'
import { computed } from 'vue'

export function useI18n() {
  const languageStore = useLanguageStore()

  const currentLanguage = computed(() => languageStore.currentLanguage)
  const isAmharic = computed(() => languageStore.isAmharic)

  return {
    t: languageStore.t,
    currentLanguage,
    isAmharic,
    options: languageStore.options,
    currentOption: computed(() => languageStore.currentOption),
    setLanguage: languageStore.setLanguage,
    toggleLanguage: languageStore.toggleLanguage
  }
}
