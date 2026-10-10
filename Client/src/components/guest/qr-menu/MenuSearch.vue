<template>
  <div class="luxury-search-container font-sans">
    <div class="relative">
      <div
        class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-800 hover:border-[#c29353] focus-within:border-[#c29353] transition-all duration-300 overflow-hidden"
      >
        <Search
          class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[#c29353] pointer-events-none"
        />

        <input
          v-model="searchQuery"
          @input="handleSearch"
          @focus="showSuggestions = true"
          @blur="handleBlur"
          type="text"
          :placeholder="
            props.placeholder &&
            props.placeholder !== 'Search delicious meals, drinks, and desserts...'
              ? props.placeholder
              : languageStore.t(
                  'search_placeholder',
                  'Search delicious meals, drinks, and desserts...',
                )
          "
          class="w-full pl-11 pr-10 py-2.5 bg-transparent outline-none text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-xs font-medium"
        />

        <button
          v-if="searchQuery"
          @click="clearSearch"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#c29353] transition p-1 cursor-pointer"
          title="Clear search"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <Transition name="scale-fade">
        <div
          v-if="showSuggestions && (computedSuggestions.length > 0 || recentSearches.length > 0)"
          class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 z-50 overflow-hidden backdrop-blur-md"
        >
          <div
            v-if="computedSuggestions.length > 0"
            class="py-2 divide-y divide-slate-100 dark:divide-slate-800"
          >
            <button
              v-for="item in computedSuggestions"
              :key="item.id"
              @click="selectItem(item)"
              class="w-full text-left px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center justify-between group cursor-pointer"
            >
              <div>
                <p
                  class="text-xs font-black text-slate-900 dark:text-white group-hover:text-[#c29353] transition"
                >
                  {{ item.name }}
                </p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                  {{ item.description || item.category }}
                </p>
              </div>
              <span class="text-xs font-black text-[#c29353]">
                ETB
                {{
                  ((item.total_price != null ? item.total_price : item.price) || 0).toLocaleString()
                }}
              </span>
            </button>
          </div>

          <div v-else-if="recentSearches.length > 0 && !searchQuery" class="py-3 px-5">
            <p
              class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-1.5"
            >
              <Clock class="w-3.5 h-3.5 text-[#c29353]" />
              {{ languageStore.t('recent_searches', 'Recent Searches') }}
            </p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="(search, idx) in recentSearches"
                :key="idx"
                @click="selectSuggestion(search)"
                class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-[#c29353] hover:text-white transition cursor-pointer"
              >
                {{ search }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Search, X, Clock } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

interface Props {
  placeholder?: string
  menuItems?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Search delicious meals, drinks, and desserts...',
  menuItems: () => [],
})

const languageStore = useLanguageStore()

const searchQuery = ref('')
const showSuggestions = ref(false)
const recentSearches = ref<string[]>([
  'Ribeye Steak',
  'Pasta',
  'Pumpkin Soup',
  'Lava Cake',
  'Latte',
])

const computedSuggestions = computed(() => {
  if (!searchQuery.value.trim() || !props.menuItems) return []
  const query = searchQuery.value.toLowerCase()
  return props.menuItems
    .filter(
      (item) =>
        item.name.toLowerCase().includes(query) ||
        (item.description && item.description.toLowerCase().includes(query)) ||
        (item.category && item.category.toLowerCase().includes(query)),
    )
    .slice(0, 5)
})

const emit = defineEmits<{
  search: [query: string]
  'suggestion-selected': [item: any]
}>()

function handleSearch() {
  emit('search', searchQuery.value)
}

function selectSuggestion(query: string) {
  searchQuery.value = query
  emit('search', query)
  showSuggestions.value = false
}

function selectItem(item: any) {
  searchQuery.value = item.name
  emit('search', item.name)
  emit('suggestion-selected', item)
  showSuggestions.value = false
}

function clearSearch() {
  searchQuery.value = ''
  emit('search', '')
}

function handleBlur() {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}
</script>
