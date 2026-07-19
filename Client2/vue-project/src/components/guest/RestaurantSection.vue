<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'
import menuService from '@/services/menuService'

const router = useRouter()

interface MenuItem {
  id: string
  name: string
  image: string
  description: string
  price: number
}

const featuredMenu = ref<MenuItem[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

function reserveTable() {
  router.push('/reservation')
}

function viewRestaurant() {
  router.push('/restaurant')
}

function formatPrice(price: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(price)
}

/**
 * Load featured menu items from backend
 */
async function loadFeaturedMenu() {
  loading.value = true
  error.value = null

  try {
    const response = await menuService.getMenuItems({ per_page: 3, is_active: true })
    
    if (response.data?.data && Array.isArray(response.data.data)) {
      // Take first 3 items as featured
      featuredMenu.value = response.data.data.slice(0, 3).map((item: any) => ({
        id: item.id,
        name: item.name,
        image: item.image || '/images/placeholder.png',
        description: item.description || 'Delicious menu item',
        price: item.price || 0,
      }))
      
      console.log(`[RestaurantSection] Loaded ${featuredMenu.value.length} featured menu items from backend`)
    } else {
      // Fallback to empty or default items
      error.value = 'No menu items available'
      console.warn('[RestaurantSection] No menu items found in response')
    }
  } catch (err: any) {
    error.value = err.message || 'Failed to load menu items'
    console.error('[RestaurantSection] Error loading menu items:', err)
    
    // Still show section but with empty items
    featuredMenu.value = []
  } finally {
    loading.value = false
  }
}

/**
 * Load menu on mount
 */
onMounted(() => {
  loadFeaturedMenu()
})
</script>

<template>
  <section class="bg-[#faf8f4] py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p
          class="mb-2 sm:mb-4 text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600"
        >
          Signature Restaurant
        </p>

        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          A Culinary Experience
        </h2>

        <p
          class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500"
        >
          Enjoy international cuisine, freshly prepared by experienced chefs using premium
          ingredients in an elegant dining atmosphere.
        </p>
      </div>

      <!-- Restaurant Banner -->
      <div
        class="mb-12 sm:mb-16 md:mb-20 overflow-hidden rounded-2xl sm:rounded-3xl bg-slate-900 grid grid-cols-1 lg:grid-cols-2"
      >
        <img
          src="/images/food/coffee.jpg"
          alt="Restaurant"
          class="h-48 sm:h-56 md:h-80 lg:h-full lg:min-h-[420px] w-full object-cover"
        />

        <div class="flex flex-col justify-center p-4 sm:p-6 md:p-8 lg:p-12 text-white">
          <p
            class="text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] md:tracking-[0.35em] text-amber-400"
          >
            Fine Dining
          </p>

          <h3 class="mt-2 sm:mt-3 md:mt-4 text-2xl sm:text-3xl md:text-4xl font-light">
            Taste Excellence
          </h3>

          <p
            class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-300"
          >
            Our restaurant offers breakfast, lunch and dinner, combining local flavors with
            international cuisine in a sophisticated setting.
          </p>

          <div class="mt-6 sm:mt-8 md:mt-10 flex flex-col sm:flex-row flex-wrap gap-2 sm:gap-4">
            <button
              class="rounded-full bg-amber-500 px-4 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 text-xs sm:text-sm md:text-base font-semibold transition hover:bg-amber-600"
              @click="reserveTable"
            >
              Reserve a Table
            </button>

            <button
              class="rounded-full border border-white px-4 sm:px-6 md:px-8 py-2.5 sm:py-3 md:py-4 text-xs sm:text-sm md:text-base font-semibold transition hover:bg-white hover:text-slate-900"
              @click="viewRestaurant"
            >
              View Menu
            </button>
          </div>
        </div>
      </div>

      <!-- Featured Dishes -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="h-12 w-12 animate-spin rounded-full border-4 border-amber-500 border-t-transparent" />
      </div>

      <div v-if="!loading && featuredMenu.length > 0" class="grid gap-4 sm:gap-6 md:gap-8 lg:gap-10 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="item in featuredMenu"
          :key="item.id"
          class="group overflow-hidden rounded-lg sm:rounded-2xl lg:rounded-3xl bg-white shadow transition duration-500 hover:-translate-y-1 sm:hover:-translate-y-2 hover:shadow-lg md:hover:shadow-2xl"
        >
          <div class="overflow-hidden">
            <img
              :src="item.image"
              :alt="item.name"
              class="h-40 sm:h-48 md:h-56 lg:h-72 w-full object-cover transition duration-700 group-hover:scale-110"
            />
          </div>

          <div class="p-4 sm:p-6 md:p-8">
            <div
              class="mb-2 sm:mb-3 md:mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
            >
              <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900">
                {{ item.name }}
              </h3>

              <span
                class="text-sm sm:text-base md:text-lg font-bold text-amber-600 whitespace-nowrap"
              >
                {{ formatPrice(item.price) }}
              </span>
            </div>

            <p class="text-xs sm:text-sm md:text-base leading-6 sm:leading-7 text-slate-500">
              {{ item.description }}
            </p>
          </div>
        </article>
      </div>

      <div v-if="!loading && featuredMenu.length === 0" class="text-center py-12">
        <p class="text-slate-500">{{ error || 'No menu items available at this time' }}</p>
      </div>

      <!-- Bottom CTA -->
      <div class="mt-12 sm:mt-16 md:mt-20">
        <div
          class="rounded-lg sm:rounded-2xl lg:rounded-3xl bg-amber-600 px-4 sm:px-6 md:px-8 lg:px-10 py-8 sm:py-10 md:py-12 lg:py-16 text-center text-white"
        >
          <h2 class="text-2xl sm:text-3xl md:text-4xl font-light">Dine With Us</h2>

          <p
            class="mx-auto mt-3 sm:mt-4 md:mt-6 max-w-3xl text-xs sm:text-sm md:text-base lg:text-lg leading-6 sm:leading-7 md:leading-8 text-amber-100"
          >
            Whether you're enjoying breakfast before a busy day or a romantic dinner in the evening,
            our chefs are ready to make every meal memorable.
          </p>

          <button
            class="mt-6 sm:mt-8 md:mt-10 rounded-full bg-white px-6 sm:px-8 md:px-10 py-2.5 sm:py-3 md:py-4 text-xs sm:text-sm md:text-base font-semibold text-amber-600 transition hover:bg-slate-100"
            @click="viewRestaurant"
          >
            Explore Our Restaurant
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
