<template>
  <aside class="category-sidebar">
    <!-- Header -->
    <div class="sidebar-header">
      <h3 class="sidebar-title">Categories</h3>
      <p class="sidebar-subtitle">{{ displayCategories.length - 1 }} Items</p>
    </div>

    <!-- Categories List -->
    <nav class="categories-list">
      <button
        v-for="category in displayCategories"
        :key="category.id"
        @click="selectCategory(category.id)"
        :class="['category-item', selectedCategory === category.id && 'active']"
      >
        <!-- Left: Icon -->
        <span class="category-icon">
          <component :is="getIconComponent(category.id)" :size="20" :stroke-width="1.5" />
        </span>

        <!-- Middle: Name -->
        <span class="category-name">{{ category.name }}</span>

        <!-- Right: Count Badge -->
        <span v-if="category.count" class="category-count">{{ category.count }}</span>
      </button>
    </nav>
    <!-- Special Offer Section -->
    <div class="special-offer">
      <div class="offer-badge">SPECIAL OFFER</div>
      <div class="offer-discount">20% OFF</div>
      <p class="offer-text">On all orders above ETB 1000</p>
      <button class="offer-button">Order Now</button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import {
  Clock,
  Utensils,
  Leaf,
  Soup,
  Salad,
  UtensilsCrossed,
  Layers,
  Pizza,
  Sandwich,
  Cake,
  Wine,
  Menu
} from 'lucide-vue-next'

interface Category {
  id: string | null
  name: string
  icon: string
  count?: number
}

interface Props {
  categories?: Category[]
  selectedCategoryId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  categories: () => [
    { id: null, name: 'All Categories', icon: 'menu', count: 0 },
    { id: 'breakfast', name: 'Breakfast', icon: 'clock', count: 24 },
    { id: 'lunch', name: 'Lunch', icon: 'utensils', count: 18 },
    { id: 'appetizers', name: 'Appetizers', icon: 'leaf', count: 18 },
    { id: 'soups', name: 'Soups', icon: 'soup', count: 12 },
    { id: 'salads', name: 'Salads', icon: 'salad', count: 16 },
    { id: 'main-course', name: 'Main Course', icon: 'utensils-crossed', count: 36 },
    { id: 'pasta', name: 'Pasta', icon: 'layers', count: 14 },
    { id: 'pizza', name: 'Pizza', icon: 'pizza', count: 12 },
    { id: 'burgers', name: 'Burgers', icon: 'sandwich', count: 10 },
    { id: 'desserts', name: 'Desserts', icon: 'cake', count: 14 },
    { id: 'beverages', name: 'Beverages', icon: 'wine', count: 22 }
  ],
  selectedCategoryId: null
})

// Default categories for fallback in computed property
const DEFAULT_CATEGORIES: Category[] = [
  { id: null, name: 'All Categories', icon: 'menu', count: 0 },
  { id: 'breakfast', name: 'Breakfast', icon: 'clock', count: 24 },
  { id: 'lunch', name: 'Lunch', icon: 'utensils', count: 18 },
  { id: 'appetizers', name: 'Appetizers', icon: 'leaf', count: 18 },
  { id: 'soups', name: 'Soups', icon: 'soup', count: 12 },
  { id: 'salads', name: 'Salads', icon: 'salad', count: 16 },
  { id: 'main-course', name: 'Main Course', icon: 'utensils-crossed', count: 36 },
  { id: 'pasta', name: 'Pasta', icon: 'layers', count: 14 },
  { id: 'pizza', name: 'Pizza', icon: 'pizza', count: 12 },
  { id: 'burgers', name: 'Burgers', icon: 'sandwich', count: 10 },
  { id: 'desserts', name: 'Desserts', icon: 'cake', count: 14 },
  { id: 'beverages', name: 'Beverages', icon: 'wine', count: 22 }
]

// Icon mapping function
const getIconComponent = (categoryId: string | null) => {
  const iconMap: { [key: string]: any } = {
    'menu': Menu,
    'clock': Clock,
    'breakfast': Clock,
    'utensils': Utensils,
    'lunch': Utensils,
    'leaf': Leaf,
    'appetizers': Leaf,
    'soup': Soup,
    'soups': Soup,
    'salad': Salad,
    'salads': Salad,
    'utensils-crossed': UtensilsCrossed,
    'main-course': UtensilsCrossed,
    'layers': Layers,
    'pasta': Layers,
    'pizza': Pizza,
    'sandwich': Sandwich,
    'burger': Sandwich,
    'burgers': Sandwich,
    'cake': Cake,
    'desserts': Cake,
    'wine': Wine,
    'beverages': Wine,
    'drinks': Wine,
  }
  return iconMap[categoryId as string] || Menu
}

const emit = defineEmits<{
  (event: 'category-selected', categoryId: string | null): void
}>()

const selectedCategory = ref<string | null>(props.selectedCategoryId)

// Always display categories - use provided or defaults
const displayCategories = computed(() => {
  if (props.categories && props.categories.length > 0) {
    console.log('[CategorySidebar] Using provided categories:', props.categories)
    return props.categories
  }
  console.log('[CategorySidebar] Using default fallback categories')
  return DEFAULT_CATEGORIES
})

watch(() => props.selectedCategoryId, (newVal) => {
  selectedCategory.value = newVal
})

const selectCategory = (categoryId: string | null) => {
  selectedCategory.value = categoryId
  emit('category-selected', categoryId)
  console.log(`[CategorySidebar] Category selected: ${categoryId}`)
}
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| SIDEBAR BASE - Clean & Minimal
|--------------------------------------------------------------------------
*/

.category-sidebar {
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, #ffffff, #fafaf9);
  display: flex;
  flex-direction: column;
  gap: 0;
  padding: 0;
  overflow-y: auto;
  border-right: 1px solid #f0f0f0;
}

/*
|--------------------------------------------------------------------------
| SIDEBAR HEADER
|--------------------------------------------------------------------------
*/

.sidebar-header {
  padding: 24px 20px 16px;
  border-bottom: 1px solid #e5e5e5;
  background: white;
}

.sidebar-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.5px;
}

.sidebar-subtitle {
  margin: 4px 0 0 0;
  font-size: 12px;
  color: #9ca3af;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/*
|--------------------------------------------------------------------------
| CATEGORIES LIST - Vertical Stack
|--------------------------------------------------------------------------
*/

.categories-list {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0;
  overflow-y: auto;
  padding: 8px 0;
}

.categories-list::-webkit-scrollbar {
  width: 4px;
}

.categories-list::-webkit-scrollbar-track {
  background: transparent;
}

.categories-list::-webkit-scrollbar-thumb {
  background: #e5e5e5;
  border-radius: 2px;
}

.categories-list::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}

/*
|--------------------------------------------------------------------------
| CATEGORY ITEM - List Item Style
|--------------------------------------------------------------------------
*/

.category-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  width: 100%;
  border: none;
  background: transparent;
  color: #4b5563;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
  position: relative;
  text-align: left;
}

/* Hover State */
.category-item:hover {
  background: #fefce8;
  color: #1f2937;
  border-left-color: #fbbf24;
}

/* Active State - Gold Highlight */
.category-item.active {
  background: linear-gradient(to right, rgba(251, 191, 36, 0.1), transparent);
  color: #d97706;
  font-weight: 600;
  border-left-color: #fbbf24;
}

.category-item.active .category-icon {
  transform: scale(1.1);
}

.category-item.active .category-count {
  background: #fbbf24;
  color: white;
}

/* Focus State - Accessibility */
.category-item:focus-visible {
  outline: 2px solid #fbbf24;
  outline-offset: -2px;
}

/*
|--------------------------------------------------------------------------
| CATEGORY ICON - Lucide Icon Display
|--------------------------------------------------------------------------
*/

.category-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  transition: transform 0.2s ease;
  color: currentColor;
}

/*
|--------------------------------------------------------------------------
| CATEGORY NAME - Text
|--------------------------------------------------------------------------
*/

.category-name {
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/*
|--------------------------------------------------------------------------
| CATEGORY COUNT - Badge
|--------------------------------------------------------------------------
*/

.category-count {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 26px;
  height: 22px;
  padding: 0 6px;
  background: #f3f4f6;
  color: #6b7280;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.category-item:hover .category-count {
  background: #fef3c7;
  color: #92400e;
}

/*
|--------------------------------------------------------------------------
| SPECIAL OFFER SECTION
|--------------------------------------------------------------------------
*/

.special-offer {
  padding: 16px;
  margin: 12px;
  background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
  border-radius: 12px;
  text-align: center;
  color: white;
  border: 1px solid #4b5563;
}

.offer-badge {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #fbbf24;
  margin-bottom: 8px;
}

.offer-discount {
  font-size: 28px;
  font-weight: 700;
  color: #fbbf24;
  margin: 4px 0;
}

.offer-text {
  font-size: 12px;
  color: #d1d5db;
  margin: 8px 0;
}

.offer-button {
  width: 100%;
  padding: 10px;
  margin-top: 12px;
  background: #fbbf24;
  color: #1f2937;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.offer-button:hover {
  background: #f59e0b;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
}

.offer-button:active {
  transform: translateY(0);
}

/*
|--------------------------------------------------------------------------
| RESPONSIVE DESIGN
|--------------------------------------------------------------------------
*/

/* Tablet */
@media (max-width: 1024px) {
  .category-sidebar {
    border-right: 1px solid #e5e5e5;
  }

  .sidebar-header {
    padding: 20px 16px 12px;
  }

  .sidebar-title {
    font-size: 16px;
  }

  .category-item {
    padding: 10px 14px;
    font-size: 13px;
    gap: 10px;
  }

  .category-icon {
    width: 22px;
    height: 22px;
  }

  .category-count {
    font-size: 10px;
    min-width: 24px;
    height: 20px;
    padding: 0 5px;
  }
}

/* Mobile */
@media (max-width: 768px) {
  .category-sidebar {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid #e5e5e5;
    flex-direction: row;
    overflow-x: auto;
    overflow-y: hidden;
    height: auto;
  }

  .sidebar-header {
    display: none;
  }

  .categories-list {
    flex-direction: row;
    padding: 8px 12px;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 4px;
  }

  .categories-list::-webkit-scrollbar {
    height: 3px;
  }

  .categories-list::-webkit-scrollbar-thumb {
    background: #d1d5db;
  }

  .category-item {
    padding: 8px 12px;
    font-size: 12px;
    gap: 6px;
    white-space: nowrap;
    border-left: none;
    border-bottom: 2px solid transparent;
  }

  .category-item:hover {
    background: transparent;
    border-bottom-color: #fbbf24;
  }

  .category-item.active {
    background: transparent;
    border-bottom-color: #fbbf24;
    border-left: none;
  }

  .category-icon {
    width: 20px;
    height: 20px;
  }

  .category-count {
    font-size: 9px;
    min-width: 22px;
    height: 18px;
    padding: 0 4px;
  }

  .special-offer {
    display: none;
  }
}

/* Small Mobile */
@media (max-width: 480px) {
  .categories-list {
    padding: 6px 8px;
  }

  .category-item {
    padding: 6px 10px;
    font-size: 11px;
    gap: 4px;
  }

  .category-icon {
    width: 18px;
    height: 18px;
  }

  .category-count {
    font-size: 8px;
    min-width: 20px;
    height: 16px;
  }
}

/*
|--------------------------------------------------------------------------
| SCROLLBAR STYLING - Smooth & Clean
|--------------------------------------------------------------------------
*/

.category-sidebar {
  scrollbar-width: thin;
  scrollbar-color: #e5e5e5 transparent;
}

/*
|--------------------------------------------------------------------------
| UTILITY CLASSES
|--------------------------------------------------------------------------
*/

button {
  outline: none;
}

/* Hide scrollbar track on desktop */
@media (min-width: 769px) {
  .categories-list::-webkit-scrollbar-track {
    background: transparent;
  }
}
</style>
