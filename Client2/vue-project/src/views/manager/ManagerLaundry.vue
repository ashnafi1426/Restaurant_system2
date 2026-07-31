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
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200/60 bg-white/80 backdrop-blur-sm rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Laundry Management</h1>
            <p class="text-slate-600">Monitor and manage all laundry requests</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl flex items-center justify-center">
            <Shirt class="w-6 h-6 text-purple-600" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="operationsStore.loading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
          <p class="text-slate-600">Loading laundry data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="operationsStore.error && !operationsStore.loading" class="bg-red-50/80 backdrop-blur-sm border border-red-200/60 text-red-700 p-6 rounded-xl mb-6">
        {{ operationsStore.error }}
      </div>

      <!-- Content -->
      <div v-if="!operationsStore.loading" class="space-y-6">

        <!-- Laundry Monitor Component -->
        <LaundryMonitor />

        <!-- Laundry Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <p class="text-sm text-slate-500">Total Requests</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900">
              {{ operationsStore.laundryRequests.length }}
            </h3>
          </div>
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <p class="text-sm text-slate-500">Pending Requests</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900">
              {{ operationsStore.pendingLaundry.length }}
            </h3>
          </div>
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <p class="text-sm text-slate-500">Completion Rate</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900">
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
