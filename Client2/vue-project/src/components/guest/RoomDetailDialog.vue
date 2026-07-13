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
  description?: string
}

const props = defineProps<{
  modelValue: boolean
  room: Room
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'close'): void
}>()

const router = useRouter()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => {
    emit('update:modelValue', value)
  },
})

const formattedPrice = computed(() => {
  return `$${props.room.price.toFixed(2)}`
})

const availabilityText = computed(() => {
  return props.room.available ? 'Available' : 'Fully Booked'
})

const availabilityClass = computed(() => {
  return props.room.available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
})

function closeDialog() {
  emit('update:modelValue', false)
  emit('close')
}

function reserveRoom() {
  emit('update:modelValue', false)

  router.push({
    path: '/reservation',
    query: {
      room: props.room.id,
    },
  })
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-sm">
        <div class="flex min-h-screen items-center justify-center p-6">
          <!-- Dialog -->
          <div class="relative w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <!-- Close -->
            <button
              @click="closeDialog"
              class="absolute right-5 top-5 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-lg transition hover:bg-red-500 hover:text-white"
            >
              ✕
            </button>

            <!-- Hero Image -->
            <div class="relative">
              <img :src="room.image" :alt="room.name" class="h-[420px] w-full object-cover" />

              <!-- Overlay -->
              <div
                class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent"
              />

              <!-- Availability -->
              <div
                class="absolute left-8 top-8 rounded-full px-5 py-2 text-sm font-semibold"
                :class="availabilityClass"
              >
                {{ availabilityText }}
              </div>

              <!-- Bottom Content -->
              <div class="absolute bottom-10 left-10 right-10 text-white">
                <!-- Room Type -->
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-amber-400">
                  {{ room.room_type }}
                </p>

                <!-- Room Name -->
                <h2 class="mt-3 text-5xl font-bold">
                  {{ room.name }}
                </h2>

                <!-- Rating -->
                <div class="mt-5 flex flex-wrap items-center gap-4">
                  <div class="flex items-center gap-2">
                    <span class="text-yellow-400 text-xl"> ★★★★★ </span>

                    <span class="font-semibold">
                      {{ room.rating }}
                    </span>

                    <span class="text-gray-300"> ({{ room.reviews }} Reviews) </span>
                  </div>

                  <div class="h-5 w-px bg-white/40" />

                  <div class="font-semibold">
                    {{ formattedPrice }}

                    <span class="font-normal text-gray-300"> / Night </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Body -->
            <div class="p-10">
              <!-- Quick Information -->
              <div class="grid gap-6 md:grid-cols-4">
                <!-- Guests -->
                <div class="rounded-2xl border border-slate-200 p-6 text-center">
                  <div class="text-4xl">👥</div>

                  <h4 class="mt-4 font-bold text-slate-900">Capacity</h4>

                  <p class="mt-2 text-slate-500">{{ room.capacity }} Guests</p>
                </div>

                <!-- Room Size -->
                <div class="rounded-2xl border border-slate-200 p-6 text-center">
                  <div class="text-4xl">📐</div>

                  <h4 class="mt-4 font-bold text-slate-900">Room Size</h4>

                  <p class="mt-2 text-slate-500">{{ room.size }} m²</p>
                </div>

                <!-- Bed -->
                <div class="rounded-2xl border border-slate-200 p-6 text-center">
                  <div class="text-4xl">🛏️</div>

                  <h4 class="mt-4 font-bold text-slate-900">Bed Type</h4>

                  <p class="mt-2 text-slate-500">
                    {{ room.bed_type }}
                  </p>
                </div>

                <!-- Price -->
                <div class="rounded-2xl border border-slate-200 p-6 text-center">
                  <div class="text-4xl">💰</div>

                  <h4 class="mt-4 font-bold text-slate-900">Price</h4>

                  <p class="mt-2 text-amber-600 text-xl font-bold">
                    {{ formattedPrice }}
                  </p>
                </div>
              </div>
              <!-- Description + Amenities -->
              <div class="mt-12 grid gap-10 lg:grid-cols-3">
                <!-- Left Content -->
                <div class="lg:col-span-2">
                  <!-- Description -->
                  <section>
                    <h3 class="text-3xl font-bold text-slate-900">Room Description</h3>

                    <p class="mt-6 leading-8 text-slate-600">
                      {{
                        room.description ||
                        'Experience premium comfort in our beautifully designed room. Featuring modern interiors, luxury bedding, complimentary Wi-Fi, air conditioning, smart television, elegant bathroom facilities, and everything required for a relaxing stay whether travelling for business or leisure.'
                      }}
                    </p>
                  </section>

                  <!-- Amenities -->
                  <section class="mt-12">
                    <h3 class="text-3xl font-bold text-slate-900">Room Amenities</h3>

                    <div class="mt-8 grid gap-4 md:grid-cols-2">
                      <div
                        v-for="amenity in room.amenities"
                        :key="amenity"
                        class="flex items-center rounded-xl border border-slate-200 p-4"
                      >
                        <div
                          class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100"
                        >
                          ✓
                        </div>

                        <span class="font-medium text-slate-700">
                          {{ amenity }}
                        </span>
                      </div>
                    </div>
                  </section>

                  <!-- Hotel Services -->
                  <section class="mt-12">
                    <h3 class="text-3xl font-bold text-slate-900">Included Services</h3>

                    <div class="mt-8 grid gap-5 md:grid-cols-2">
                      <div class="rounded-xl bg-slate-50 p-5">🍽️ Complimentary Breakfast</div>

                      <div class="rounded-xl bg-slate-50 p-5">📶 High-Speed Wi-Fi</div>

                      <div class="rounded-xl bg-slate-50 p-5">🏊 Swimming Pool Access</div>

                      <div class="rounded-xl bg-slate-50 p-5">🏋️ Fitness Center</div>

                      <div class="rounded-xl bg-slate-50 p-5">🚗 Free Parking</div>

                      <div class="rounded-xl bg-slate-50 p-5">🧹 Daily Housekeeping</div>
                    </div>
                  </section>
                </div>

                <!-- Right Sidebar -->
                <aside>
                  <!-- Policies -->
                  <div class="rounded-2xl border border-slate-200 p-8 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-900">Hotel Policies</h3>

                    <div class="mt-8 space-y-6">
                      <div>
                        <h4 class="font-semibold text-slate-800">Check-in</h4>

                        <p class="mt-2 text-slate-500">From 2:00 PM</p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Check-out</h4>

                        <p class="mt-2 text-slate-500">Before 12:00 PM</p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Cancellation</h4>

                        <p class="mt-2 text-slate-500">
                          Free cancellation up to 24 hours before arrival.
                        </p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Smoking</h4>

                        <p class="mt-2 text-slate-500">Non-smoking room.</p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Pets</h4>

                        <p class="mt-2 text-slate-500">Pets are not allowed.</p>
                      </div>
                    </div>
                  </div>

                  <!-- Reservation Summary -->
                  <div class="mt-8 rounded-2xl bg-amber-50 p-8">
                    <h3 class="text-2xl font-bold text-slate-900">Reservation Summary</h3>

                    <div class="mt-8 space-y-5">
                      <div class="flex justify-between">
                        <span class="text-slate-500"> Room Type </span>

                        <strong>
                          {{ room.room_type }}
                        </strong>
                      </div>

                      <div class="flex justify-between">
                        <span class="text-slate-500"> Capacity </span>

                        <strong> {{ room.capacity }} Guests </strong>
                      </div>

                      <div class="flex justify-between">
                        <span class="text-slate-500"> Bed </span>

                        <strong>
                          {{ room.bed_type }}
                        </strong>
                      </div>

                      <hr />

                      <div class="flex justify-between text-2xl font-bold">
                        <span>Total</span>

                        <span class="text-amber-600">
                          {{ formattedPrice }}
                        </span>
                      </div>
                    </div>
                  </div>
                </aside>
              </div>
              <!-- Description + Amenities -->
              <div class="mt-12 grid gap-10 lg:grid-cols-3">
                <!-- Left Content -->
                <div class="lg:col-span-2">
                  <!-- Description -->
                  <section>
                    <h3 class="text-3xl font-bold text-slate-900">Room Description</h3>

                    <p class="mt-6 leading-8 text-slate-600">
                      {{
                        room.description ||
                        'Experience premium comfort in our beautifully designed room. Featuring modern interiors, luxury bedding, complimentary Wi-Fi, air conditioning, smart television, elegant bathroom facilities, and everything required for a relaxing stay whether travelling for business or leisure.'
                      }}
                    </p>
                  </section>

                  <!-- Amenities -->
                  <section class="mt-12">
                    <h3 class="text-3xl font-bold text-slate-900">Room Amenities</h3>

                    <div class="mt-8 grid gap-4 md:grid-cols-2">
                      <div
                        v-for="amenity in room.amenities"
                        :key="amenity"
                        class="flex items-center rounded-xl border border-slate-200 p-4"
                      >
                        <div
                          class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100"
                        >
                          ✓
                        </div>

                        <span class="font-medium text-slate-700">
                          {{ amenity }}
                        </span>
                      </div>
                    </div>
                  </section>

                  <!-- Hotel Services -->
                  <section class="mt-12">
                    <h3 class="text-3xl font-bold text-slate-900">Included Services</h3>

                    <div class="mt-8 grid gap-5 md:grid-cols-2">
                      <div class="rounded-xl bg-slate-50 p-5">🍽️ Complimentary Breakfast</div>

                      <div class="rounded-xl bg-slate-50 p-5">📶 High-Speed Wi-Fi</div>

                      <div class="rounded-xl bg-slate-50 p-5">🏊 Swimming Pool Access</div>

                      <div class="rounded-xl bg-slate-50 p-5">🏋️ Fitness Center</div>

                      <div class="rounded-xl bg-slate-50 p-5">🚗 Free Parking</div>

                      <div class="rounded-xl bg-slate-50 p-5">🧹 Daily Housekeeping</div>
                    </div>
                  </section>
                </div>

                <!-- Right Sidebar -->
                <aside>
                  <!-- Policies -->
                  <div class="rounded-2xl border border-slate-200 p-8 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-900">Hotel Policies</h3>

                    <div class="mt-8 space-y-6">
                      <div>
                        <h4 class="font-semibold text-slate-800">Check-in</h4>

                        <p class="mt-2 text-slate-500">From 2:00 PM</p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Check-out</h4>

                        <p class="mt-2 text-slate-500">Before 12:00 PM</p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Cancellation</h4>

                        <p class="mt-2 text-slate-500">
                          Free cancellation up to 24 hours before arrival.
                        </p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Smoking</h4>

                        <p class="mt-2 text-slate-500">Non-smoking room.</p>
                      </div>

                      <div>
                        <h4 class="font-semibold text-slate-800">Pets</h4>

                        <p class="mt-2 text-slate-500">Pets are not allowed.</p>
                      </div>
                    </div>
                  </div>

                  <!-- Reservation Summary -->
                  <div class="mt-8 rounded-2xl bg-amber-50 p-8">
                    <h3 class="text-2xl font-bold text-slate-900">Reservation Summary</h3>

                    <div class="mt-8 space-y-5">
                      <div class="flex justify-between">
                        <span class="text-slate-500"> Room Type </span>

                        <strong>
                          {{ room.room_type }}
                        </strong>
                      </div>

                      <div class="flex justify-between">
                        <span class="text-slate-500"> Capacity </span>

                        <strong> {{ room.capacity }} Guests </strong>
                      </div>

                      <div class="flex justify-between">
                        <span class="text-slate-500"> Bed </span>

                        <strong>
                          {{ room.bed_type }}
                        </strong>
                      </div>

                      <hr />

                      <div class="flex justify-between text-2xl font-bold">
                        <span>Total</span>

                        <span class="text-amber-600">
                          {{ formattedPrice }}
                        </span>
                      </div>
                    </div>
                  </div>
                </aside>
              </div>
              <!-- Bottom Action Area -->
              <div class="mt-14 border-t border-slate-200 pt-10">
                <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                  <!-- Availability -->
                  <div>
                    <h3 class="text-2xl font-bold text-slate-900">Current Availability</h3>

                    <div
                      class="mt-4 inline-flex items-center rounded-full px-5 py-3 font-semibold"
                      :class="availabilityClass"
                    >
                      {{ availabilityText }}
                    </div>

                    <p class="mt-4 max-w-xl text-slate-500">
                      Room availability is updated in real time. Once your reservation is confirmed,
                      this room will immediately become unavailable for the selected dates.
                    </p>
                  </div>

                  <!-- Price & Buttons -->
                  <div class="flex flex-col items-end">
                    <p class="text-slate-500">Starting From</p>

                    <h2 class="mt-2 text-5xl font-bold text-amber-600">
                      {{ formattedPrice }}
                    </h2>

                    <p class="mt-2 text-slate-500">Per Night</p>

                    <div class="mt-8 flex flex-wrap gap-4">
                      <!-- Close -->
                      <button
                        @click="closeDialog"
                        class="rounded-xl border border-slate-300 px-8 py-4 font-semibold text-slate-700 transition hover:bg-slate-100"
                      >
                        Close
                      </button>

                      <!-- Contact -->
                      <RouterLink
                        to="/contact"
                        class="rounded-xl border border-amber-500 px-8 py-4 font-semibold text-amber-600 transition hover:bg-amber-500 hover:text-white"
                      >
                        Contact Hotel
                      </RouterLink>

                      <!-- Reserve -->
                      <button
                        @click="reserveRoom"
                        :disabled="!room.available"
                        class="rounded-xl bg-amber-500 px-10 py-4 font-semibold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                      >
                        Reserve Now
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Body -->
          </div>
          <!-- End Dialog -->
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
