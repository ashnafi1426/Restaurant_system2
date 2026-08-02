<script setup lang="ts">
import { onMounted } from 'vue'
import { useManagerRevenueStore } from '@/stores/manager/revenueStore'
import RevenueOverview from '@/components/manager/RevenueOverview.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { TrendingUp } from 'lucide-vue-next'

const revenueStore = useManagerRevenueStore()

onMounted(async () => {
  await revenueStore.initialize()
})

function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'ETB',
    maximumFractionDigits: 0,
  }).format(value)
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200/60 bg-white/80 backdrop-blur-sm rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Revenue Report</h1>
            <p class="text-slate-600">Comprehensive revenue analysis and breakdowns</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-emerald-100 to-emerald-50 rounded-xl flex items-center justify-center">
            <TrendingUp class="w-6 h-6 text-emerald-600" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="revenueStore.loading" class="flex justify-center items-center py-32">
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
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading revenue data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="revenueStore.error && !revenueStore.loading" class="bg-red-50/80 backdrop-blur-sm border border-red-200/60 text-red-700 p-6 rounded-xl mb-6">
        {{ revenueStore.error }}
      </div>

      <!-- Content -->
      <div v-if="!revenueStore.loading" class="space-y-6">
        
        <!-- Revenue Overview Component -->
        <RevenueOverview />

        <!-- Revenue Chart -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 mb-6">Revenue Trend</h2>
          <div class="h-64 flex items-center justify-center bg-slate-50 rounded-2xl">
            <p class="text-slate-500">Chart visualization coming soon...</p>
          </div>
        </div>

        <!-- Revenue Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <p class="text-sm text-slate-500">Today's Revenue</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900">
              {{ formatCurrency(revenueStore.revenueSummary?.today ?? 0) }}
            </h3>
            <p class="text-sm text-emerald-600 mt-2">+12% vs yesterday</p>
          </div>

          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <p class="text-sm text-slate-500">This Week</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900">
              {{ formatCurrency(revenueStore.revenueSummary?.thisWeek ?? 0) }}
            </h3>
            <p class="text-sm text-emerald-600 mt-2">+8% vs last week</p>
          </div>

          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <p class="text-sm text-slate-500">This Month</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900">
              {{ formatCurrency(revenueStore.revenueSummary?.thisMonth ?? 0) }}
            </h3>
            <p class="text-sm text-emerald-600 mt-2">+15% vs last month</p>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script lang="ts">
function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'ETB',
    maximumFractionDigits: 0,
  }).format(value)
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
