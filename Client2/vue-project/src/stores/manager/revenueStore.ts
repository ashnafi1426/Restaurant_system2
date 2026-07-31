import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import managerService from '@/services/managerService'
import type { RevenueSummary, RevenueChartItem } from '@/types/manager'

export const useManagerRevenueStore = defineStore('managerRevenue', () => {
  /*
  |--------------------------------------------------------------------------
  | STATE
  |--------------------------------------------------------------------------
  */

  const revenueSummary = ref<RevenueSummary | null>(null)
  const revenueChart = ref<RevenueChartItem[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  /*
  |--------------------------------------------------------------------------
  | COMPUTED
  |--------------------------------------------------------------------------
  */

  const revenue = computed(() => ({
    today: revenueSummary.value?.today ?? 0,
    week: revenueSummary.value?.thisWeek ?? 0,
    month: revenueSummary.value?.thisMonth ?? 0,
    rooms: 0,
    restaurant: 0,
    roomService: 0,
    laundry: 0,
  }))

  /*
  |--------------------------------------------------------------------------
  | ACTIONS
  |--------------------------------------------------------------------------
  */

  async function loadSummary() {
    try {
      loading.value = true
      error.value = null
      revenueSummary.value = await managerService.getRevenueSummary()
    } finally {
      loading.value = false
    }
  }

  async function loadChart(period: 'weekly' | 'monthly' | 'yearly' = 'monthly') {
    try {
      revenueChart.value = await managerService.getRevenueChart(period)
    } catch (err: any) {
      error.value = err.message
    }
  }

  async function initialize() {
    await Promise.all([loadSummary(), loadChart()])
  }

  function reset() {
    revenueSummary.value = null
    revenueChart.value = []
    error.value = null
  }

  return {
    revenueSummary,
    revenueChart,
    loading,
    error,
    revenue,
    loadSummary,
    loadChart,
    initialize,
    reset,
  }
})
