<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'

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

const formattedPrice = computed(() =>
  `$${props.room.price.toFixed(2)}`
)

const availabilityClass = computed(() =>
  props.room.available
    ? 'bg-green-100 text-green-700'
    : 'bg-red-100 text-red-700'
)

const availabilityText = computed(() =>
  props.room.available
    ? 'Available'
    : 'Fully Booked'
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
    class="overflow-hidden rounded-3xl bg-white shadow-lg transition duration-300 hover:-translate-y-2 hover:shadow-2xl"
  >
    <!-- Image -->
    <div class="relative">

      <img
        :src="room.image"
        :alt="room.name"
        class="h-72 w-full object-cover"
      >

      <div
        class="absolute left-4 top-4 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white"
      >
        {{ room.room_type }}
      </div>

      <div
        class="absolute right-4 top-4 rounded-full px-4 py-2 text-sm font-semibold"
        :class="availabilityClass"
      >
        {{ availabilityText }}
      </div>

    </div>

    <!-- Body -->
    <div class="p-6">

      <div
        class="mb-3 flex items-center justify-between"
      >
        <h2
          class="text-2xl font-bold text-slate-900"
        >
          {{ room.name }}
        </h2>

        <span
          class="text-2xl font-bold text-amber-600"
        >
          {{ formattedPrice }}
        </span>

      </div>

      <p
        class="mb-6 text-slate-500"
      >
        Per Night
      </p>

      <!-- Rating -->
      <div
        class="mb-6 flex items-center gap-2"
      >

        <span class="text-yellow-500">
          ★★★★★
        </span>

        <span
          class="font-medium text-slate-700"
        >
          {{ room.rating }}
        </span>

        <span
          class="text-slate-400"
        >
          ({{ room.reviews }} Reviews)
        </span>

      </div>

      <!-- Information -->
      <div
        class="grid grid-cols-2 gap-4"
      >

        <div
          class="rounded-xl bg-slate-100 p-4"
        >
          <p
            class="text-sm text-slate-500"
          >
            Guests
          </p>

          <p
            class="mt-1 font-semibold"
          >
            {{ room.capacity }}
          </p>
        </div>

        <div
          class="rounded-xl bg-slate-100 p-4"
        >
          <p
            class="text-sm text-slate-500"
          >
            Room Size
          </p>

          <p
            class="mt-1 font-semibold"
          >
            {{ room.size }} m²
          </p>
        </div>

        <div
          class="rounded-xl bg-slate-100 p-4"
        >
          <p
            class="text-sm text-slate-500"
          >
            Bed
          </p>

          <p
            class="mt-1 font-semibold"
          >
            {{ room.bed_type }}
          </p>
        </div>

        <div
          class="rounded-xl bg-slate-100 p-4"
        >
          <p
            class="text-sm text-slate-500"
          >
            Type
          </p>

          <p
            class="mt-1 font-semibold"
          >
            {{ room.room_type }}
          </p>
        </div>

      </div>

      <!-- Amenities -->
      <div
        class="mt-8"
      >

        <h3
          class="mb-3 font-semibold text-slate-900"
        >
          Amenities
        </h3>

        <div
          class="flex flex-wrap gap-2"
        >

          <span
            v-for="item in room.amenities.slice(0,5)"
            :key="item"
            class="rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-700"
          >
            {{ item }}
          </span>

        </div>

      </div>

      <!-- Buttons -->
      <div
        class="mt-8 grid grid-cols-2 gap-4"
      >

        <button
          @click="viewDetails"
          class="rounded-xl border border-amber-500 py-3 font-semibold text-amber-600 transition hover:bg-amber-500 hover:text-white"
        >
          View Details
        </button>

        <button
          @click="reserveRoom"
          class="rounded-xl bg-amber-500 py-3 font-semibold text-white transition hover:bg-amber-600"
          :disabled="!room.available"
        >
          Reserve Now
        </button>

      </div>

    </div>

  </div>
</template>