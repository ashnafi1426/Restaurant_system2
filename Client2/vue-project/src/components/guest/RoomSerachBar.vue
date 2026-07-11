<script setup lang="ts">
import { reactive } from 'vue'

const searchForm = reactive({
  checkIn: '',
  checkOut: '',
  adults: 2,
  children: 0,
})

const searchRooms = () => {
  console.log('Searching Rooms...', searchForm)

  // Later
  // router.push({
  //   path: '/rooms',
  //   query: {
  //     check_in: searchForm.checkIn,
  //     check_out: searchForm.checkOut,
  //     adults: searchForm.adults,
  //     children: searchForm.children,
  //   },
  // })
}
</script>

<template>
  <section
    class="-mt-20 relative z-20 pb-12"
  >
    <div
      class="mx-auto max-w-7xl px-6"
    >

      <div
        class="rounded-3xl bg-white p-8 shadow-2xl"
      >

        <div
          class="grid gap-6 lg:grid-cols-5"
        >

          <!-- Check In -->
          <div>

            <label
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Check In
            </label>

            <input
              v-model="searchForm.checkIn"
              type="date"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
            >

          </div>

          <!-- Check Out -->
          <div>

            <label
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Check Out
            </label>

            <input
              v-model="searchForm.checkOut"
              type="date"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
            >

          </div>

          <!-- Adults -->
          <div>

            <label
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Adults
            </label>

            <select
              v-model="searchForm.adults"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
            >

              <option :value="1">1 Adult</option>
              <option :value="2">2 Adults</option>
              <option :value="3">3 Adults</option>
              <option :value="4">4 Adults</option>
              <option :value="5">5 Adults</option>
              <option :value="6">6 Adults</option>

            </select>

          </div>

          <!-- Children -->
          <div>

            <label
              class="mb-2 block text-sm font-semibold text-slate-700"
            >
              Children
            </label>

            <select
              v-model="searchForm.children"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none transition focus:border-amber-500"
            >

              <option :value="0">0 Children</option>
              <option :value="1">1 Child</option>
              <option :value="2">2 Children</option>
              <option :value="3">3 Children</option>
              <option :value="4">4 Children</option>

            </select>
          </div>
          <!-- Button -->
          <div
            class="flex items-end"
          >
            <button
              @click="searchRooms"
              class="w-full rounded-xl bg-amber-500 px-6 py-3 text-lg font-semibold text-white transition hover:bg-amber-600"
            >
              Search Rooms
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>