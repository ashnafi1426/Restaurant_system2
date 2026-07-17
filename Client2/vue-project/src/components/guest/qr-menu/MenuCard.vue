<template>
  <div class="menu-card">
    <!-- Card Container -->
    <div class="card-container">
      <!-- Image Section -->
      <div class="image-section">
        <img
          :src="item.image"
          :alt="item.name"
          class="food-image"
        />
      </div>

      <!-- Content Section -->
      <div class="content-section">
        <!-- Food Name -->
        <h3 class="food-name">{{ item.name }}</h3>

        <!-- Description (2 lines) -->
        <p class="description">{{ item.description }}</p>

        <!-- Bottom Section: Price & Add Button -->
        <div class="bottom-section">
          <!-- Price - Large Gold -->
          <span class="price">{{ formatPrice(item.price) }}</span>

          <!-- Add Button - Gold Square (NOT full width) -->
          <button
            @click.stop="addToCart"
            :disabled="isAdding"
            class="add-button"
          >
            <svg v-if="!isAdding" class="button-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
            </svg>
            <svg v-else class="button-icon spinner" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20m0-20a9.978 9.978 0 00-9 18m18 0a9.978 9.978 0 00-9-18"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

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
  item: MenuItem
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'add-to-cart': [item: MenuItem, quantity: number]
  'toggle-favorite': [itemId: string | number, isFavorite: boolean]
}>()

// State
const isAdding = ref(false)

// Methods
const formatPrice = (price: number | string | undefined): string => {
  // Handle undefined, null, or non-numeric values
  if (price === undefined || price === null || price === '') {
    return 'ETB 0'
  }

  // Convert to number if it's a string
  const numPrice = typeof price === 'string' ? parseFloat(price) : price

  // Check if conversion was successful
  if (isNaN(numPrice)) {
    console.warn('Invalid price value:', price)
    return 'ETB 0'
  }

  return `ETB ${Math.round(numPrice)}`
}

const addToCart = () => {
  isAdding.value = true
  setTimeout(() => {
    isAdding.value = false
    emit('add-to-cart', props.item, 1)
  }, 600)
}
</script>

<style scoped>
/* Card Container */
.menu-card {
  height: 100%;
}

.card-container {
  background: white;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  height: 100%;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(251, 191, 36, 0.1);
}

.card-container:hover {
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  border-color: rgba(251, 191, 36, 0.3);
  transform: translateY(-12px);
}

/* Image Section */
.image-section {
  position: relative;
  width: 100%;
  height: 180px;
  overflow: hidden;
  border-radius: 20px 20px 0 0;
}

@media (min-width: 640px) {
  .image-section {
    height: 200px;
  }
}

@media (min-width: 1024px) {
  .image-section {
    height: 224px;
  }
}

.food-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-container:hover .food-image {
  transform: scale(1.1);
}

/* Content Section */
.content-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 14px;
  gap: 10px;
}

@media (min-width: 640px) {
  .content-section {
    padding: 16px;
    gap: 11px;
  }
}

@media (min-width: 1024px) {
  .content-section {
    padding: 20px;
    gap: 12px;
  }
}

/* Food Name */
.food-name {
  font-size: 15px;
  font-weight: 700;
  color: #111827;
  margin: 0;
  line-height: 1.4;
  transition: color 0.3s ease;
}

@media (min-width: 640px) {
  .food-name {
    font-size: 16px;
  }
}

@media (min-width: 1024px) {
  .food-name {
    font-size: 18px;
  }
}

.card-container:hover .food-name {
  color: #d97706;
}

/* Description (2 lines) */
.description {
  font-size: 12px;
  color: #6b7280;
  margin: 0;
  line-clamp: 2;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}

@media (min-width: 640px) {
  .description {
    font-size: 13px;
  }
}

@media (min-width: 1024px) {
  .description {
    font-size: 14px;
  }
}

/* Bottom Section */
.bottom-section {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 10px;
  padding-top: 10px;
  border-top: 1px solid #f3f4f6;
}

@media (min-width: 640px) {
  .bottom-section {
    gap: 11px;
    padding-top: 11px;
  }
}

@media (min-width: 1024px) {
  .bottom-section {
    gap: 12px;
    padding-top: 12px;
  }
}

/* Price */
.price {
  font-size: 20px;
  font-weight: 900;
  color: #d97706;
  letter-spacing: -0.5px;
}

@media (min-width: 640px) {
  .price {
    font-size: 24px;
  }
}

@media (min-width: 1024px) {
  .price {
    font-size: 28px;
  }
}

/* Add Button - Gold Square */
.add-button {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
  position: relative;
  overflow: hidden;
}

@media (min-width: 640px) {
  .add-button {
    width: 40px;
    height: 40px;
    border-radius: 14px;
  }
}

@media (min-width: 1024px) {
  .add-button {
    width: 44px;
    height: 44px;
    border-radius: 16px;
  }
}

.add-button:hover:not(:disabled) {
  transform: scale(1.1);
  box-shadow: 0 8px 24px rgba(251, 191, 36, 0.4);
  background: linear-gradient(135deg, #fcd34d 0%, #fbbf24 100%);
}

.add-button:active:not(:disabled) {
  transform: scale(0.95);
}

.add-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: scale(1);
}

.button-icon {
  width: 18px;
  height: 18px;
  stroke: currentColor;
}

@media (min-width: 640px) {
  .button-icon {
    width: 20px;
    height: 20px;
  }
}

@media (min-width: 1024px) {
  .button-icon {
    width: 24px;
    height: 24px;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.button-icon.spinner {
  animation: spin 1s linear infinite;
}
</style>
