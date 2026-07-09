<script setup lang="ts">
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const stats = [
  {
    title: 'Total Revenue',
    value: '$58,400',
  },
  {
    title: 'Occupancy Rate',
    value: '82%',
  },
  {
    title: 'Reservations',
    value: 124,
  },
  {
    title: 'Guests',
    value: 178,
  },
]

const performance = [
  {
    date: '2026-06-25',
    revenue: '$4,200',
    occupancy: '85%',
    reservations: 18,
  },
  {
    date: '2026-06-24',
    revenue: '$3,950',
    occupancy: '81%',
    reservations: 15,
  },
  {
    date: '2026-06-23',
    revenue: '$4,500',
    occupancy: '89%',
    reservations: 20,
  },
]
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold text-slate-800">Manager Dashboard</h1>

        <p class="text-slate-500 mt-1">Monitor hotel performance, occupancy and revenue.</p>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div
          v-for="stat in stats"
          :key="stat.title"
          class="bg-white rounded-xl border shadow-sm p-6"
        >
          <p class="text-sm text-slate-500">
            {{ stat.title }}
          </p>

          <h2 class="text-4xl font-bold text-slate-800 mt-3">
            {{ stat.value }}
          </h2>
        </div>
      </div>

      <!-- Revenue Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Daily Revenue</h3>

          <p class="text-4xl font-bold text-green-600 mt-4">$4,200</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Monthly Revenue</h3>

          <p class="text-4xl font-bold text-blue-600 mt-4">$58,400</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Restaurant Revenue</h3>

          <p class="text-4xl font-bold text-purple-600 mt-4">$12,850</p>
        </div>
      </div>

      <!-- Occupancy Monitoring -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Total Rooms</h3>

          <p class="text-5xl font-bold text-slate-800 mt-4">120</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Occupied Rooms</h3>

          <p class="text-5xl font-bold text-red-500 mt-4">98</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Available Rooms</h3>

          <p class="text-5xl font-bold text-green-500 mt-4">22</p>
        </div>
      </div>

      <!-- Reservation Summary -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Pending Reservations</h3>

          <p class="text-4xl font-bold text-yellow-500 mt-4">12</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Confirmed Reservations</h3>

          <p class="text-4xl font-bold text-green-500 mt-4">95</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Cancelled Reservations</h3>

          <p class="text-4xl font-bold text-red-500 mt-4">17</p>
        </div>
      </div>

      <!-- Performance Table -->
      <div class="bg-white rounded-xl border shadow-sm">
        <div class="px-6 py-4 border-b">
          <h2 class="text-lg font-semibold">Hotel Performance</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50">
              <tr>
                <th class="text-left p-4">Date</th>

                <th class="text-left p-4">Revenue</th>

                <th class="text-left p-4">Occupancy</th>

                <th class="text-left p-4">Reservations</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="item in performance" :key="item.date" class="border-t">
                <td class="p-4">
                  {{ item.date }}
                </td>

                <td class="p-4">
                  {{ item.revenue }}
                </td>

                <td class="p-4">
                  {{ item.occupancy }}
                </td>

                <td class="p-4">
                  {{ item.reservations }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
