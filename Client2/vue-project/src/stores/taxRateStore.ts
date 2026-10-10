import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import taxRateService from '@/services/taxRateService'
import type { TaxRate, TaxRateFormData } from '@/types/taxRate'

export const useTaxRateStore = defineStore('taxRate', () => {
  const taxRates = ref<TaxRate[]>([])
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)

  const activeTaxRates = computed(() => taxRates.value.filter((t) => t.is_active))
  const defaultTaxRate = computed(
    () =>
      taxRates.value.find((t) => t.is_default && t.is_active) ||
      taxRates.value.find((t) => t.is_active) ||
      null,
  )

  async function withSaving<T>(action: () => Promise<T>, fallbackMessage: string): Promise<T> {
    saving.value = true
    error.value = null
    try {
      return await action()
    } catch (err: any) {
      console.error(`[taxRateStore] ${fallbackMessage}:`, err)
      error.value = err.response?.data?.message || fallbackMessage
      throw err
    } finally {
      saving.value = false
    }
  }

  async function fetchTaxRates(params: any = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await taxRateService.getTaxRates(params)
      if (response.data && response.data.data) {
        taxRates.value = response.data.data
      } else if (Array.isArray(response.data)) {
        taxRates.value = response.data
      } else {
        taxRates.value = []
      }
    } catch (err: any) {
      console.error('[taxRateStore] Failed to fetch tax rates:', err)
      error.value = err.response?.data?.message || 'Failed to load tax rates'
      taxRates.value = []
    } finally {
      loading.value = false
    }
  }

  function createTaxRate(data: TaxRateFormData) {
    return withSaving(async () => {
      const response = await taxRateService.createTaxRate(data)
      await fetchTaxRates()
      return response.data
    }, 'Failed to create tax rate')
  }

  function updateTaxRate(id: string, data: Partial<TaxRateFormData>) {
    return withSaving(async () => {
      const response = await taxRateService.updateTaxRate(id, data)
      await fetchTaxRates()
      return response.data
    }, 'Failed to update tax rate')
  }

  async function toggleStatus(id: string) {
    try {
      const response = await taxRateService.toggleTaxRateStatus(id)
      const updated = response.data?.data
      if (updated) {
        const index = taxRates.value.findIndex((t) => t.id === id)
        if (index !== -1) {
          taxRates.value[index] = updated
        }
      } else {
        await fetchTaxRates()
      }
      return response.data
    } catch (err: any) {
      console.error('[taxRateStore] Failed to toggle tax rate status:', err)
      throw err
    }
  }

  function deleteTaxRate(id: string) {
    return withSaving(async () => {
      await taxRateService.deleteTaxRate(id)
      taxRates.value = taxRates.value.filter((t) => t.id !== id)
    }, 'Failed to delete tax rate')
  }

  return {
    taxRates,
    activeTaxRates,
    defaultTaxRate,
    loading,
    saving,
    error,
    fetchTaxRates,
    createTaxRate,
    updateTaxRate,
    toggleStatus,
    deleteTaxRate,
  }
})
