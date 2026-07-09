<script setup lang="ts">
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const stats = [
  {
    title: 'Orders Today',
    value: 48,
  },
  {
    title: 'Pending Orders',
    value: 12,
  },
  {
    title: 'Preparing',
    value: 8,
  },
  {
    title: 'Served',
    value: 28,
  },
]

const orders = [
  {
    id: '#ORD-1001',
    room: '201',
    item: 'Chicken Burger',
    quantity: 2,
    status: 'Pending',
  },
  {
    id: '#ORD-1002',
    room: '305',
    item: 'Pizza',
    quantity: 1,
    status: 'Preparing',
  },
  {
    id: '#ORD-1003',
    room: '102',
    item: 'Pasta',
    quantity: 3,
    status: 'Served',
  },
]
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-3xl font-bold text-slate-800">Chef Dashboard</h1>

        <p class="text-slate-500 mt-1">Manage kitchen orders and food preparation.</p>
      </div>

      <!-- Statistics -->
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

      <!-- Quick Actions -->
      <div class="bg-white rounded-xl border shadow-sm p-6">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button class="bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg">
            View Orders
          </button>

          <button class="bg-yellow-500 hover:bg-yellow-600 text-white py-3 rounded-lg">
            Start Preparing
          </button>

          <button class="bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg">
            Update Status
          </button>

          <button class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg">
            Mark Served
          </button>
        </div>
      </div>

      <!-- Kitchen Status -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Pending</h3>

          <p class="text-5xl font-bold text-red-500 mt-4">12</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Preparing</h3>

          <p class="text-5xl font-bold text-yellow-500 mt-4">8</p>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6">
          <h3 class="font-semibold text-slate-700">Served</h3>

          <p class="text-5xl font-bold text-green-500 mt-4">28</p>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-xl border shadow-sm">
        <div class="px-6 py-4 border-b">
          <h2 class="text-lg font-semibold">Recent Food Orders</h2>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50">
              <tr>
                <th class="text-left p-4">Order ID</th>

                <th class="text-left p-4">Room</th>

                <th class="text-left p-4">Food Item</th>

                <th class="text-left p-4">Qty</th>

                <th class="text-left p-4">Status</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="order in orders" :key="order.id" class="border-t">
                <td class="p-4">
                  {{ order.id }}
                </td>

                <td class="p-4">
                  {{ order.room }}
                </td>

                <td class="p-4">
                  {{ order.item }}
                </td>

                <td class="p-4">
                  {{ order.quantity }}
                </td>

                <td class="p-4">
                  <span
                    v-if="order.status === 'Pending'"
                    class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm"
                  >
                    Pending
                  </span>

                  <span
                    v-else-if="order.status === 'Preparing'"
                    class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm"
                  >
                    Preparing
                  </span>

                  <span v-else class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                    Served
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
