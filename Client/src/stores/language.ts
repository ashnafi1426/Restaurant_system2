import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { translations } from '@/translations'
import axios from 'axios'

export type LanguageCode = 'en' | 'am'

export interface LanguageOption {
  code: LanguageCode
  name: string
  nativeName: string
  flag: string
}

export const LANGUAGE_OPTIONS: LanguageOption[] = [
  { code: 'en', name: 'English', nativeName: 'English', flag: '🇬🇧' },
  { code: 'am', name: 'Amharic', nativeName: 'አማርኛ', flag: '🇪🇹' },
]

export const useLanguageStore = defineStore('language', () => {
  const initialLang = (localStorage.getItem('app_language') as LanguageCode) || 'en'
  const currentLanguage = ref<LanguageCode>(initialLang === 'am' ? 'am' : 'en')

  if (axios.defaults?.headers?.common) {
    axios.defaults.headers.common['X-App-Locale'] = currentLanguage.value
    axios.defaults.headers.common['Accept-Language'] = currentLanguage.value
  }

  const serverTranslations = ref<Record<string, Record<string, string>>>({})

  const isAmharic = computed(() => currentLanguage.value === 'am')
  const currentOption = computed(
    () => LANGUAGE_OPTIONS.find((opt) => opt.code === currentLanguage.value) || LANGUAGE_OPTIONS[0],
  )

  const fetchServerTranslations = async (lang: LanguageCode) => {
    try {
      const res = await axios.get(`/api/translations?lang=${lang}`)
      if (res.data?.translations) {
        serverTranslations.value[lang] = {
          ...(res.data.translations.all || {}),
          ...(res.data.translations.front || {}),
          ...(res.data.translations.back || {}),
          ...(res.data.translations.message || {}),
        }
      }
    } catch {
      // Gracefully fall back to local translations
    }
  }

  const setLanguage = async (lang: LanguageCode) => {
    if (lang !== 'en' && lang !== 'am') return
    currentLanguage.value = lang
    localStorage.setItem('app_language', lang)
    document.documentElement.lang = lang

    // Synchronize axios headers so backend sends messages in active language
    if (axios.defaults?.headers?.common) {
      axios.defaults.headers.common['X-App-Locale'] = lang
      axios.defaults.headers.common['Accept-Language'] = lang
    }

    // Dispatch global event for listeners
    window.dispatchEvent(new CustomEvent('app-language-changed', { detail: { language: lang } }))

    // Background fetch backend translations
    await fetchServerTranslations(lang)
  }

  // Pre-load on init
  fetchServerTranslations(currentLanguage.value)

  const toggleLanguage = () => {
    setLanguage(currentLanguage.value === 'en' ? 'am' : 'en')
  }

  const t = (key: string, fallback?: string): string => {
    const lang = currentLanguage.value

    // Check server translations first
    if (serverTranslations.value[lang]?.[key]) {
      return serverTranslations.value[lang][key]
    }

    // Check local dictionaries
    const dict = translations[lang] as Record<string, any>
    if (dict && dict[key] !== undefined) {
      return String(dict[key])
    }

    // Fallback to English dictionary
    const fallbackDict = translations.en as Record<string, any>
    if (fallbackDict && fallbackDict[key] !== undefined) {
      return String(fallbackDict[key])
    }

    return fallback !== undefined ? fallback : key
  }

  return {
    currentLanguage,
    isAmharic,
    currentOption,
    options: LANGUAGE_OPTIONS,
    setLanguage,
    toggleLanguage,
    t,
  }
})
