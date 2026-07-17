<template>
  <div class="luxury-search-container">
    <!-- Premium Search Bar -->
    <div class="relative">
      <!-- Search Input Container -->
      <div class="relative bg-white rounded-3xl shadow-2xl border-2 border-gray-100 hover:border-amber-200 focus-within:border-amber-400 transition-all duration-300 overflow-hidden">
        <!-- Gold Search Icon -->
        <svg
          class="absolute left-6 top-1/2 -translate-y-1/2 w-6 h-6 text-amber-500 pointer-events-none"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          ></path>
        </svg>

        <!-- Search Input -->
        <input
          v-model="searchQuery"
          @input="handleSearch"
          @focus="showSuggestions = true"
          @blur="handleBlur"
          type="text"
          :placeholder="placeholder"
          class="w-full pl-16 pr-14 py-5 md:py-6 bg-white outline-none text-gray-800 placeholder-gray-400 text-base md:text-lg font-light"
        />

        <!-- Clear Button -->
        <Transition name="fade">
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute right-6 top-1/2 -translate-y-1/2 text-gray-400 hover:text-amber-500 transition-colors p-2"
            title="Clear search"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2.5"
                d="M6 18L18 6M6 6l12 12"
              ></path>
            </svg>
          </button>
        </Transition>
      </div>

      <!-- Luxury Dropdown Suggestions -->
      <Transition name="scale-fade">
        <div
          v-if="showSuggestions && (suggestions.length > 0 || recentSearches.length > 0)"
          class="absolute top-full left-0 right-0 mt-4 bg-white rounded-3xl shadow-2xl border border-gray-100 z-50 overflow-hidden backdrop-blur-sm"
        >
          <!-- Recent Searches Section -->
          <div v-if="recentSearches.length > 0 && !searchQuery" class="border-b border-gray-100 py-4 px-6">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-widest mb-4 flex items-center gap-2">
              <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Recent Searches
            </p>
            <div class="max-h-40 overflow-y-auto">
              <button
                v-for="(search, idx) in recentSearches"
                :key="idx"
                @click="selectSuggestion(search)"
                class="w-full text-left px-4 py-3 hover:bg-gradient-to-r hover:from-amber-50 hover:to-amber-50 text-sm text-gray-700 transition-all duration-200 flex items-center gap-3 group rounded-xl"
              >
                <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ search }}</span>
              </button>
            </div>
          </div>

          <!-- Search Suggestions Section -->
          <div v-if="suggestions.length > 0" class="py-4">
            <p v-if="searchQuery" class="text-xs font-bold text-gray-600 uppercase tracking-widest px-6 pb-3 flex items-center gap-2">
              <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
              Suggestions
            </p>
            <div class="max-h-96 overflow-y-auto space-y-1 px-3">
              <button
                v-for="suggestion in suggestions"
                :key="suggestion.id"
                @click="selectSuggestion(suggestion.name)"
                class="w-full text-left px-4 py-4 hover:bg-gradient-to-r hover:from-amber-50 hover:to-amber-100 text-sm transition-all duration-200 flex items-center gap-4 group rounded-2xl border border-transparent hover:border-amber-200"
              >
                <!-- Food Thumbnail -->
                <div class="flex-shrink-0">
                  <img
                    :src="suggestion.image || '/images/placeholder.png'"
                    :alt="suggestion.name"
                    class="w-14 h-14 rounded-xl object-cover shadow-md group-hover:shadow-lg group-hover:scale-105 transition-all duration-300"
                  />
                </div>

                <!-- Food Details -->
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-gray-900 group-hover:text-amber-700 transition-colors truncate">{{ suggestion.name }}</p>
                  <p class="text-xs text-gray-600 group-hover:text-gray-700 mt-1">{{ suggestion.category }}</p>
                  <p class="text-xs text-amber-600 font-bold mt-1">{{ suggestion.price }}</p>
                </div>

                <!-- Arrow Icon -->
                <svg class="w-5 h-5 text-gray-400 group-hover:text-amber-500 group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- No Results State -->
          <div v-if="searchQuery && suggestions.length === 0" class="px-6 py-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-gray-600 font-semibold text-base">No items found</p>
            <p class="text-gray-500 text-sm mt-2">Try searching for: pizzas, salads, coffee, or drinks</p>
          </div>

          <!-- Suggestions Footer -->
          <div v-if="suggestions.length > 0" class="bg-gradient-to-r from-amber-50 to-transparent border-t border-amber-100 px-6 py-4">
            <p class="text-xs text-amber-700 text-center font-medium flex items-center justify-center gap-1">
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
              </svg>
              Click any item to select
            </p>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface MenuItem {
  id: string | number
  name: string
  category: string
  price: string
  image?: string
}

interface Props {
  placeholder?: string
  menuItems?: MenuItem[]
  debounceMs?: number
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Search delicious meals...',
  menuItems: () => [
    { id: '1', name: 'Margherita Pizza', category: 'Main Course', price: 'ETB 450', image: '/images/food/pizza.jpg' },
    { id: '2', name: 'Caesar Salad', category: 'Appetizers', price: 'ETB 280', image: '/images/food/pizza.jpg' },
    { id: '3', name: 'Grilled Salmon', category: 'Main Course', price: 'ETB 850', image: '/images/food/pizza.jpg' },
    { id: '4', name: 'Chocolate Cake', category: 'Desserts', price: 'ETB 350', image: '/images/food/dessert.jpg' },
    { id: '5', name: 'Espresso', category: 'Beverages', price: 'ETB 150', image: '/images/food/coffee.jpg' },
    { id: '6', name: 'Chicken Tikka', category: 'Main Course', price: 'ETB 620', image: '/images/food/pizza.jpg' },
    { id: '7', name: 'Pasta Carbonara', category: 'Main Course', price: 'ETB 550', image: '/images/food/pizza.jpg' },
    { id: '8', name: 'Cappuccino', category: 'Beverages', price: 'ETB 180', image: '/images/food/coffee.jpg' },
    { id: '9', name: 'Greek Salad', category: 'Appetizers', price: 'ETB 320', image: '/images/food/pizza.jpg' },
    { id: '10', name: 'Tiramisu', category: 'Desserts', price: 'ETB 320', image: '/images/food/dessert.jpg' }
  ],
  debounceMs: 300
})

const emit = defineEmits<{
  search: [query: string]
  'suggestion-selected': [suggestion: string]
}>()

// State
const searchQuery = ref('')
const showSuggestions = ref(false)
const recentSearches = ref<string[]>([])
let debounceTimer: ReturnType<typeof setTimeout>

// Computed
const suggestions = computed(() => {
  if (!searchQuery.value.trim()) return []

  const query = searchQuery.value.toLowerCase()
  return props.menuItems.filter(item =>
    item.name.toLowerCase().includes(query) ||
    item.category.toLowerCase().includes(query)
  ).slice(0, 10)
})

// Methods
const handleSearch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('search', searchQuery.value)
  }, props.debounceMs)
}

const handleBlur = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const selectSuggestion = (suggestion: string) => {
  searchQuery.value = suggestion
  showSuggestions.value = false

  // Add to recent searches
  if (!recentSearches.value.includes(suggestion)) {
    recentSearches.value.unshift(suggestion)
    if (recentSearches.value.length > 8) {
      recentSearches.value.pop()
    }
  }

  emit('suggestion-selected', suggestion)
  emit('search', suggestion)
}

const clearSearch = () => {
  searchQuery.value = ''
  showSuggestions.value = false
  emit('search', '')
}
</script>

<style scoped>
.luxury-search-container {
  position: relative;
  z-index: 30;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.scale-fade-enter-active,
.scale-fade-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scale-fade-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(-8px);
}

.scale-fade-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-8px);
}

/* Input styling */
input::placeholder {
  color: #d1d5db;
  font-weight: 300;
}

input:focus {
  box-shadow: inset 0 0 0 1px #fcd34d;
}

/* Scrollbar styling */
div::-webkit-scrollbar {
  width: 6px;
}

div::-webkit-scrollbar-track {
  background: transparent;
}

div::-webkit-scrollbar-thumb {
  background: #fbbf24;
  border-radius: 3px;
}

div::-webkit-scrollbar-thumb:hover {
  background: #f59e0b;
}

/* Focus visible for accessibility */
button:focus-visible {
  outline: 2px solid #fbbf24;
  outline-offset: 2px;
}

/* Smooth hover effects */
button {
  position: relative;
}

/* Image animation */
img {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Container styles */
.luxury-search-container {
  width: 100%;
}

/* Gold accent throughout */
:deep(.text-amber-500) {
  color: #f59e0b;
}

:deep(.text-amber-600) {
  color: #d97706;
}

/* Responsive padding */
@media (max-width: 768px) {
  input {
    padding-left: 2.5rem;
    padding-right: 2.5rem;
    padding-top: 1.125rem;
    padding-bottom: 1.125rem;
    font-size: 0.875rem;
  }

  svg {
    width: 1.25rem;
    height: 1.25rem;
  }
}
</style>
