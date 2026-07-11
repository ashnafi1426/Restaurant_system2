<script setup lang="ts">
import { reactive } from 'vue'

const filters = reactive({
  roomType: '',
  price: '',
  capacity: '',
  bedType: '',
  sortBy: 'recommended',
})

const applyFilters = () => {
  console.log('Apply Filters', filters)

  // Later
  // roomStore.filterRooms(filters)
}

const resetFilters = () => {
  filters.roomType = ''
  filters.price = ''
  filters.capacity = ''
  filters.bedType = ''
  filters.sortBy = 'recommended'
}
</script>

<template>
  <section class="bg-white border-b border-slate-200 py-8">

    <div class="mx-auto max-w-7xl px-6">

      <!-- Header -->
      <div
        class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
      >

        <div>

          <h2 class="text-3xl font-bold text-slate-900">
            Find Your Perfect Room
          </h2>

          <p class="mt-2 text-slate-500">
            Filter rooms based on your preferences.
          </p>

        </div>

        <button
          @click="resetFilters"
          class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 transition hover:bg-slate-100"
        >
          Reset Filters
        </button>

      </div>

      <!-- Filters -->
      <div
        class="grid gap-6 md:grid-cols-2 xl:grid-cols-6"
      >

        <!-- Room Type -->
        <div>

          <label
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Room Type
          </label>

          <select
            v-model="filters.roomType"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
          >
            <option value="">All Types</option>
            <option value="standard">Standard</option>
            <option value="deluxe">Deluxe</option>
            <option value="executive">Executive</option>
            <option value="suite">Suite</option>
            <option value="family">Family</option>
          </select>

        </div>

        <!-- Price -->
        <div>

          <label
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Price Range
          </label>

          <select
            v-model="filters.price"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
          >
            <option value="">Any Price</option>
            <option value="0-100">$0 - $100</option>
            <option value="100-200">$100 - $200</option>
            <option value="200-300">$200 - $300</option>
            <option value="300-500">$300 - $500</option>
            <option value="500+">$500+</option>
          </select>

        </div>

        <!-- Capacity -->
        <div>

          <label
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Capacity
          </label>

          <select
            v-model="filters.capacity"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
          >
            <option value="">Any</option>
            <option value="1">1 Guest</option>
            <option value="2">2 Guests</option>
            <option value="3">3 Guests</option>
            <option value="4">4 Guests</option>
            <option value="5">5+ Guests</option>
          </select>

        </div>

        <!-- Bed -->
        <div>

          <label
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Bed Type
          </label>

          <select
            v-model="filters.bedType"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
          >
            <option value="">Any</option>
            <option value="single">Single Bed</option>
            <option value="double">Double Bed</option>
            <option value="queen">Queen Bed</option>
            <option value="king">King Bed</option>
          </select>

        </div>

        <!-- Sort -->
        <div>

          <label
            class="mb-2 block text-sm font-semibold text-slate-700"
          >
            Sort By
          </label>

          <select
            v-model="filters.sortBy"
            class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
          >
            <option value="recommended">Recommended</option>
            <option value="price_low">Price: Low → High</option>
            <option value="price_high">Price: High → Low</option>
            <option value="rating">Highest Rating</option>
            <option value="newest">Newest Rooms</option>
          </select>

        </div>

        <!-- Apply -->
        <div class="flex items-end">

          <button
            @click="applyFilters"
            class="w-full rounded-xl bg-amber-500 py-3 font-semibold text-white transition hover:bg-amber-600"
          >
            Apply Filters
          </button>

        </div>

      </div>

    </div>
  </section>
</template>