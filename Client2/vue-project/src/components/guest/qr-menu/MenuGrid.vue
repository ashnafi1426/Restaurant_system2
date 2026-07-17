<template>
  <div class="menu-grid-wrapper">
    <!-- Loading State - Luxury Skeleton Cards -->
    <div v-if="isLoading" class="menu-grid">
      <div v-for="n in itemsPerPage" :key="`skeleton-${n}`" class="skeleton-card">
        <!-- Image Skeleton -->
        <div class="skeleton-image"></div>
        <!-- Content Skeleton -->
        <div class="skeleton-content">
          <div class="skeleton-line skeleton-title"></div>
          <div class="skeleton-line skeleton-desc-1"></div>
          <div class="skeleton-line skeleton-desc-2"></div>
          <div class="skeleton-line skeleton-price"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="displayedItems.length === 0" class="empty-state">
      <div class="empty-illustration">
        <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
      <h3 class="empty-title">No items found</h3>
      <p class="empty-message">Try adjusting your filters or search query to find what you're looking for</p>
      <button @click="clearFilters" class="empty-button">
        <svg class="button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        Clear Filters
      </button>
    </div>

    <!-- Menu Grid -->
    <div v-else class="menu-grid">
      <MenuCard
        v-for="item in displayedItems"
        :key="item.id"
        :item="item"
        @add-to-cart="handleAddToCart"
        @toggle-favorite="handleToggleFavorite"
      />
    </div>

    <!-- Pagination -->
    <div v-if="!isLoading && totalPages > 1" class="pagination-wrapper">
      <!-- Previous Button -->
      <button
        @click="previousPage"
        :disabled="currentPage === 1"
        class="pagination-button pagination-nav"
      >
        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>

      <!-- Page Numbers -->
      <div class="pagination-numbers">
        <button
          v-for="page in pageNumbers"
          :key="page"
          @click="page !== '...' && goToPage(Number(page))"
          :class="[
            'pagination-page',
            page === '...' ? 'pagination-dots' : '',
            currentPage === page ? 'pagination-active' : ''
          ]"
          :disabled="page === '...'"
        >
          {{ page }}
        </button>
      </div>

      <!-- Next Button -->
      <button
        @click="nextPage"
        :disabled="currentPage === totalPages"
        class="pagination-button pagination-nav"
      >
        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
        </svg>
      </button>
    </div>

    <!-- Results Info -->
    <div v-if="!isLoading && items.length > 0" class="results-info">
      Showing {{ startItem }}-{{ endItem }} of {{ items.length }} items
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import MenuCard from './MenuCard.vue'

interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  image: string
  category: string
  rating?: number
  badge?: string
  dietary?: string[]
  calories?: number
  preparationTime?: number
  is_available?: boolean
}

interface Props {
  items: MenuItem[]
  viewMode?: 'grid' | 'list'
  isLoading?: boolean
  itemsPerPage?: number
  gridId?: string
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'grid',
  isLoading: false,
  itemsPerPage: 12,
  gridId: 'menu-grid'
})

const emit = defineEmits<{
  'add-to-cart': [item: MenuItem, quantity: number]
  'toggle-favorite': [itemId: string | number, isFavorite: boolean]
  'clear-filters': []
}>()

// State
const currentPage = ref(1)

// Computed
const totalPages = computed(() => {
  return Math.ceil(props.items.length / props.itemsPerPage)
})

const displayedItems = computed(() => {
  const start = (currentPage.value - 1) * props.itemsPerPage
  const end = start + props.itemsPerPage
  return props.items.slice(start, end)
})

const startItem = computed(() => {
  return (currentPage.value - 1) * props.itemsPerPage + 1
})

const endItem = computed(() => {
  return Math.min(currentPage.value * props.itemsPerPage, props.items.length)
})

const pageNumbers = computed(() => {
  const pages: (number | string)[] = []
  const maxPagesToShow = 5

  if (totalPages.value <= maxPagesToShow) {
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)

    if (currentPage.value > 3) {
      pages.push('...')
    }

    for (let i = Math.max(2, currentPage.value - 1); i <= Math.min(totalPages.value - 1, currentPage.value + 1); i++) {
      if (!pages.includes(i)) {
        pages.push(i)
      }
    }

    if (currentPage.value < totalPages.value - 2) {
      pages.push('...')
    }

    pages.push(totalPages.value)
  }

  return pages
})

// Methods
const handleAddToCart = (item: MenuItem, quantity: number) => {
  emit('add-to-cart', item, quantity)
}

const handleToggleFavorite = (itemId: string | number, isFavorite: boolean) => {
  emit('toggle-favorite', itemId, isFavorite)
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    scrollToTop()
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    scrollToTop()
  }
}

const goToPage = (page: number) => {
  currentPage.value = page
  scrollToTop()
}

const scrollToTop = () => {
  const element = document.getElementById(props.gridId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const clearFilters = () => {
  currentPage.value = 1
  emit('clear-filters')
}
</script>

<style scoped>
/* Grid Wrapper */
.menu-grid-wrapper {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* Main Grid */
.menu-grid {
  display: grid;
  gap: 24px;
  grid-template-columns: repeat(4, 1fr);
  width: 100%;
}

/* Responsive Grid Layout */
@media (max-width: 1440px) {
  .menu-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 1200px) {
  .menu-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .menu-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }
}

@media (max-width: 640px) {
  .menu-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}

/* Skeleton Loading State */
.skeleton-card {
  background: white;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  height: 100%;
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.skeleton-image {
  width: 100%;
  height: 224px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}

.skeleton-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 20px;
  gap: 12px;
}

.skeleton-line {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
  border-radius: 8px;
}

.skeleton-title {
  height: 18px;
  width: 80%;
}

.skeleton-desc-1 {
  height: 14px;
  width: 100%;
}

.skeleton-desc-2 {
  height: 14px;
  width: 70%;
  margin-bottom: 8px;
}

.skeleton-price {
  height: 24px;
  width: 50%;
  margin-top: auto;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: calc(200% + 200px) 0;
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.9;
  }
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
  background: white;
  border-radius: 24px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.empty-illustration {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 120px;
  height: 120px;
  background: linear-gradient(135deg, #fbbf24/10 0%, #f59e0b/10 100%);
  border-radius: 50%;
  margin-bottom: 24px;
  color: #d97706;
}

.empty-illustration svg {
  width: 60px;
  height: 60px;
}

.empty-title {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 12px 0;
}

.empty-message {
  font-size: 16px;
  color: #6b7280;
  margin: 0 0 24px 0;
  max-width: 400px;
}

.empty-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}

.empty-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(251, 191, 36, 0.3);
}

.button-icon {
  width: 20px;
  height: 20px;
  stroke: currentColor;
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  padding: 24px;
  background: white;
  border-radius: 24px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.pagination-button {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  background: white;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  font-weight: 600;
}

.pagination-button:hover:not(:disabled) {
  border-color: #fbbf24;
  color: #fbbf24;
  background: #fef3c7;
}

.pagination-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.nav-icon {
  width: 20px;
  height: 20px;
}

.pagination-numbers {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pagination-page {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  background: white;
  color: #6b7280;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pagination-page:hover:not(.pagination-dots):not(:disabled) {
  border-color: #fbbf24;
  color: #fbbf24;
  background: #fef3c7;
}

.pagination-active {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: white;
  border-color: #f59e0b;
  box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
}

.pagination-dots {
  cursor: default;
  border: none;
  background: transparent;
  color: #9ca3af;
}

/* Results Info */
.results-info {
  text-align: center;
  font-size: 14px;
  color: #9ca3af;
  padding: 12px 0;
}

/* Mobile Pagination */
@media (max-width: 640px) {
  .pagination-wrapper {
    gap: 8px;
  }

  .pagination-page {
    width: 40px;
    height: 40px;
    font-size: 12px;
  }

  .pagination-button {
    width: 40px;
    height: 40px;
  }
}
</style>
