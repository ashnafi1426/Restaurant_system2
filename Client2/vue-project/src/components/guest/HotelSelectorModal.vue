<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { useGuestHotelStore, type PublicHotel } from '@/stores/guestHotelStore'
import { 
  Building2, 
  MapPin, 
  Check, 
  X, 
  Search, 
  Sparkles, 
  Phone, 
  Globe 
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    open?: boolean
  }>(),
  {
    open: undefined
  }
)

const emit = defineEmits<{
  'update:open': [value: boolean]
  'close': []
  'select': [hotel: PublicHotel]
}>()

const guestHotelStore = useGuestHotelStore()
const searchQuery = ref('')

const isOpen = computed(() => {
  if (props.open !== undefined) {
    return props.open
  }
  return guestHotelStore.isSelectorOpen
})

// Filter hotels based on search
const filteredHotels = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  if (!query) return guestHotelStore.availableHotels

  return guestHotelStore.availableHotels.filter(hotel => 
    hotel.name.toLowerCase().includes(query) ||
    (hotel.city && hotel.city.toLowerCase().includes(query)) ||
    (hotel.address && hotel.address.toLowerCase().includes(query)) ||
    (hotel.country && hotel.country.toLowerCase().includes(query))
  )
})

function getHotelLogoUrl(hotel: PublicHotel) {
  const logo = hotel.logo
  if (logo && typeof logo === 'string' && logo.trim()) {
    const trimmed = logo.trim()
    if (
      trimmed.startsWith('http://') ||
      trimmed.startsWith('https://') ||
      trimmed.startsWith('data:') ||
      trimmed.startsWith('/images/')
    ) {
      return trimmed
    }
    if (trimmed.startsWith('/storage/')) {
      return `http://127.0.0.1:8000${trimmed}`
    }
    if (trimmed.startsWith('storage/') || trimmed.startsWith('hotels/')) {
      return `http://127.0.0.1:8000/storage/${trimmed.replace(/^storage\//, '')}`
    }
    if (trimmed.startsWith('images/')) {
      return `/${trimmed}`
    }
    return `http://127.0.0.1:8000/storage/${trimmed}`
  }

  const name = (hotel.name || '').toLowerCase()
  if (name.includes('qelem') || name.includes('meda')) {
    return '/images/qelem meda logo.png'
  }
  return '/images/Hotel logo.png'
}

function handleSelect(hotel: PublicHotel) {
  guestHotelStore.selectHotel(hotel)
  emit('update:open', false)
  emit('select', hotel)
  emit('close')
}

function handleClose() {
  guestHotelStore.closeHotelSelector()
  emit('update:open', false)
  emit('close')
}

onMounted(() => {
  if (guestHotelStore.availableHotels.length === 0) {
    guestHotelStore.fetchAvailableHotels()
  }
  // If no hotel is selected, auto-open the selector
  if (!guestHotelStore.hasSelectedHotel) {
    guestHotelStore.openHotelSelector()
    emit('update:open', true)
  }
})
</script>

<template>
  <Teleport to="body">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 backdrop-blur-md p-4 transition-all duration-300"
      @click.self="handleClose"
    >
      <div 
        class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-3xl shadow-2xl max-w-2xl w-full p-6 md:p-8 relative flex flex-col max-h-[90vh] overflow-hidden transform transition-all duration-300 animate-in fade-in zoom-in-95"
      >
        <!-- Header -->
        <div class="flex items-start justify-between pb-5 border-b border-slate-100 dark:border-slate-800">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-bold uppercase tracking-wider mb-2">
              <Sparkles class="w-3.5 h-3.5" />
              <span>Multi-Property Experience</span>
            </div>
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
              {{ guestHotelStore.hasSelectedHotel ? 'Switch Hotel' : 'Select Your Destination' }}
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
              Choose a hotel to browse curated rooms, amenities, and personalized dining.
            </p>
          </div>

          <!-- Close button (only if a hotel is already selected) -->
          <button 
            v-if="guestHotelStore.hasSelectedHotel"
            @click="handleClose"
            class="p-2 rounded-2xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            title="Close"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Search Bar -->
        <div class="py-4">
          <div class="relative">
            <Search class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" />
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Search by hotel name, city, or location..."
              class="w-full pl-11 pr-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
            />
          </div>
        </div>

        <!-- Hotels List / States -->
        <div class="overflow-y-auto flex-1 pr-1 space-y-3 py-2">
          <!-- Loading state -->
          <div v-if="guestHotelStore.isLoading" class="flex flex-col items-center justify-center py-16">
            <div class="w-10 h-10 border-4 border-amber-500/20 border-t-amber-500 rounded-full animate-spin mb-3"></div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Loading available hotels...</p>
          </div>

          <!-- Empty search result -->
          <div v-else-if="filteredHotels.length === 0 && searchQuery" class="text-center py-12">
            <Building2 class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" />
            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">No hotels found matching "{{ searchQuery }}"</p>
            <p class="text-xs text-slate-400 mt-1">Try a different search term or clear the filter.</p>
          </div>

          <!-- No hotels available at all -->
          <div v-else-if="guestHotelStore.availableHotels.length === 0" class="text-center py-12">
            <Building2 class="w-12 h-12 text-rose-400 mx-auto mb-3" />
            <p class="text-sm font-bold text-rose-600 dark:text-rose-400">No active hotels are currently available.</p>
            <button 
              @click="guestHotelStore.fetchAvailableHotels()" 
              class="mt-3 px-4 py-2 text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-xl transition cursor-pointer"
            >
              Retry
            </button>
          </div>

          <!-- Hotel Cards Grid -->
          <div v-else class="grid grid-cols-1 gap-3">
            <button
              v-for="hotel in filteredHotels"
              :key="hotel.id"
              @click="handleSelect(hotel)"
              class="w-full text-left p-4 md:p-5 rounded-2xl border-2 transition-all duration-200 flex items-center justify-between gap-4 group cursor-pointer"
              :class="[
                guestHotelStore.hotelId === hotel.id
                  ? 'border-amber-500 bg-amber-50/60 dark:bg-amber-950/20 shadow-sm'
                  : 'border-slate-100 dark:border-slate-800/80 hover:border-amber-400 dark:hover:border-amber-500/60 hover:bg-slate-50 dark:hover:bg-slate-800/40'
              ]"
            >
              <div class="flex items-center gap-4 min-w-0">
                <!-- Avatar / Logo -->
                <div 
                  class="w-14 h-14 rounded-2xl flex-shrink-0 flex items-center justify-center font-black text-xl shadow-xs overflow-hidden transition-transform group-hover:scale-105"
                  :class="[
                    guestHotelStore.hotelId === hotel.id
                      ? 'bg-amber-500 text-slate-950'
                      : 'bg-slate-100 dark:bg-slate-800 text-amber-600 dark:text-amber-400 border border-slate-200 dark:border-slate-700'
                  ]"
                >
                  <img 
                    v-if="getHotelLogoUrl(hotel)" 
                    :src="getHotelLogoUrl(hotel)!" 
                    :alt="hotel.name" 
                    class="w-full h-full object-contain p-1"
                    @error="(e: any) => { e.target.style.display = 'none'; if (e.target.nextElementSibling) e.target.nextElementSibling.style.display = 'block'; }" 
                  />
                  <span :style="{ display: getHotelLogoUrl(hotel) ? 'none' : 'block' }">{{ (hotel.name || 'H').charAt(0).toUpperCase() }}</span>
                </div>

                <!-- Info -->
                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <h3 
                      class="text-base font-bold text-slate-900 dark:text-white truncate transition-colors"
                      :class="{'text-amber-600 dark:text-amber-400': guestHotelStore.hotelId === hotel.id}"
                    >
                      {{ hotel.name }}
                    </h3>
                    <span 
                      v-if="guestHotelStore.hotelId === hotel.id"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-slate-950 uppercase tracking-wider"
                    >
                      <Check class="w-3 h-3" />
                      Active
                    </span>
                  </div>

                  <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400 mt-1">
                    <span v-if="hotel.address || hotel.city" class="flex items-center gap-1">
                      <MapPin class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" />
                      <span class="truncate">{{ [hotel.address, hotel.city].filter(Boolean).join(', ') }}</span>
                    </span>
                    <span v-if="hotel.phone" class="hidden sm:flex items-center gap-1">
                      <Phone class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                      <span>{{ hotel.phone }}</span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Action Indicator -->
              <div class="flex-shrink-0">
                <span 
                  v-if="guestHotelStore.hotelId === hotel.id"
                  class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-500 text-slate-950 shadow-xs"
                >
                  <Check class="w-4 h-4 stroke-[3]" />
                </span>
                <span 
                  v-else
                  class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 group-hover:bg-amber-500 group-hover:text-slate-950 transition-colors"
                >
                  Select
                </span>
              </div>
            </button>
          </div>
        </div>

        <!-- Footer Notice -->
        <div class="pt-4 mt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-400">
          <span>You can switch hotels at any time from the top navigation.</span>
          <span v-if="guestHotelStore.currentHotel" class="font-semibold text-slate-600 dark:text-slate-300">
            Selected: {{ guestHotelStore.hotelName }}
          </span>
        </div>
      </div>
    </div>
  </Teleport>
</template>
