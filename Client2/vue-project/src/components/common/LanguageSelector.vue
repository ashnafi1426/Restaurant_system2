<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useLanguageStore, type LanguageCode } from '@/stores/language'
import { ChevronDown, Check, Globe } from 'lucide-vue-next'

interface Props {
  variant?: 'pill' | 'compact' | 'minimal' | 'header'
  showLabel?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'pill',
  showLabel: true,
})

const languageStore = useLanguageStore()
const isOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function selectLanguage(code: LanguageCode) {
  languageStore.setLanguage(code)
  isOpen.value = false
}

function handleClickOutside(event: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div ref="dropdownRef" class="relative inline-block text-left shrink-0">
    <!-- Trigger Button -->
    <button
      @click.stop="toggleDropdown"
      type="button"
      :class="[
        'flex items-center gap-2 transition-all duration-200 cursor-pointer select-none whitespace-nowrap focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E9A11A] focus-visible:ring-offset-2',
        variant === 'header'
          ? 'h-10 px-3.5 rounded-full border border-slate-200 dark:border-slate-700 bg-transparent hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-200 hover:text-[#0B1B35] dark:hover:text-white text-[12px] font-semibold'
          : variant === 'pill'
          ? 'h-10 px-3.5 rounded-full text-xs font-medium border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 hover:border-[#E9A11A]'
          : variant === 'compact'
          ? 'h-9 px-2.5 rounded-full text-xs font-medium border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-[#E9A11A] text-slate-700 dark:text-slate-200'
          : 'h-10 p-2 rounded-full border border-transparent text-slate-600 dark:text-slate-300 hover:text-[#E9A11A]'
      ]"
      :title="`Current language: ${languageStore.currentOption.nativeName}. Click to change.`"
      aria-haspopup="true"
      :aria-expanded="isOpen"
    >
      <Globe class="w-4 h-4 text-[#E9A11A] shrink-0" />
      <span v-if="showLabel" class="text-[12px] font-semibold tracking-wider uppercase">
        {{ languageStore.currentLanguage === 'am' ? 'አማ' : 'EN' }}
      </span>
      <ChevronDown
        class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-200 shrink-0"
        :class="{ 'rotate-180': isOpen }"
      />
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0 -translate-y-1"
      enter-to-class="transform scale-100 opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100 translate-y-0"
      leave-to-class="transform scale-95 opacity-0 -translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-44 rounded-2xl bg-white dark:bg-[#0B1B35] border border-slate-200 dark:border-slate-700 shadow-xl py-1.5 z-50 overflow-hidden font-sans"
      >
        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 flex items-center gap-1.5">
          <Globe class="w-3 h-3 text-[#E9A11A]" />
          <span>{{ languageStore.t('language', 'Language') }}</span>
        </div>

        <div class="p-1 space-y-0.5">
          <button
            v-for="opt in languageStore.options"
            :key="opt.code"
            @click.stop="selectLanguage(opt.code)"
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors cursor-pointer"
            :class="[
              languageStore.currentLanguage === opt.code
                ? 'bg-[#E9A11A]/10 text-[#E9A11A] font-semibold'
                : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60'
            ]"
          >
            <div class="flex items-center gap-2.5">
              <span class="w-6 h-5 rounded flex items-center justify-center text-[11px] bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold">
                {{ opt.code === 'am' ? 'አማ' : 'EN' }}
              </span>
              <div class="flex flex-col text-left leading-tight">
                <span class="text-xs font-semibold">{{ opt.nativeName }}</span>
                <span class="text-[10px] text-slate-400 font-normal">{{ opt.name }}</span>
              </div>
            </div>

            <Check
              v-if="languageStore.currentLanguage === opt.code"
              class="w-4 h-4 text-[#E9A11A] shrink-0"
            />
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>