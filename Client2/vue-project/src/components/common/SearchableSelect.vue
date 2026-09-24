<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { Search, ChevronDown, Check, X, Building2 } from 'lucide-vue-next'

export interface SelectOption {
  [key: string]: any
}

const props = withDefaults(
  defineProps<{
    modelValue: string | number | null | undefined
    options: SelectOption[]
    labelKey?: string
    valueKey?: string
    sublabelKey?: string
    placeholder?: string
    searchPlaceholder?: string
    emptyText?: string
    disabled?: boolean
    clearable?: boolean
    showHotelIcon?: boolean
    triggerClass?: string
    dropdownClass?: string
    formatOptionLabel?: (option: SelectOption) => string
  }>(),
  {
    labelKey: 'name',
    valueKey: 'id',
    sublabelKey: 'city',
    placeholder: 'Select an option...',
    searchPlaceholder: 'Search...',
    emptyText: 'No matching items found',
    disabled: false,
    clearable: false,
    showHotelIcon: false,
    triggerClass: '',
    dropdownClass: '',
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
  (e: 'change', option: SelectOption | null): void
}>()

const isOpen = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)

const containerRef = ref<HTMLElement | null>(null)
const triggerRef = ref<HTMLButtonElement | null>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)
const listRef = ref<HTMLElement | null>(null)
const openUpwards = ref(false)

const getOptionValue = (option: SelectOption): any => {
  if (!option) return ''
  if (typeof option !== 'object') return option
  return option[props.valueKey] !== undefined ? option[props.valueKey] : option.id ?? option.value
}

const getOptionLabel = (option: SelectOption): string => {
  if (!option) return ''
  if (typeof option !== 'object') return String(option)
  return option[props.labelKey] !== undefined
    ? String(option[props.labelKey])
    : option.name ?? option.label ?? option.title ?? ''
}

const getOptionSublabel = (option: SelectOption): string => {
  if (!option || typeof option !== 'object') return ''
  const sub = option[props.sublabelKey] ?? option.city ?? option.description ?? ''
  if (sub) return String(sub)
  if (props.showHotelIcon || option.country) {
    return option.country || 'Ethiopia'
  }
  return ''
}

const selectedOption = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return null
  }
  return props.options.find(opt => String(getOptionValue(opt)) === String(props.modelValue)) || null
})

const formatDisplayValue = computed(() => {
  if (!selectedOption.value) return ''
  if (props.formatOptionLabel) {
    return props.formatOptionLabel(selectedOption.value)
  }
  const label = getOptionLabel(selectedOption.value)
  const sub = getOptionSublabel(selectedOption.value)
  return sub ? `${label} (${sub})` : label
})

const filteredOptions = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return props.options

  const tokens = query.split(/\s+/).filter(Boolean)
  return props.options.filter(opt => {
    const label = getOptionLabel(opt).toLowerCase()
    const sublabel = getOptionSublabel(opt).toLowerCase()
    const extra = (opt.address || opt.slug || opt.country || '').toLowerCase()
    const fullText = `${label} ${sublabel} ${extra}`

    return tokens.every(token => fullText.includes(token))
  })
})

const isSelected = (option: SelectOption): boolean => {
  if (!selectedOption.value) return false
  return String(getOptionValue(option)) === String(getOptionValue(selectedOption.value))
}

const openDropdown = () => {
  if (props.disabled) return
  
  if (triggerRef.value) {
    const rect = triggerRef.value.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    const spaceAbove = rect.top
    openUpwards.value = spaceBelow < 260 && spaceAbove > spaceBelow
  }

  isOpen.value = true
  searchQuery.value = ''
  highlightedIndex.value = -1

  nextTick(() => {
    searchInputRef.value?.focus()
    if (selectedOption.value && listRef.value) {
      const selectedIndex = filteredOptions.value.findIndex(o => isSelected(o))
      if (selectedIndex >= 0) {
        highlightedIndex.value = selectedIndex
        const items = listRef.value.querySelectorAll('li[role="option"]')
        if (items[selectedIndex]) {
          (items[selectedIndex] as HTMLElement).scrollIntoView({ block: 'nearest' })
        }
      }
    }
  })
}

const closeDropdown = () => {
  isOpen.value = false
  searchQuery.value = ''
  highlightedIndex.value = -1
}

const toggleDropdown = () => {
  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

const selectOption = (option: SelectOption) => {
  const val = getOptionValue(option)
  emit('update:modelValue', val)
  emit('change', option)
  closeDropdown()
}

const clearSelection = () => {
  emit('update:modelValue', '')
  emit('change', null)
}

const navigateDown = () => {
  if (filteredOptions.value.length === 0) return
  if (highlightedIndex.value < filteredOptions.value.length - 1) {
    highlightedIndex.value++
  } else {
    highlightedIndex.value = 0
  }
  scrollToHighlighted()
}

const navigateUp = () => {
  if (filteredOptions.value.length === 0) return
  if (highlightedIndex.value > 0) {
    highlightedIndex.value--
  } else {
    highlightedIndex.value = filteredOptions.value.length - 1
  }
  scrollToHighlighted()
}

const selectHighlighted = () => {
  if (
    highlightedIndex.value >= 0 &&
    highlightedIndex.value < filteredOptions.value.length
  ) {
    selectOption(filteredOptions.value[highlightedIndex.value])
  } else if (filteredOptions.value.length === 1) {
    selectOption(filteredOptions.value[0])
  }
}

const scrollToHighlighted = () => {
  nextTick(() => {
    if (!listRef.value) return
    const items = listRef.value.querySelectorAll('li[role="option"]')
    if (items[highlightedIndex.value]) {
      (items[highlightedIndex.value] as HTMLElement).scrollIntoView({ block: 'nearest' })
    }
  })
}

const handleClickOutside = (event: MouseEvent) => {
  if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', handleClickOutside)
})

watch(searchQuery, () => {
  highlightedIndex.value = 0
})
</script>

<template>
  <div
    ref="containerRef"
    class="relative w-full select-none"
    :class="{ 'opacity-60 pointer-events-none': disabled }"
  >
    <button
      ref="triggerRef"
      type="button"
      :disabled="disabled"
      @click="toggleDropdown"
      @keydown.down.prevent="openDropdown"
      @keydown.space.prevent="toggleDropdown"
      class="w-full flex items-center justify-between gap-2 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-medium text-left transition hover:border-slate-300 dark:hover:border-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 cursor-pointer shadow-2xs"
      :class="[
        isOpen ? 'border-indigo-500 ring-2 ring-indigo-500/20' : '',
        triggerClass
      ]"
      :aria-expanded="isOpen"
      aria-haspopup="listbox"
    >
      <div class="flex items-center gap-2 min-w-0 flex-1">
        <Building2
          v-if="showHotelIcon"
          class="w-4 h-4 text-indigo-500 dark:text-indigo-400 flex-shrink-0"
        />
        <span
          v-if="selectedOption"
          class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100"
        >
          {{ formatDisplayValue }}
        </span>
        <span v-else class="text-xs text-slate-400 font-normal truncate">
          {{ placeholder }}
        </span>
      </div>

      <div class="flex items-center gap-1 flex-shrink-0">
        <button
          v-if="clearable && selectedOption && !disabled"
          type="button"
          @click.stop="clearSelection"
          class="p-0.5 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition cursor-pointer"
          title="Clear selection"
        >
          <X class="w-3.5 h-3.5" />
        </button>
        <ChevronDown
          class="w-4 h-4 text-slate-400 transition-transform duration-200"
          :class="{ 'rotate-180 text-indigo-500': isOpen }"
        />
      </div>
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute left-0 right-0 z-[100] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-black/40 overflow-hidden flex flex-col"
        :class="[
          openUpwards ? 'bottom-full mb-1.5' : 'top-full mt-1.5',
          dropdownClass
        ]"
      >
        <div class="p-2 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/60">
          <div class="relative flex items-center">
            <Search class="w-4 h-4 text-slate-400 absolute left-2.5 pointer-events-none" />
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              @keydown.down.prevent="navigateDown"
              @keydown.up.prevent="navigateUp"
              @keydown.enter.prevent="selectHighlighted"
              @keydown.esc.prevent="closeDropdown"
              class="w-full pl-8 pr-7 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute right-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded cursor-pointer"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
          <div
            v-if="filteredOptions.length > 0"
            class="flex items-center justify-between px-1 pt-1.5 text-[10px] text-slate-400 font-medium"
          >
            <span>{{ filteredOptions.length }} {{ filteredOptions.length === 1 ? 'hotel' : 'hotels' }}</span>
            <span v-if="searchQuery" class="text-indigo-500 font-semibold">Filtered results</span>
          </div>
        </div>

        <ul
          ref="listRef"
          role="listbox"
          class="max-h-56 overflow-y-auto p-1.5 space-y-0.5 scrollbar-thin"
        >
          <li
            v-for="(option, index) in filteredOptions"
            :key="getOptionValue(option)"
            role="option"
            :aria-selected="isSelected(option)"
            @click="selectOption(option)"
            @mouseenter="highlightedIndex = index"
            class="flex items-center justify-between px-2.5 py-2 rounded-xl text-xs cursor-pointer transition"
            :class="[
              isSelected(option)
                ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200/50 dark:border-indigo-800/50'
                : highlightedIndex === index
                ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white font-medium'
                : 'text-slate-700 dark:text-slate-300 font-medium'
            ]"
          >
            <div class="flex items-center gap-2 min-w-0 flex-1">
              <Building2 class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
              <div class="min-w-0 flex-1">
                <div class="truncate leading-tight">{{ getOptionLabel(option) }}</div>
                <div
                  v-if="getOptionSublabel(option)"
                  class="text-[10px] text-slate-400 dark:text-slate-500 truncate leading-tight mt-0.5"
                >
                  {{ getOptionSublabel(option) }}
                </div>
              </div>
            </div>

            <Check
              v-if="isSelected(option)"
              class="w-4 h-4 text-indigo-600 dark:text-indigo-400 flex-shrink-0 ml-2"
            />
          </li>

          <li
            v-if="filteredOptions.length === 0"
            class="py-6 px-4 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-1"
          >
            <Building2 class="w-6 h-6 text-slate-300 dark:text-slate-600 mb-1" />
            <p class="font-bold text-slate-600 dark:text-slate-400">{{ emptyText }}</p>
            <p v-if="searchQuery" class="text-[11px] text-slate-400">
              No matches for "<span class="text-indigo-500 font-semibold">{{ searchQuery }}</span>"
            </p>
          </li>
        </ul>
      </div>
    </Transition>
  </div>
</template>
