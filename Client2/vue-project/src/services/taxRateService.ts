import api from '../api/auth'
import { publicAxios } from './axios'
import type { TaxRate, TaxRateFormData } from '../types/taxRate'

export default {
  getTaxRates(params: any = {}) {
    return api.get('/tax-rates', { params })
  },

  getPublicTaxRates(params: any = {}) {
    return publicAxios.get('/tax-rates', { params })
  },

  getTaxRate(id: string) {
    return api.get(`/tax-rates/${id}`)
  },

  createTaxRate(data: TaxRateFormData) {
    return api.post('/tax-rates', data)
  },

  updateTaxRate(id: string, data: Partial<TaxRateFormData>) {
    return api.put(`/tax-rates/${id}`, data)
  },

  toggleTaxRateStatus(id: string) {
    return api.patch(`/tax-rates/${id}/toggle`)
  },

  deleteTaxRate(id: string) {
    return api.delete(`/tax-rates/${id}`)
  },
}
