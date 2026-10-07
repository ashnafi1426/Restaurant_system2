<script setup lang="ts">
import { onMounted, watch } from 'vue'
import { useManagerStore } from '@/stores/managerStore'
import { useMenuStore } from '@/stores/menuStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Package, Building2, BedDouble, Utensils, Wrench, Sparkles, Shirt } from 'lucide-vue-next'

const manager = useManagerStore()
const menuStore = useMenuStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const stats = computed(() => manager.safeStatistics)

const loadData = async () => {
  await Promise.allSettled([
    manager.loadStatistics(),
    menuStore.fetchStatistics(),
  ])
}

onMounted(loadData)
watch(() => hotelStore.hotelId, loadData)
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900 py-4 md:py-6 transition-colors duration-300">
      <!-- PAGE HEADER -->
      <div class="mb-6 md:mb-8 border-b border-slate-200/60 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-lg p-4 md:p-6 transition-colors duration-300">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">{{ languageStore.t('inventory_management', 'Property & Stock Inventory') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400">{{ languageStore.t('track_manage_inventory', 'Live inventory for room assets, restaurant stock, and service items') }}</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-amber-100 to-amber-50 dark:from-amber-900 dark:to-amber-800 rounded-xl flex items-center justify-center">
            <Package class="w-6 h-6 text-amber-600 dark:text-amber-400" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="manager.loading || menuStore.loading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12 mx-auto">
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
      <div v-if="!manager.loading && !menuStore.loading" class="space-y-6">

        <!-- Top Overview Cards (Real data from database) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('total_room_units', 'Total Room Assets') }}</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">
              {{ stats.totalRooms }}
            </h3>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">
              {{ stats.availableRooms }} {{ languageStore.t('available', 'available') }} • {{ stats.occupiedRooms }} {{ languageStore.t('occupied', 'occupied') }}
            </p>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('menu_stock_catalog', 'Menu Stock Catalog') }}</p>
            <h3 class="mt-3 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ menuStore.statistics.total_items }}
            </h3>
            <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">
              {{ menuStore.statistics.available_items }} {{ languageStore.t('available_in_kitchen', 'available in kitchen') }}
            </p>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ languageStore.t('maintenance_attention', 'Attention & Restock') }}</p>
            <h3 class="mt-3 text-3xl font-bold text-amber-600 dark:text-amber-400">
              {{ (menuStore.statistics.unavailable_items || 0) + (stats.maintenanceRooms || 0) }}
            </h3>
            <p class="text-sm text-amber-600 dark:text-amber-400 mt-2">
              {{ menuStore.statistics.unavailable_items || 0 }} {{ languageStore.t('out_of_stock_items', 'out of stock items') }} • {{ stats.maintenanceRooms || 0 }} {{ languageStore.t('rooms_in_repair', 'rooms under repair') }}
            </p>
          </div>
        </div>

        <!-- Inventory by Category (Real Database Categories) -->
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">{{ languageStore.t('inventory_by_category', 'Inventory by Department') }}</h2>
          <div class="space-y-4">
            <!-- Room Inventory -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <BedDouble class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <div>
                  <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('room_assets', 'Room Accommodations') }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ stats.availableRooms }} {{ languageStore.t('ready_for_guests', 'ready for guests') }}</p>
                </div>
              </div>
              <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ stats.totalRooms }} {{ languageStore.t('rooms', 'Rooms') }}</span>
            </div>

            <!-- Restaurant Menu Items -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <Utensils class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                <div>
                  <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('restaurant_menu_items', 'Restaurant Menu Catalog') }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ menuStore.statistics.available_items }} {{ languageStore.t('in_stock', 'in stock') }}</p>
                </div>
              </div>
              <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ menuStore.statistics.total_items }} {{ languageStore.t('items', 'Items') }}</span>
            </div>

            <!-- Maintenance Status -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <Wrench class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                <div>
                  <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('maintenance_units', 'Units in Maintenance') }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ languageStore.t('offline_for_repair', 'Offline for repairs') }}</p>
                </div>
              </div>
              <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ stats.maintenanceRooms }} {{ languageStore.t('rooms', 'Rooms') }}</span>
            </div>

            <!-- Housekeeping Queue -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <Sparkles class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                <div>
                  <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('housekeeping_queue', 'Rooms Awaiting Cleaning') }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ languageStore.t('turnover_required', 'Turnover service required') }}</p>
                </div>
              </div>
              <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ stats.pendingHousekeeping }} {{ languageStore.t('rooms', 'Rooms') }}</span>
            </div>

            <!-- Laundry in Processing -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl">
              <div class="flex items-center gap-3">
                <Shirt class="w-5 h-5 text-sky-600 dark:text-sky-400" />
                <div>
                  <p class="font-semibold text-slate-900 dark:text-slate-100">{{ languageStore.t('active_laundry_requests', 'Active Laundry in Service') }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ languageStore.t('in_cleaning_process', 'In cleaning cycle') }}</p>
                </div>
              </div>
              <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ stats.pendingLaundry }} {{ languageStore.t('requests', 'Requests') }}</span>
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
