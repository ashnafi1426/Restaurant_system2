<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useLanguageStore } from '@/stores/language'

interface Room {
  id: string
  name: string
  room_number?: string
  room_type: string
  price: number
  capacity: number
  size: number
  bed_type: string
  image: string
  rating: number
  reviews: number
  available: boolean
  amenities: string[]
}

const props = defineProps<{
  room: Room
}>()

const emit = defineEmits<{
  (e: 'details', room: Room): void
}>()

const router = useRouter()
const languageStore = useLanguageStore()

const formattedPrice = computed(() => `$${props.room.price.toFixed(2)}`)

const availabilityClass = computed(() =>
  props.room.available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
)

const availabilityText = computed(() =>
  props.room.available
    ? languageStore.t('available', 'Available')
    : languageStore.t('fully_booked', 'Fully Booked'),
)

function viewDetails() {
  emit('details', props.room)
}

function reserveRoom() {
  router.push({
    path: '/reservation',
    query: {
      room: props.room.id,
    },
  })
}
</script>

<template>
  <div
    class="group relative overflow-hidden rounded-2xl bg-white shadow-sm transition-all duration-500 hover:-translate-y-0.5 hover:shadow-md border border-slate-100 h-[360px] md:h-[380px] flex flex-col"
  >
    <!-- Image Container with Overlay - Fixed Height -->
    <div class="relative overflow-hidden h-[160px] md:h-[180px] flex-shrink-0">
      <img
        :src="room.image"
        :alt="room.name"
        class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
      />

      <!-- Gradient Overlay -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"
      ></div>

      <!-- Price Tag - Bottom Left - Minimized -->
      <div class="absolute bottom-2 left-2 flex items-baseline gap-0.5">
        <span class="text-xl md:text-2xl font-semibold text-white drop-shadow-lg">
          {{ formattedPrice }}
        </span>
        <span class="text-[9px] font-light text-white/90">{{
          languageStore.t('per_night', '/night')
        }}</span>
      </div>
    </div>

    <!-- Card Body - Ultra Compact with Flex Grow -->
    <div class="p-3 flex flex-col flex-grow">
      <!-- Title & Rating - Minimized -->
      <div class="mb-2">
        <h3 class="text-sm font-medium text-slate-900 mb-1 leading-tight">
          {{ room.name }}
        </h3>

        <div class="flex items-center gap-1">
          <div class="flex items-center gap-0.5">
            <span class="text-amber-400 text-[10px]">★</span>
            <span class="text-amber-400 text-[10px]">★</span>
            <span class="text-amber-400 text-[10px]">★</span>
            <span class="text-amber-400 text-[10px]">★</span>
            <span class="text-amber-400 text-[10px]">★</span>
          </div>
          <span class="text-[9px] font-light text-slate-700">
            {{ room.rating }}
          </span>
          <span class="text-[9px] text-slate-400 font-light"> ({{ room.reviews }}) </span>
        </div>
      </div>

      <!-- Room Info Grid - Minimized -->
      <div class="grid grid-cols-3 gap-1.5 mb-2 pb-2 border-b border-slate-100">
        <div class="text-center">
          <p class="text-[8px] uppercase tracking-wide text-slate-500 font-light mb-0.5">
            {{ languageStore.t('guests', 'Guests') }}
          </p>
          <p class="text-[11px] font-medium text-slate-900">
            {{ room.capacity }}
          </p>
        </div>

        <div class="text-center border-x border-slate-100">
          <p class="text-[8px] uppercase tracking-wide text-slate-500 font-light mb-0.5">
            {{ languageStore.t('size', 'Size') }}
          </p>
          <p class="text-[11px] font-medium text-slate-900">{{ room.size }}m²</p>
        </div>

        <div class="text-center">
          <p class="text-[8px] uppercase tracking-wide text-slate-500 font-light mb-0.5">
            {{ languageStore.t('bed', 'Bed') }}
          </p>
          <p class="text-[11px] font-medium text-slate-900">{{ room.bed_type }}</p>
        </div>
      </div>

      <!-- Amenities - Ultra Compact -->
      <div class="mb-2">
        <p class="text-[9px] font-light text-slate-700 mb-1">
          {{ languageStore.t('amenities', 'Amenities') }}
        </p>
        <div class="flex flex-wrap gap-1">
          <span
            v-for="item in room.amenities.slice(0, 3)"
            :key="item"
            class="inline-block rounded bg-slate-50 px-1.5 py-0.5 text-[8px] font-light text-slate-600 border border-slate-200"
          >
            {{ item }}
          </span>
          <span
            v-if="room.amenities.length > 3"
            class="inline-block rounded bg-amber-50 px-1.5 py-0.5 text-[8px] font-light text-amber-700 border border-amber-200"
          >
            +{{ room.amenities.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Action Buttons - Minimized, At Bottom with mt-auto -->
      <div class="grid grid-cols-2 gap-1.5 mt-auto">
        <button
          @click="viewDetails"
          class="rounded-xl border border-slate-300 py-2 text-[10px] font-light text-slate-700 transition-all hover:border-amber-500 hover:bg-amber-50 hover:text-amber-700 cursor-pointer"
        >
          {{ languageStore.t('details', 'Details') }}
        </button>

        <button
          @click="reserveRoom"
          class="rounded-xl bg-amber-500 py-2 text-[10px] font-light text-white transition-all hover:bg-amber-600 disabled:bg-slate-300 disabled:cursor-not-allowed shadow-sm cursor-pointer"
          :disabled="!room.available"
        >
          {{ languageStore.t('book_now', 'Book Now') }}
        </button>
      </div>
    </div>
  </div>
</template>
