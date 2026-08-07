<script setup lang="ts">
import { onMounted } from 'vue'
import { useManagerRevenueStore } from '@/stores/manager/revenueStore'
import RevenueOverview from '@/components/manager/RevenueOverview.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Wallet } from 'lucide-vue-next'

const revenueStore = useManagerRevenueStore()

onMounted(async () => {
  await revenueStore.loadSummary()
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
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900 py-4 md:py-6 transition-colors duration-300">
      <!-- PAGE HEADER -->
      <div class="mb-6 md:mb-8 border-b border-slate-200/60 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-lg p-4 md:p-6 transition-colors duration-300">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">Finance & Revenue</h1>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400">Complete financial overview and analysis</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900 dark:to-blue-800 rounded-xl flex items-center justify-center">
            <Wallet class="w-6 h-6 text-blue-600 dark:text-blue-400" />
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
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading financial data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="revenueStore.error && !revenueStore.loading" class="bg-red-50/80 dark:bg-red-900/20 backdrop-blur-sm border border-red-200/60 dark:border-red-800/60 text-red-700 dark:text-red-400 p-6 rounded-xl mb-6">
        {{ revenueStore.error }}
      </div>

      <!-- Content -->
      <div v-if="!revenueStore.loading" class="space-y-6">

        <!-- Revenue Overview Component -->
        <RevenueOverview />

        <!-- Financial Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">Income Statement</h2>
            <div class="space-y-4">
              <div class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-600 dark:text-slate-400">Total Revenue</span>
                <span class="font-bold text-slate-900 dark:text-slate-100">{{ formatCurrency(revenueStore.revenueSummary?.thisMonth ?? 0) }}</span>
              </div>
              <div class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-600 dark:text-slate-400">Operating Expenses</span>
                <span class="font-bold text-red-600 dark:text-red-400">-{{ formatCurrency(15000) }}</span>
              </div>
              <div class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700">
                <span class="text-slate-600 dark:text-slate-400">Staff Costs</span>
                <span class="font-bold text-red-600 dark:text-red-400">-{{ formatCurrency(8000) }}</span>
              </div>
              <div class="flex justify-between items-center text-lg">
                <span class="font-bold text-slate-900 dark:text-slate-100">Net Profit</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(revenueStore.revenueSummary?.thisMonth ?? 0 - 23000) }}</span>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">Key Metrics</h2>
            <div class="space-y-4">
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-slate-600 dark:text-slate-400">Profit Margin</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100">42%</span>
                </div>
                <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500 dark:bg-emerald-400" style="width: 42%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-slate-600 dark:text-slate-400">Operating Ratio</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100">58%</span>
                </div>
                <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-500 dark:bg-blue-400" style="width: 58%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-slate-600 dark:text-slate-400">Growth Rate</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400">+15%</span>
                </div>
                <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500 dark:bg-emerald-400" style="width: 15%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">Expense Breakdown</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-red-500 dark:bg-red-400"></div>
                <span class="text-slate-900 dark:text-slate-100">Staff Salaries</span>
              </div>
              <div class="text-right">
                <p class="font-bold text-slate-900 dark:text-slate-100">8,000 ETB</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">35%</p>
              </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-blue-500 dark:bg-blue-400"></div>
                <span class="text-slate-900 dark:text-slate-100">Utilities</span>
              </div>
              <div class="text-right">
                <p class="font-bold text-slate-900 dark:text-slate-100">4,500 ETB</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">20%</p>
              </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-amber-500 dark:bg-amber-400"></div>
                <span class="text-slate-900 dark:text-slate-100">Inventory & Supplies</span>
              </div>
              <div class="text-right">
                <p class="font-bold text-slate-900 dark:text-slate-100">3,500 ETB</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">15%</p>
              </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-purple-500 dark:bg-purple-400"></div>
                <span class="text-slate-900 dark:text-slate-100">Maintenance & Repairs</span>
              </div>
              <div class="text-right">
                <p class="font-bold text-slate-900 dark:text-slate-100">2,000 ETB</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">10%</p>
              </div>
            </div>
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
