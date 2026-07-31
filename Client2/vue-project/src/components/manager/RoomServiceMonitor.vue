<script setup lang="ts">
import { computed } from 'vue'
import { Truck, MapPin, User, Clock } from 'lucide-vue-next'
import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const deliveryStats = computed(() => {
  return {
    total: manager.deliveries.length,
    active: manager.activeDeliveries.length,
    completed: manager.deliveries.filter((d) => d.status === 'delivered').length,
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Room Service</h2>
        <p class="text-sm text-slate-500">Active deliveries tracking</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-teal-100 flex items-center justify-center">
        <Truck class="w-6 h-6 text-teal-600" />
      </div>
    </div>

    <!-- STATISTICS -->
    <div class="grid grid-cols-3 gap-4 mb-8">
      <div class="bg-blue-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Total</p>
        <h3 class="text-3xl font-bold text-blue-700 mt-2">{{ deliveryStats.total }}</h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Active</p>
        <h3 class="text-3xl font-bold text-green-700 mt-2">{{ deliveryStats.active }}</h3>
      </div>

      <div class="bg-emerald-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Completed</p>
        <h3 class="text-3xl font-bold text-emerald-700 mt-2">{{ deliveryStats.completed }}</h3>
      </div>
    </div>

    <!-- ACTIVE DELIVERIES -->
    <div class="space-y-3">
      <p class="text-sm font-medium text-slate-600">Active Deliveries</p>

      <div
        v-for="delivery in manager.activeDeliveries.slice(0, 4)"
        :key="delivery.id"
        class="p-4 rounded-lg border border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="font-medium text-sm">Room {{ delivery.roomNumber }}</p>
            <p class="text-xs text-slate-500">Guest: {{ delivery.guestName }}</p>
          </div>
          <span
            :class="[
              'px-3 py-1 rounded-full text-xs font-medium',
              delivery.status === 'pending' && 'bg-yellow-100 text-yellow-700',
              delivery.status === 'in_transit' && 'bg-blue-100 text-blue-700',
              delivery.status === 'delivered' && 'bg-green-100 text-green-700',
            ]"
          >
            {{ delivery.status.replace('_', ' ').toUpperCase() }}
          </span>
        </div>

        <div class="space-y-2 text-xs text-slate-600">
          <div class="flex items-center gap-2">
            <Truck class="w-4 h-4" />
            <span>{{ delivery.items }}</span>
          </div>

          <div class="flex items-center gap-2">
            <User class="w-4 h-4" />
            <span>Waiter: {{ delivery.waiterName || 'Unassigned' }}</span>
          </div>

          <div class="flex items-center gap-2">
            <Clock class="w-4 h-4" />
            <span>ETA: {{ delivery.estimatedTime || '--' }} min</span>
          </div>
        </div>
      </div>

      <div v-if="manager.activeDeliveries.length === 0" class="py-6 text-center text-slate-500">
        No active deliveries
      </div>
    </div>
  </section>
</template>
