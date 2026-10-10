<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ref, onMounted, watch } from 'vue'
import menuService from '@/services/menuService'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { Utensils, Calendar, ArrowRight } from 'lucide-vue-next'

const router = useRouter()
const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface MenuItem {
  id: string
  name: string
  image: string
  description: string
  price: number
}

const featuredMenu = ref<MenuItem[]>([])
const loading = ref(false)

const defaultDishPlaceholder =
  'https://images.unsplash.com/photo-1544025162-d76694265947?w=800&h=600&fit=crop'

function reserveTable() {
  router.push('/contact')
}

function viewRestaurantMenu() {
  router.push('/menu')
}

function formatPrice(price: number) {
  const currency = guestHotelStore.currency || 'ETB'
  return `${(price || 0).toLocaleString()} ${currency}`
}

async function loadFeaturedMenu() {
  loading.value = true
  try {
    const response = await menuService.getMenuItems({ per_page: 3, flat: 1, is_available: 1 })
    const items = response.data?.data || response.data
    if (Array.isArray(items) && items.length > 0) {
      featuredMenu.value = items.slice(0, 3).map((item: any) => ({
        id: item.id || String(Math.random()),
        name: item.name,
        image: item.image || item.image_url || defaultDishPlaceholder,
        description: item.description || 'Delicious gourmet chef specialty dish.',
        price: item.total_price != null ? item.total_price : item.price || 0,
      }))
    } else {
      featuredMenu.value = []
    }
  } catch (err) {
    console.error('[RestaurantSection] Failed to load featured menu:', err)
    featuredMenu.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadFeaturedMenu()
})

watch(
  () => guestHotelStore.hotelId,
  () => {
    loadFeaturedMenu()
  },
)
</script>

<template>
  <section
    class="bg-slate-50 dark:bg-slate-950 py-12 sm:py-16 lg:py-24 transition-colors duration-300 font-sans border-b border-slate-200 dark:border-slate-800"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      <!-- Section Header -->
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ languageStore.t('signature_dining', 'Signature Dining') }}
        </span>
        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('culinary_experience', 'A Culinary Experience') }}
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'culinary_experience_desc',
              'Indulge in authentic Ethiopian culinary traditions and international fine dining crafted by award-winning chefs.',
            )
          }}
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-center gap-3">
        <button
          @click="viewRestaurantMenu"
          class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs uppercase tracking-wider shadow-md shadow-amber-500/20 transition cursor-pointer flex items-center gap-2"
        >
          <Utensils class="w-4 h-4" />
          <span>{{ languageStore.t('explore_full_menu', 'Explore Full Menu') }}</span>
        </button>

        <button
          @click="reserveTable"
          class="px-6 py-3 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-900 dark:text-white font-extrabold text-xs transition border border-slate-200 dark:border-slate-800 cursor-pointer flex items-center gap-2"
        >
          <Calendar class="w-4 h-4 text-amber-500" />
          <span>{{ languageStore.t('reserve_table', 'Reserve a Table') }}</span>
        </button>
      </div>

      <!-- Menu Grid -->
      <div v-if="featuredMenu.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="item in featuredMenu"
          :key="item.id"
          class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs hover:border-amber-500/40 transition duration-300 flex flex-col justify-between"
        >
          <!-- Dish Image -->
          <div class="relative h-60 overflow-hidden bg-slate-900">
            <img
              :src="item.image"
              :alt="item.name"
              class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            />
            <div
              class="absolute top-4 right-4 px-3 py-1 bg-slate-950/80 backdrop-blur-md text-amber-400 font-black text-xs rounded-full border border-amber-500/30"
            >
              {{ formatPrice(item.price) }}
            </div>
          </div>

          <!-- Dish Info -->
          <div class="p-6 space-y-3 flex-1 flex flex-col justify-between">
            <div class="space-y-2">
              <h3
                class="text-base font-black text-slate-900 dark:text-white group-hover:text-amber-500 transition"
              >
                {{ item.name }}
              </h3>
              <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                {{ item.description }}
              </p>
            </div>

            <button
              @click="viewRestaurantMenu"
              class="w-full mt-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white font-extrabold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
            >
              <span>{{ languageStore.t('order_food_online', 'Order Food Online') }}</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
