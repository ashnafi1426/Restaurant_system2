<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useManagerStore } from '@/stores/managerStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Package, Building2 } from 'lucide-vue-next'

const manager = useManagerStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loadData = async () => {
  await manager.loadStatistics()
}

onMounted(loadData)

watch(() => hotelStore.hotelId, loadData)

const categories = [
  { key: 'linens_textiles', fallback: 'Linens & Textiles', items: 450, utilization: 85 },
  { key: 'kitchen_supplies', fallback: 'Kitchen Supplies', items: 320, utilization: 72 },
  { key: 'toiletries', fallback: 'Toiletries', items: 280, utilization: 90 },
  { key: 'cleaning_materials', fallback: 'Cleaning Materials', items: 200, utilization: 65 },
]

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
            <div class="flex items-center gap-2">
              <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">{{ languageStore.t('inventory_management', 'Inventory Management') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400">{{ languageStore.t('track_manage_inventory', 'Track and manage hotel and restaurant inventory') }}</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-amber-100 to-amber-50 dark:from-amber-900 dark:to-amber-800 rounded-xl flex items-center justify-center">
            <Package class="w-6 h-6 text-amber-600 dark:text-amber-400" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="manager.loading" class="flex justify-center items-center py-32">
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
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">{{ languageStore.t('loading_inventory_data', 'Loading inventory data...') }}</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="manager.error && !manager.loading" class="bg-red-50/80 dark:bg-red-900/20 backdrop-blur-sm border border-red-200/60 dark:border-red-800 text-red-700 dark:text-red-400 p-6 rounded-xl mb-6">
        {{ manager.error }}
      </div>

      <!-- Content -->
      <div v-if="!manager.loading" class="space-y-6">

        <!-- Inventory Status -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('total_items', 'Total Items') }}</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">1,250</h3>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">{{ languageStore.t('in_stock_all_categories', 'In stock across all categories') }}</p>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('low_stock_items', 'Low Stock Items') }}</p>
            <h3 class="mt-3 text-3xl font-bold text-amber-600 dark:text-amber-400">23</h3>
            <p class="text-sm text-amber-600 dark:text-amber-400 mt-2">{{ languageStore.t('require_urgent_reorder', 'Require urgent reorder') }}</p>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('stock_value', 'Stock Value') }}</p>
            <h3 class="mt-3 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(45000) }}</h3>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">{{ languageStore.t('total_inventory_value', 'Total inventory value') }}</p>
          </div>
        </div>

        <!-- Categories -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">{{ languageStore.t('inventory_by_category', 'Inventory by Category') }}</h2>
          <div class="space-y-4">
            <div v-for="category in categories" :key="category.key" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t(category.key, category.fallback) }}</span>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ category.items }} {{ languageStore.t('items', 'items') }}</span>
              </div>
              <div class="flex items-center gap-4">
                <div class="w-32 h-2 bg-slate-200 dark:bg-slate-600 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-600 dark:bg-blue-400" :style="{ width: category.utilization + '%' }"></div>
                </div>
                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ category.utilization }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">{{ languageStore.t('recent_inventory_movements', 'Recent Inventory Movements') }}</h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('linen_stock_added', 'Linen Stock - Added') }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('today_at', 'Today at') }} 10:30 AM</p>
              </div>
              <span class="text-emerald-600 dark:text-emerald-400 font-semibold">+150 {{ languageStore.t('units', 'units') }}</span>
            </div>
            <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('kitchen_supplies_used', 'Kitchen Supplies - Used') }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('today_at', 'Today at') }} 9:15 AM</p>
              </div>
              <span class="text-red-600 dark:text-red-400 font-semibold">-45 {{ languageStore.t('units', 'units') }}</span>
            </div>
            <div class="flex items-center justify-between p-4">
              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('toiletries_added', 'Toiletries - Added') }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('yesterday_at', 'Yesterday at') }} 3:20 PM</p>
              </div>
              <span class="text-emerald-600 dark:text-emerald-400 font-semibold">+200 {{ languageStore.t('units', 'units') }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

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
