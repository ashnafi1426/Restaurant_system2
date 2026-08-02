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
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200/60 bg-white/80 backdrop-blur-sm rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Finance & Revenue</h1>
            <p class="text-slate-600">Complete financial overview and analysis</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl flex items-center justify-center">
            <Wallet class="w-6 h-6 text-blue-600" />
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
      <div v-if="revenueStore.error && !revenueStore.loading" class="bg-red-50/80 backdrop-blur-sm border border-red-200/60 text-red-700 p-6 rounded-xl mb-6">
        {{ revenueStore.error }}
      </div>

      <!-- Content -->
      <div v-if="!revenueStore.loading" class="space-y-6">

        <!-- Revenue Overview Component -->
        <RevenueOverview />

        <!-- Financial Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-xl font-bold mb-6">Income Statement</h2>
            <div class="space-y-4">
              <div class="flex justify-between items-center pb-4 border-b border-slate-200">
                <span class="text-slate-600">Total Revenue</span>
                <span class="font-bold">{{ formatCurrency(revenueStore.revenueSummary?.thisMonth ?? 0) }}</span>
              </div>
              <div class="flex justify-between items-center pb-4 border-b border-slate-200">
                <span class="text-slate-600">Operating Expenses</span>
                <span class="font-bold text-red-600">-{{ formatCurrency(15000) }}</span>
              </div>
              <div class="flex justify-between items-center pb-4 border-b border-slate-200">
                <span class="text-slate-600">Staff Costs</span>
                <span class="font-bold text-red-600">-{{ formatCurrency(8000) }}</span>
              </div>
              <div class="flex justify-between items-center text-lg">
                <span class="font-bold">Net Profit</span>
                <span class="font-bold text-emerald-600">{{ formatCurrency(revenueStore.revenueSummary?.thisMonth ?? 0 - 23000) }}</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-xl font-bold mb-6">Key Metrics</h2>
            <div class="space-y-4">
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-slate-600">Profit Margin</span>
                  <span class="font-bold">42%</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500" style="width: 42%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-slate-600">Operating Ratio</span>
                  <span class="font-bold">58%</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-500" style="width: 58%"></div>
                </div>
              </div>
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-slate-600">Growth Rate</span>
                  <span class="font-bold text-emerald-600">+15%</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500" style="width: 15%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
          <h2 class="text-xl font-bold mb-6">Expense Breakdown</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <span>Staff Salaries</span>
              </div>
              <div class="text-right">
                <p class="font-bold">8,000 ETB</p>
                <p class="text-sm text-slate-500">35%</p>
              </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                <span>Utilities</span>
              </div>
              <div class="text-right">
                <p class="font-bold">4,500 ETB</p>
                <p class="text-sm text-slate-500">20%</p>
              </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                <span>Inventory & Supplies</span>
              </div>
              <div class="text-right">
                <p class="font-bold">3,500 ETB</p>
                <p class="text-sm text-slate-500">15%</p>
              </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                <span>Maintenance & Repairs</span>
              </div>
              <div class="text-right">
                <p class="font-bold">2,000 ETB</p>
                <p class="text-sm text-slate-500">10%</p>
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
