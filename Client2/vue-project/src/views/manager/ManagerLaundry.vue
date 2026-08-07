<script setup lang="ts">
import { onMounted } from 'vue'
import { useManagerOperationsStore } from '@/stores/manager/operationsStore'
import LaundryMonitor from '@/components/manager/LaundryMonitor.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Shirt } from 'lucide-vue-next'

const operationsStore = useManagerOperationsStore()

onMounted(async () => {
  await operationsStore.loadLaundry()
})

function calculateCompletionRate() {
  if (operationsStore.laundryRequests.length === 0) return 0
  const completed = operationsStore.laundryRequests.filter(item => item.status === 'completed').length
  return Math.round((completed / operationsStore.laundryRequests.length) * 100)
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900 py-4 md:py-6 transition-colors duration-300">
      <!-- PAGE HEADER -->
      <div class="mb-6 md:mb-8 border-b border-slate-200/60 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-lg p-4 md:p-6 transition-colors duration-300">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">Laundry Management</h1>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400">Monitor and manage all laundry requests</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-50 dark:from-purple-900 dark:to-purple-800 rounded-xl flex items-center justify-center">
            <Shirt class="w-6 h-6 text-purple-600 dark:text-purple-400" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="operationsStore.loading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
            </svg>
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
              </svg>
            </div>
          </div>
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading laundry data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="operationsStore.error && !operationsStore.loading" class="bg-red-50/80 dark:bg-red-900/20 backdrop-blur-sm border border-red-200/60 dark:border-red-800 text-red-700 dark:text-red-400 p-6 rounded-xl mb-6">
        {{ operationsStore.error }}
      </div>

      <!-- Content -->
      <div v-if="!operationsStore.loading" class="space-y-6">

        <!-- Laundry Monitor Component -->
        <LaundryMonitor />

        <!-- Laundry Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Requests</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">
              {{ operationsStore.laundryRequests.length }}
            </h3>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Pending Requests</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">
              {{ operationsStore.pendingLaundry.length }}
            </h3>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Completion Rate</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">
              {{ calculateCompletionRate() }}%
            </h3>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script lang="ts">
function calculateCompletionRate() {
  const manager = useManagerStore()
  if (manager.laundryRequests.length === 0) return 0
  const completed = manager.laundryRequests.filter(item => item.status === 'completed').length
  return Math.round((completed / manager.laundryRequests.length) * 100)
}
</script>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>
