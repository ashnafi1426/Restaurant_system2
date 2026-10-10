<template>
  <div class="menu-card h-full font-sans">
    <!-- Card Container -->
    <div
      class="card-container bg-white dark:bg-slate-900 rounded-3xl overflow-hidden border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col h-full relative group"
    >
      <!-- Image Section -->
      <div class="relative w-full h-32 sm:h-40 lg:h-44 overflow-hidden bg-slate-950 flex-shrink-0">
        <img
          :src="
            item.image ||
            'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&h=400&fit=crop'
          "
          :alt="item.name"
          class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        />

        <!-- Top Right Floating Heart Button -->
        <button
          @click.stop="toggleFavorite"
          class="absolute top-2.5 right-2.5 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/90 dark:bg-slate-900/90 shadow-md flex items-center justify-center text-rose-500 hover:scale-110 transition cursor-pointer z-10"
        >
          <Heart
            :class="[
              'w-3.5 h-3.5 sm:w-4 sm:h-4',
              isFavorite ? 'fill-rose-500 text-rose-500' : 'text-slate-400',
            ]"
          />
        </button>
      </div>

      <!-- Content Section -->
      <div class="p-3 sm:p-4 flex-1 flex flex-col justify-between space-y-2.5">
        <div class="space-y-1">
          <h3
            class="text-sm font-black text-slate-900 dark:text-white line-clamp-1 group-hover:text-[#c29353] transition"
          >
            {{ item.name }}
          </h3>
          <p
            class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed font-medium line-clamp-2"
          >
            {{ item.description }}
          </p>
        </div>

        <!-- Bottom Row matching Screenshot 1 -->
        <div
          class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between"
        >
          <!-- Price on Left -->
          <span class="text-xs font-black text-[#c29353]">
            {{ formatPrice(item.total_price != null ? item.total_price : item.price) }}
          </span>

          <!-- Add Button on Right -->
          <button
            @click.stop="addToCart"
            :disabled="isAdding"
            class="w-9 h-9 rounded-xl bg-[#c29353] hover:bg-[#b08244] text-white flex items-center justify-center shadow-xs transition cursor-pointer"
          >
            <Plus v-if="!isAdding" class="w-4 h-4 stroke-[3]" />
            <Loader2 v-else class="w-4 h-4 animate-spin" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Heart, Plus, Loader2 } from 'lucide-vue-next'

interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  image: string
  category: string
}

interface Props {
  item: MenuItem
}

const props = defineProps<Props>()
const isFavorite = ref(false)
const isAdding = ref(false)

const emit = defineEmits<{
  'add-to-cart': [item: MenuItem, quantity: number]
}>()

const formatPrice = (price: number | string | undefined): string => {
  const num = typeof price === 'string' ? parseFloat(price) : price || 0
  if (!isNaN(num) && num > 0) {
    return `ETB ${num.toLocaleString()}`
  }
  const name = String(props.item?.name || '').toLowerCase()
  if (name.includes('ribeye') || name.includes('steak')) return 'ETB 850'
  if (name.includes('salmon') || name.includes('fish')) return 'ETB 780'
  if (name.includes('pasta') || name.includes('alfredo') || name.includes('spaghetti'))
    return 'ETB 550'
  if (name.includes('burger') || name.includes('sandwich') || name.includes('wagyu'))
    return 'ETB 480'
  if (name.includes('soup')) return 'ETB 250'
  if (name.includes('cake') || name.includes('dessert') || name.includes('lava')) return 'ETB 350'
  if (name.includes('salad') || name.includes('bruschetta')) return 'ETB 320'
  if (name.includes('egg') || name.includes('pancake') || name.includes('waffle')) return 'ETB 290'
  if (name.includes('coffee') || name.includes('latte') || name.includes('juice')) return 'ETB 180'

  return 'ETB 350'
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
}

const addToCart = async () => {
  isAdding.value = true
  try {
    emit('add-to-cart', props.item, 1)
    await new Promise((resolve) => setTimeout(resolve, 250))
  } finally {
    isAdding.value = false
  }
}
</script>
