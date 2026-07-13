<script setup lang="ts">
import { computed, reactive } from 'vue'

const availability = reactive({
  checkIn: '',
  checkOut: '',
  adults: 2,
  children: 0,
  availableRooms: 5,
  pricePerNight: 180,
})

const totalNights = computed(() => {
  if (!availability.checkIn || !availability.checkOut) return 0

  const start = new Date(availability.checkIn)
  const end = new Date(availability.checkOut)

  const diff = (end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)

  return diff > 0 ? diff : 0
})

const estimatedTotal = computed(() => {
  return totalNights.value * availability.pricePerNight
})

function checkAvailability() {
  console.log('Checking availability...', availability)

  // Later:
  // GET /api/public/rooms/availability
}
</script>

<template>
  <section class="rounded-3xl bg-white p-8 shadow-xl">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-bold text-slate-900">Room Availability</h2>

      <p class="mt-2 text-slate-500">Select your stay dates to check room availability.</p>
    </div>

    <!-- Form -->
    <div class="grid gap-6 lg:grid-cols-4">
      <!-- Check In -->
      <div>
        <label class="mb-2 block font-medium text-slate-700"> Check In </label>

        <input
          v-model="availability.checkIn"
          type="date"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-amber-500 focus:outline-none"
        />
      </div>

      <!-- Check Out -->
      <div>
        <label class="mb-2 block font-medium text-slate-700"> Check Out </label>

        <input
          v-model="availability.checkOut"
          type="date"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-amber-500 focus:outline-none"
        />
      </div>

      <!-- Adults -->
      <div>
        <label class="mb-2 block font-medium text-slate-700"> Adults </label>

        <select
          v-model="availability.adults"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-amber-500 focus:outline-none"
        >
          <option :value="1">1 Adult</option>
          <option :value="2">2 Adults</option>
          <option :value="3">3 Adults</option>
          <option :value="4">4 Adults</option>
        </select>
      </div>

      <!-- Children -->
      <div>
        <label class="mb-2 block font-medium text-slate-700"> Children </label>

        <select
          v-model="availability.children"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-amber-500 focus:outline-none"
        >
          <option :value="0">0 Children</option>
          <option :value="1">1 Child</option>
          <option :value="2">2 Children</option>
          <option :value="3">3 Children</option>
        </select>
      </div>
    </div>

    <!-- Button -->
    <div class="mt-8">
      <button
        @click="checkAvailability"
        class="rounded-xl bg-amber-500 px-8 py-3 font-semibold text-white transition hover:bg-amber-600"
      >
        Check Availability
      </button>
    </div>

    <!-- Summary -->
    <div class="mt-10 rounded-2xl bg-slate-50 p-8">
      <h3 class="text-2xl font-bold text-slate-900">Booking Summary</h3>

      <div class="mt-6 grid gap-6 md:grid-cols-2">
        <div>
          <p class="text-slate-500">Available Rooms</p>

          <h4 class="mt-2 text-3xl font-bold text-green-600">
            {{ availability.availableRooms }}
          </h4>
        </div>

        <div>
          <p class="text-slate-500">Price Per Night</p>

          <h4 class="mt-2 text-3xl font-bold text-amber-600">${{ availability.pricePerNight }}</h4>
        </div>

        <div>
          <p class="text-slate-500">Total Nights</p>

          <h4 class="mt-2 text-3xl font-bold">
            {{ totalNights }}
          </h4>
        </div>

        <div>
          <p class="text-slate-500">Estimated Total</p>

          <h4 class="mt-2 text-3xl font-bold text-amber-600">${{ estimatedTotal }}</h4>
        </div>
      </div>
    </div>
  </section>
</template>
