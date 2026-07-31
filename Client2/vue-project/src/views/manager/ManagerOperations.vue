<script setup lang="ts">
import { onMounted } from 'vue'
import { useManagerOperationsStore } from '@/stores/manager/operationsStore'
import ManagerHeader from '@/components/manager/managerHeader.vue'
import RestaurantMonitor from '@/components/manager/RestaurantMonitor.vue'
import RoomServiceMonitor from '@/components/manager/RoomServiceMonitor.vue'
import LaundryMonitor from '@/components/manager/LaundryMonitor.vue'
import HousekeepingMonitor from '@/components/manager/HousekeepingMonitor.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'

const operationsStore = useManagerOperationsStore()

onMounted(async () => {
  await operationsStore.initialize()
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- Welcome Header -->
      <div class="mb-8 px-6 pt-6">
        <div>
          <h1 class="text-4xl font-bold text-slate-900">Daily Operations</h1>
          <p class="text-slate-600 mt-2">Real-time monitoring of all hotel operations</p>
        </div>
      </div>

      <div class="px-6">
      <div v-if="operationsStore.loading" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-if="operationsStore.error" class="bg-red-50 border border-red-200 text-red-700 p-5 rounded-xl">
        {{ operationsStore.error }}
      </div>

      <!-- Content -->
      <div v-if="!operationsStore.loading" class="space-y-6">

        <!-- Restaurant & Room Service -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <RestaurantMonitor />
          <RoomServiceMonitor />
        </div>

        <!-- Laundry & Housekeeping -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <LaundryMonitor />
          <HousekeepingMonitor />
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-sm text-slate-500">Pending Orders</p>
            <h3 class="mt-2 text-2xl font-bold">{{ operationsStore.pendingOrders.length }}</h3>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-sm text-slate-500">Preparing Orders</p>
            <h3 class="mt-2 text-2xl font-bold">{{ operationsStore.preparingOrders.length }}</h3>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-sm text-slate-500">Active Deliveries</p>
            <h3 class="mt-2 text-2xl font-bold">{{ operationsStore.activeDeliveries.length }}</h3>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-sm text-slate-500">Pending Laundry</p>
            <h3 class="mt-2 text-2xl font-bold">{{ operationsStore.pendingLaundry.length }}</h3>
          </div>
        </div>
      </div>
    </div>
    </div>
  </DashboardLayout>
</template>
