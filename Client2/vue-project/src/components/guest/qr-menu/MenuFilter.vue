<template>
  <div class="luxury-filter-container">
    <!-- Main Toolbar -->
    <div class="luxury-filter-toolbar bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 px-6 md:px-8 py-5 md:py-6 sticky top-32 z-40">
      <!-- Content Container - Horizontal Layout -->
      <div class="flex items-center gap-4 md:gap-6">
        <!-- Sort Dropdown -->
        <div class="relative group">
          <button
            @click="showSortDropdown = !showSortDropdown"
            class="flex items-center gap-3 px-5 py-3 border-2 border-gray-200 hover:border-amber-400 rounded-2xl transition-all duration-300 text-sm font-semibold text-gray-700 hover:bg-amber-50 group-hover:shadow-md"
          >
            <svg class="w-5 h-5 text-gray-600 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <span class="hidden sm:inline">{{ currentSort.label }}</span>
            <span class="sm:hidden">Sort</span>
            <svg class="w-4 h-4 text-gray-600 transition-transform duration-300" :class="{ 'rotate-180': showSortDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
          </button>

          <!-- Sort Dropdown Menu -->
          <Transition name="dropdown">
            <div
              v-if="showSortDropdown"
              class="absolute top-full left-0 mt-3 w-56 bg-white rounded-2xl shadow-2xl border border-amber-100 py-3 z-50 overflow-hidden"
            >
              <button
                v-for="option in sortOptions"
                :key="option.value"
                @click="selectSort(option)"
                :class="[
                  'w-full text-left px-5 py-3.5 text-sm font-medium transition-all duration-200',
                  currentSort.value === option.value
                    ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-white'
                    : 'text-gray-700 hover:bg-amber-50 hover:border-l-4 hover:border-amber-400 hover:pl-4'
                ]"
              >
                {{ option.label }}
              </button>
            </div>
          </Transition>
        </div>

        <!-- Divider -->
        <div class="hidden sm:block w-px h-8 bg-gradient-to-b from-transparent via-gray-300 to-transparent"></div>

        <!-- View Mode Toggle - Grid/List -->
        <div class="flex items-center gap-2 bg-gray-100/50 rounded-2xl p-1.5 backdrop-blur-sm">
          <button
            @click="viewMode = 'grid'"
            :class="[
              'p-3 rounded-xl transition-all duration-300 font-semibold',
              viewMode === 'grid'
                ? 'bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg hover:shadow-xl hover:from-amber-500 hover:to-amber-700'
                : 'text-gray-600 hover:text-amber-700 hover:bg-white/50'
            ]"
            title="Grid View"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"></path>
            </svg>
          </button>
          <button
            @click="viewMode = 'list'"
            :class="[
              'p-3 rounded-xl transition-all duration-300 font-semibold',
              viewMode === 'list'
                ? 'bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg hover:shadow-xl hover:from-amber-500 hover:to-amber-700'
                : 'text-gray-600 hover:text-amber-700 hover:bg-white/50'
            ]"
            title="List View"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm0 6a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z"></path>
            </svg>
          </button>
        </div>

        <!-- Divider -->
        <div class="hidden sm:block w-px h-8 bg-gradient-to-b from-transparent via-gray-300 to-transparent"></div>

        <!-- Active Filter Chips -->
        <div v-if="activeFilters.length > 0" class="flex items-center gap-2 overflow-x-auto pb-1">
          <div
            v-for="filter in activeFilters"
            :key="filter"
            class="flex items-center gap-2 bg-gradient-to-r from-amber-100 to-amber-50 text-amber-800 px-4 py-2.5 rounded-full text-xs font-semibold whitespace-nowrap shadow-sm hover:shadow-md hover:from-amber-200 hover:to-amber-100 transition-all duration-300"
          >
            <span>{{ filter }}</span>
            <button
              @click="removeFilter(filter)"
              class="hover:bg-amber-200/50 p-1 rounded-full transition-colors"
              title="Remove filter"
            >
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18.364 5.636l-12.728 12.728m12.728 0L5.636 5.636"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Flex Spacer -->
        <div class="flex-1"></div>

        <!-- Advanced Filters Button -->
        <button
          @click="showAdvancedFilters = !showAdvancedFilters"
          :class="[
            'ml-auto flex items-center gap-2.5 px-5 py-3 border-2 rounded-2xl transition-all duration-300 text-sm font-semibold group',
            showAdvancedFilters
              ? 'border-amber-500 bg-gradient-to-br from-amber-50 to-transparent text-amber-700 shadow-md'
              : 'border-gray-200 text-gray-700 hover:border-amber-400 hover:bg-amber-50 hover:text-amber-700'
          ]"
        >
          <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
          </svg>
          <span class="hidden sm:inline">More</span>
          <svg class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': showAdvancedFilters }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Advanced Filters Panel - Smooth Slide Down -->
    <Transition name="slide-down">
      <div
        v-if="showAdvancedFilters"
        class="mt-4 bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50 p-6 md:p-8 sticky top-48 z-39"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Rating Filter -->
          <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-5 border border-white/30">
            <label class="text-sm font-bold text-gray-900 block mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
              </svg>
              Rating
            </label>
            <div class="flex gap-2">
              <button
                v-for="rating in [5, 4, 3]"
                :key="rating"
                @click="toggleRating(rating)"
                :class="[
                  'flex-1 px-3 py-3 rounded-xl text-xs font-bold transition-all duration-300',
                  selectedRatings.includes(rating)
                    ? 'bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-lg hover:shadow-xl hover:from-amber-500 hover:to-amber-700'
                    : 'border-2 border-gray-200 text-gray-700 hover:border-amber-400 hover:bg-amber-50'
                ]"
              >
                {{ rating }}★
              </button>
            </div>
          </div>

          <!-- Calories Filter -->
          <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-5 border border-white/30">
            <label class="text-sm font-bold text-gray-900 block mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
              Calories
            </label>
            <input
              v-model.number="maxCalories"
              type="range"
              min="0"
              max="1000"
              step="50"
              class="w-full h-2.5 bg-gray-200 rounded-full appearance-none cursor-pointer accent-amber-500 hover:accent-amber-600 transition-all"
            />
            <p class="text-xs text-gray-600 mt-3 text-center font-semibold">Up to {{ maxCalories }} cal</p>
          </div>

          <!-- Prep Time Filter -->
          <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-5 border border-white/30">
            <label class="text-sm font-bold text-gray-900 block mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"></path>
              </svg>
              Prep Time
            </label>
            <select
              v-model="maxPrepTime"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 hover:border-amber-300 transition-colors font-medium bg-white"
            >
              <option value="">All times</option>
              <option value="5">Under 5 min</option>
              <option value="15">Under 15 min</option>
              <option value="30">Under 30 min</option>
              <option value="60">Under 1 hour</option>
            </select>
          </div>

          <!-- Price Range Filter -->
          <div class="bg-white/50 backdrop-blur-sm rounded-2xl p-5 border border-white/30">
            <label class="text-sm font-bold text-gray-900 block mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
              </svg>
              Price
            </label>
            <div class="flex gap-2">
              <input
                v-model.number="priceRange.min"
                type="number"
                placeholder="Min"
                class="flex-1 px-3 py-3 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 hover:border-amber-300 transition-colors"
                min="0"
              />
              <input
                v-model.number="priceRange.max"
                type="number"
                placeholder="Max"
                class="flex-1 px-3 py-3 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 hover:border-amber-300 transition-colors"
                min="0"
              />
            </div>
          </div>
        </div>

        <!-- Apply Button -->
        <button
          @click="applyAdvancedFilters"
          class="w-full mt-6 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white py-4 rounded-2xl font-bold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 flex items-center justify-center gap-2 group"
        >
          <svg class="w-5 h-5 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
          </svg>
          Apply Advanced Filters
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface SortOption {
  value: string
  label: string
}

interface Props {
  sortOptions?: SortOption[]
  initialViewMode?: 'grid' | 'list'
}

const props = withDefaults(defineProps<Props>(), {
  sortOptions: () => [
    { value: 'popular', label: 'Most Popular' },
    { value: 'price-low', label: 'Price: Low to High' },
    { value: 'price-high', label: 'Price: High to Low' },
    { value: 'rating', label: 'Highest Rated' },
    { value: 'newest', label: 'Newest' }
  ],
  initialViewMode: 'grid'
})

const emit = defineEmits<{
  'sort-changed': [value: string]
  'view-mode-changed': [mode: 'grid' | 'list']
  'filters-applied': [filters: any]
}>()

// State
const showSortDropdown = ref(false)
const showAdvancedFilters = ref(false)
const viewMode = ref(props.initialViewMode)
const activeFilters = ref<string[]>([])
const currentSort = ref(props.sortOptions[0])
const selectedRatings = ref<number[]>([])
const maxCalories = ref(500)
const maxPrepTime = ref('')
const priceRange = ref({ min: 0, max: 1000 })

// Methods
const selectSort = (option: SortOption) => {
  currentSort.value = option
  showSortDropdown.value = false
  emit('sort-changed', option.value)
}

const removeFilter = (filter: string) => {
  activeFilters.value = activeFilters.value.filter(f => f !== filter)
}

const toggleRating = (rating: number) => {
  const index = selectedRatings.value.indexOf(rating)
  if (index > -1) {
    selectedRatings.value.splice(index, 1)
  } else {
    selectedRatings.value.push(rating)
  }
}

const applyAdvancedFilters = () => {
  emit('filters-applied', {
    ratings: selectedRatings.value,
    maxCalories: maxCalories.value,
    maxPrepTime: maxPrepTime.value,
    priceRange: priceRange.value
  })

  const filterLabels = []
  if (selectedRatings.value.length > 0) {
    filterLabels.push(`${Math.min(...selectedRatings.value)}★+`)
  }
  if (maxCalories.value < 1000) {
    filterLabels.push(`${maxCalories.value}cal`)
  }
  if (maxPrepTime.value) {
    filterLabels.push(`${maxPrepTime.value}min`)
  }
  if (priceRange.value.min > 0 || priceRange.value.max < 1000) {
    filterLabels.push(`ETB ${priceRange.value.min}-${priceRange.value.max}`)
  }

  activeFilters.value = filterLabels
  showAdvancedFilters.value = false
}
</script>

<style scoped>
.luxury-filter-container {
  position: relative;
  z-index: 40;
}

/* Glass Morphism Effect */
.luxury-filter-toolbar {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.85) 0%, rgba(250, 250, 249, 0.8) 100%);
  -webkit-backdrop-filter: blur(16px);
  backdrop-filter: blur(16px);
}

/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-12px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}

/* Slide Down Animation for Advanced Filters */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 600px;
  overflow: hidden;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-16px);
  max-height: 0;
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-16px);
  max-height: 0;
}

/* Scrollbar styling for filter chips */
div::-webkit-scrollbar {
  height: 4px;
}

div::-webkit-scrollbar-track {
  background: transparent;
}

div::-webkit-scrollbar-thumb {
  background: #fbbf24;
  border-radius: 2px;
}

div::-webkit-scrollbar-thumb:hover {
  background: #f59e0b;
}

/* Range input styling */
input[type='range'] {
  -webkit-appearance: none;
  appearance: none;
  background: transparent;
}

input[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
  transition: all 0.3s ease;
}

input[type='range']::-webkit-slider-thumb:hover {
  box-shadow: 0 4px 16px rgba(251, 191, 36, 0.6);
  transform: scale(1.2);
}

input[type='range']::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  cursor: pointer;
  border: none;
  box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
  transition: all 0.3s ease;
}

input[type='range']::-moz-range-thumb:hover {
  box-shadow: 0 4px 16px rgba(251, 191, 36, 0.6);
  transform: scale(1.2);
}

/* Focus visible states */
button:focus-visible,
input:focus-visible,
select:focus-visible {
  outline: 2px solid #fbbf24;
  outline-offset: 2px;
}

/* Smooth button transitions */
button {
  position: relative;
  overflow: hidden;
}

/* Advanced filters panel glass effect */
.bg-white\/80 {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.85) 0%, rgba(250, 250, 249, 0.8) 100%);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .luxury-filter-toolbar {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .luxury-filter-toolbar > div {
    min-width: min-content;
  }
}
</style>
