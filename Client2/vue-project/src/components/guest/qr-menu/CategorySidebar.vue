<template>
  <aside class="category-sidebar w-full lg:w-72 bg-white rounded-3xl shadow-xl p-6 sticky top-32 h-fit">
    <div class="space-y-2">
      <button
        v-for="category in categories"
        :key="category.id"
        @click="selectCategory(category.id)"
        :class="[
          'category-button w-full',
          selectedCategoryId === category.id ? 'active-category' : ''
        ]"
      >
        <!-- Left - Icon & Name -->
        <span class="flex items-center gap-3 flex-1 min-w-0">
          <span class="icon-box flex-shrink-0">
            <component :is="getCategoryIcon(category.id)" class="w-4 h-4" />
          </span>
          <span class="text-sm font-medium truncate">{{ category.name }}</span>
        </span>
        <!-- Right - Count Badge -->
        <span v-if="category.count" class="count-badge flex-shrink-0">{{ category.count }}</span>
      </button>
    </div>
    <div class="h-px bg-gradient-to-r from-transparent via-amber-200 to-transparent mt-6"></div>
  </aside>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { 
  Menu, 
  Utensils, 
  Sun, 
  Soup, 
  Salad,
  Wine,
  Coffee,
  Pizza,
  Cake,
  Leaf,
  Flame
} from 'lucide-vue-next'

interface Category {
  id: string
  name: string
  icon: string
  count?: number
}

interface Props {
  categories?: Category[]
  selectedCategoryId?: string | null
  totalItems?: number
}

const props = withDefaults(defineProps<Props>(), {
  categories: () => [
    { id: 'breakfast', name: 'Breakfast', icon: 'breakfast', count: 12 },
    { id: 'soups', name: 'Soups', icon: 'soups', count: 6 },
    { id: 'appetizers', name: 'Appetizers', icon: 'appetizers', count: 8 },
    { id: 'main-course', name: 'Main Courses', icon: 'main-course', count: 25 },
    { id: 'sandwiches', name: 'Sandwiches', icon: 'sandwiches', count: 10 },
    { id: 'pasta', name: 'Pasta', icon: 'pasta', count: 9 },
    { id: 'dessert', name: 'Desserts', icon: 'dessert', count: 7 },
    { id: 'drinks', name: 'Beverages', icon: 'drinks', count: 15 }
  ],
  selectedCategoryId: null,
  totalItems: 92
})

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
  (event: 'category-selected', categoryId: string | null): void
}>()

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const selectedCategory = ref<string | null>(props.selectedCategoryId)

/*
|--------------------------------------------------------------------------
| Watchers
|--------------------------------------------------------------------------
*/

watch(() => props.selectedCategoryId, (newVal) => {
  selectedCategory.value = newVal
})

/*
|--------------------------------------------------------------------------
| Methods
|--------------------------------------------------------------------------
*/

const selectCategory = (categoryId: string | null) => {
  selectedCategory.value = categoryId
  emit('category-selected', categoryId)
}

// Map category IDs to Lucide icons - Clean & Professional
const getCategoryIcon = (categoryId: string) => {
  const iconMap: Record<string, any> = {
    'breakfast': Sun,
    'soups': Soup,
    'appetizers': Salad,
    'main-course': Utensils,
    'sandwiches': Leaf,
    'pasta': Flame,
    'dessert': Cake,
    'drinks': Wine,
    'beverages': Coffee,
    'pizza': Pizza,
    'salads': Salad,
    'grill': Flame,
    'seafood': Coffee,
    'sides': Leaf,
    'cake': Cake
  }
  return iconMap[categoryId] || Utensils
}
</script>

<style scoped>
/*
|--------------------------------------------------------------------------
| Sidebar Base - Professional & Clean
|--------------------------------------------------------------------------
*/

.category-sidebar {
  background: linear-gradient(145deg, #ffffff 0%, #fafaf9 100%);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  scrollbar-width: thin;
  scrollbar-color: #fbbf24 transparent;
  transition: none !important;
  transform: none !important;
  position: sticky;
  top: 32px;
  height: fit-content;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
  border: 1px solid rgba(251, 191, 36, 0.1);
}

/*
|--------------------------------------------------------------------------
| Custom Scrollbar - Premium Style
|--------------------------------------------------------------------------
*/

.category-sidebar::-webkit-scrollbar {
  width: 4px;
}

.category-sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.category-sidebar::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #fbbf24, #d97706);
  border-radius: 20px;
}

.category-sidebar::-webkit-scrollbar-thumb:hover {
  background: #b45309;
}

/*
|--------------------------------------------------------------------------
| Category Buttons - Clean & Minimal
|--------------------------------------------------------------------------
*/

.category-button {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem 1rem;
  border-radius: 0.75rem;
  background: transparent;
  border: 1px solid transparent;
  color: #4b5563;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
  transform: none !important;
  translate: none !important;
}

/*
|--------------------------------------------------------------------------
| Hover Effect - Clean & Subtle
|--------------------------------------------------------------------------
*/

.category-button:hover {
  background: #fffbeb;
  border-color: #fbbf24;
  color: #1f2937;
  transform: none !important;
  translate: none !important;
}

/*
|--------------------------------------------------------------------------
| Active Category - Premium Gold
|--------------------------------------------------------------------------
*/

.active-category {
  background: linear-gradient(135deg, #fbbf24, #d97706);
  color: white;
  border-color: #f59e0b;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
  transform: none !important;
  translate: none !important;
}

.active-category:hover {
  background: linear-gradient(135deg, #f59e0b, #b45309);
  transform: none !important;
  translate: none !important;
}

/*
|--------------------------------------------------------------------------
| Icon Box - Clean Background
|--------------------------------------------------------------------------
*/

.icon-box {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: rgba(251, 191, 36, 0.1);
  flex-shrink: 0;
  transition: all 0.2s ease;
  transform: none !important;
}

.category-button:hover .icon-box {
  background: rgba(251, 191, 36, 0.2);
}

.active-category .icon-box {
  background: rgba(255, 255, 255, 0.2);
}

/*
|--------------------------------------------------------------------------
| Icon Colors
|--------------------------------------------------------------------------
*/

.icon-box svg {
  color: #92400e;
  transition: color 0.2s ease;
}

.category-button:hover .icon-box svg {
  color: #d97706;
}

.active-category .icon-box svg {
  color: white;
}

/*
|--------------------------------------------------------------------------
| Count Badge - Minimal & Clean
|--------------------------------------------------------------------------
*/

.count-badge {
  min-width: 28px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 600;
  background: #f3f4f6;
  color: #6b7280;
  flex-shrink: 0;
  padding: 0 8px;
  transition: all 0.2s ease;
  transform: none !important;
}

.category-button:hover .count-badge {
  background: #fef3c7;
  color: #92400e;
}

.active-category .count-badge {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

/*
|--------------------------------------------------------------------------
| Shine Animation - Premium Touch
|--------------------------------------------------------------------------
*/

.category-button::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.15), transparent);
  transform: translateX(-100%);
  transition: transform 0.6s ease;
  pointer-events: none;
}

.category-button:hover::before {
  transform: translateX(100%);
}

.active-category::before {
  background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.1), transparent);
}

/*
|--------------------------------------------------------------------------
| Focus Styles - Accessibility
|--------------------------------------------------------------------------
*/

button {
  outline: none;
  transform: none !important;
}

button:focus-visible {
  outline: 2px solid #fbbf24;
  outline-offset: 2px;
}

/*
|--------------------------------------------------------------------------
| Stats Card - Clean & Professional
|--------------------------------------------------------------------------
*/

.category-sidebar .mt-6.p-4 {
  transition: box-shadow 0.25s ease;
  transform: none !important;
  background: linear-gradient(135deg, #fffbeb, rgba(251, 191, 36, 0.08));
  border: 1px solid rgba(251, 191, 36, 0.15);
}

.category-sidebar .mt-6.p-4:hover {
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.08);
  transform: none !important;
}

/*
|--------------------------------------------------------------------------
| Divider - Elegant Gold
|--------------------------------------------------------------------------
*/

.category-sidebar .h-px {
  background: linear-gradient(to right, transparent, rgba(251, 191, 36, 0.3), transparent);
  transform: none !important;
}

/*
|--------------------------------------------------------------------------
| Mobile Responsive - Enhanced Mobile First
|--------------------------------------------------------------------------
*/

@media (max-width: 1024px) {
  .category-sidebar {
    position: relative;
    top: 0;
    width: 100%;
    max-height: none;
    transform: none !important;
    padding: 12px;
    border-radius: 20px;
  }
}

@media (max-width: 768px) {
  .category-sidebar {
    padding: 12px;
    border-radius: 18px;
  }

  .category-button {
    padding: 10px 12px;
    font-size: 13px;
    border-radius: 10px;
  }
  
  .icon-box {
    width: 28px;
    height: 28px;
  }
  
  .icon-box svg {
    width: 14px;
    height: 14px;
  }
  
  .count-badge {
    min-width: 22px;
    height: 18px;
    font-size: 11px;
    padding: 0 6px;
  }
}

@media (max-width: 640px) {
  .category-sidebar {
    padding: 10px;
    border-radius: 16px;
    gap: 4px;
  }

  .category-button {
    padding: 8px 10px;
    font-size: 12px;
    border-radius: 8px;
  }
  
  .icon-box {
    width: 24px;
    height: 24px;
  }
  
  .icon-box svg {
    width: 12px;
    height: 12px;
  }
  
  .count-badge {
    min-width: 20px;
    height: 16px;
    font-size: 10px;
    padding: 0 4px;
  }
}

/*
|--------------------------------------------------------------------------
| Gold Glow Effect - Active Category
|--------------------------------------------------------------------------
*/

.active-category::after {
  content: "";
  position: absolute;
  right: -15px;
  top: -15px;
  width: 60px;
  height: 60px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2), transparent);
  border-radius: 50%;
  pointer-events: none;
  transform: none !important;
}

/*
|--------------------------------------------------------------------------
| Ensure NO Movement
|--------------------------------------------------------------------------
*/

.category-sidebar * {
  transform: none !important;
  translate: none !important;
}

/*
|--------------------------------------------------------------------------
| Text Utilities
|--------------------------------------------------------------------------
*/

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.min-w-0 {
  min-width: 0;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

/*
|--------------------------------------------------------------------------
| Typography - Professional
|--------------------------------------------------------------------------
*/

.text-amber-700 {
  color: #b45309;
}

.text-amber-800 {
  color: #92400e;
}

.text-amber-900 {
  color: #78350f;
}

.font-serif {
  font-family: Georgia, 'Times New Roman', serif;
}

/*
|--------------------------------------------------------------------------
| Stats Card Typography
|--------------------------------------------------------------------------
*/

.category-sidebar .mt-6.p-4 .text-xs {
  letter-spacing: 0.05em;
}

.category-sidebar .mt-6.p-4 .text-2xl {
  line-height: 1.2;
}
</style>