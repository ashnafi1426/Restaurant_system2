<script setup lang="ts">
import { computed } from 'vue'
import { Shirt, AlertCircle, CheckCircle2, Clock } from 'lucide-vue-next'
import { useManagerStore } from '@/stores/managerStore'

const manager = useManagerStore()

const laundryStats = computed(() => {
  const requests = manager.laundryRequests
  return {
    total: requests.length,
    pending: requests.filter((r) => r.status === 'pending').length,
    inProgress: requests.filter((r) => r.status === 'in_progress').length,
    completed: requests.filter((r) => r.status === 'completed').length,
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Laundry Management</h2>
        <p class="text-sm text-slate-500">Laundry requests and processing</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-pink-100 flex items-center justify-center">
        <Shirt class="w-6 h-6 text-pink-600" />
      </div>
    </div>

    <!-- STATISTICS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-blue-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Total Requests</p>
        <h3 class="text-3xl font-bold text-blue-700 mt-2">{{ laundryStats.total }}</h3>
      </div>

      <div class="bg-yellow-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Pending</p>
        <h3 class="text-3xl font-bold text-yellow-700 mt-2">{{ laundryStats.pending }}</h3>
      </div>

      <div class="bg-purple-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Processing</p>
        <h3 class="text-3xl font-bold text-purple-700 mt-2">{{ laundryStats.inProgress }}</h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Completed</p>
        <h3 class="text-3xl font-bold text-green-700 mt-2">{{ laundryStats.completed }}</h3>
      </div>
    </div>

    <!-- REQUESTS LIST -->
    <div class="space-y-3">
      <p class="text-sm font-medium text-slate-600">Pending Requests</p>

      <div
        v-for="request in manager.pendingLaundry.slice(0, 5)"
        :key="request.id"
        class="p-4 rounded-lg border border-slate-200 hover:border-pink-300 hover:bg-pink-50 transition"
      >
        <div class="flex items-start justify-between mb-2">
          <div>
            <p class="font-medium text-sm">Room {{ request.roomNumber }}</p>
            <p class="text-xs text-slate-500">{{ request.itemCount }} items</p>
          </div>

          <span
            :class="[
              'px-3 py-1 rounded-full text-xs font-medium',
              request.status === 'pending' && 'bg-yellow-100 text-yellow-700',
              request.status === 'in_progress' && 'bg-blue-100 text-blue-700',
              request.status === 'completed' && 'bg-green-100 text-green-700',
            ]"
          >
            {{ request.status.replace('_', ' ').toUpperCase() }}
          </span>
        </div>

        <div class="space-y-1 text-xs text-slate-600">
          <p v-if="request.itemDetails">Items: {{ request.itemDetails }}</p>
          <div class="flex items-center justify-between mt-2">
            <span>Priority: {{ request.priority?.toUpperCase() || 'NORMAL' }}</span>
            <div class="flex items-center gap-1">
              <Clock class="w-3 h-3" />
              <span>{{ request.estimatedCompletion || '--' }} hours</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="manager.pendingLaundry.length === 0" class="py-8 text-center text-slate-500">
        No pending laundry requests
      </div>
    </div>

    <!-- PROGRESS -->
    <div v-if="laundryStats.total > 0" class="mt-6 pt-4 border-t">
      <p class="text-xs text-slate-600 mb-2">Overall Progress</p>
      <div class="flex gap-1 h-2 rounded-full overflow-hidden bg-slate-100">
        <div
          class="bg-yellow-500"
          :style="{ width: `${(laundryStats.pending / laundryStats.total) * 100}%` }"
        ></div>
        <div
          class="bg-purple-500"
          :style="{ width: `${(laundryStats.inProgress / laundryStats.total) * 100}%` }"
        ></div>
        <div
          class="bg-green-500"
          :style="{ width: `${(laundryStats.completed / laundryStats.total) * 100}%` }"
        ></div>
      </div>
    </div>
  </section>
</template>
