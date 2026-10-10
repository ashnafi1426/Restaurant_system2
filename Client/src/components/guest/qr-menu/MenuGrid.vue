<template>
  <div class="menu-grid-wrapper">
    <div v-if="isLoading" class="menu-grid">
      <div v-for="n in itemsPerPage" :key="`skeleton-${n}`" class="skeleton-card">
        <div class="skeleton-image"></div>

        <div class="skeleton-content">
          <div class="skeleton-line skeleton-title"></div>
          <div class="skeleton-line skeleton-desc-1"></div>
          <div class="skeleton-line skeleton-desc-2"></div>
          <div class="skeleton-line skeleton-price"></div>
        </div>
      </div>
    </div>

    <div v-else-if="displayedItems.length === 0" class="empty-state">
      <div class="empty-illustration">
        <svg
          width="120"
          height="120"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
        >
          <path
            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          ></path>
        </svg>
      </div>
      <h3 class="empty-title">{{ languageStore.t('no_items_found', 'No items found') }}</h3>
      <p class="empty-message">
        {{
          languageStore.t(
            'adjust_filters',
            "Try adjusting your filters or search query to find what you're looking for",
          )
        }}
      </p>
      <button @click="clearFilters" class="empty-button">
        <svg class="button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          ></path>
        </svg>
        {{ languageStore.t('clear_filters', 'Clear Filters') }}
      </button>
    </div>

    <div v-else class="menu-grid">
      <QRMenuItemCard
        v-for="item in displayedItems"
        :key="item.id"
        :item="item"
        :guest-name="guestName"
        :guest-email="guestEmail"
        :order-id="orderId"
        @add-to-cart="(qty) => handleAddToCart(item, qty)"
        @write-review="handleWriteReview(item)"
        @view-reviews="handleViewReviews(item)"
      />
    </div>

    <div v-if="!isLoading && totalPages > 1" class="pagination-wrapper">
      <button
        @click="previousPage"
        :disabled="currentPage === 1"
        class="pagination-button pagination-nav"
      >
        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M15 19l-7-7 7-7"
          ></path>
        </svg>
      </button>

      <div class="pagination-numbers">
        <button
          v-for="page in pageNumbers"
          :key="page"
          @click="page !== '...' && goToPage(Number(page))"
          :class="[
            'pagination-page',
            page === '...' ? 'pagination-dots' : '',
            currentPage === page ? 'pagination-active' : '',
          ]"
          :disabled="page === '...'"
        >
          {{ page }}
        </button>
      </div>

      <button
        @click="nextPage"
        :disabled="currentPage === totalPages"
        class="pagination-button pagination-nav"
      >
        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M9 5l7 7-7 7"
          ></path>
        </svg>
      </button>
    </div>

    <div v-if="!isLoading && items.length > 0" class="results-info">
      {{
        languageStore.currentLanguage === 'am'
          ? `ከ ${items.length} ዕቃዎች ${startItem}-${endItem} በማሳየት ላይ`
          : `Showing ${startItem}-${endItem} of ${items.length} items`
      }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import QRMenuItemCard from './QRMenuItemCard.vue'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

import type { MenuItem } from '@/types/menu'

interface Props {
  items: MenuItem[]
  viewMode?: 'grid' | 'list'
  isLoading?: boolean
  itemsPerPage?: number
  gridId?: string
  guestName?: string
  guestEmail?: string
  orderId?: string
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'grid',
  isLoading: false,
  itemsPerPage: 12,
  gridId: 'menu-grid',
  guestName: 'Guest',
  guestEmail: '',
  orderId: '',
})

const emit = defineEmits<{
  'add-to-cart': [item: MenuItem, quantity: number]
  'toggle-favorite': [itemId: string | number, isFavorite: boolean]
  'clear-filters': []
  'write-review': [item: MenuItem]
  'view-reviews': [item: MenuItem]
}>()

const currentPage = ref(1)

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

    for (
      let i = Math.max(2, currentPage.value - 1);
      i <= Math.min(totalPages.value - 1, currentPage.value + 1);
      i++
    ) {
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

const handleAddToCart = (item: MenuItem, quantity: number) => {
  emit('add-to-cart', item, quantity)
}

const handleWriteReview = (item: MenuItem) => {
  emit('write-review', item)
}

const handleViewReviews = (item: MenuItem) => {
  emit('view-reviews', item)
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
.menu-grid-wrapper {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.menu-grid {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(4, 1fr);
  width: 100%;
}

@media (max-width: 1280px) {
  .menu-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }
}

@media (max-width: 1024px) {
  .menu-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
  }
}

@media (max-width: 768px) {
  .menu-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
}

@media (max-width: 580px) {
  .menu-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }
}

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
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.9;
  }
}

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

.results-info {
  text-align: center;
  font-size: 14px;
  color: #9ca3af;
  padding: 12px 0;
}

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
