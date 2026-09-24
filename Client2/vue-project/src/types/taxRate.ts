export interface TaxRate {
  id: string
  hotel_id?: string
  name: string
  code: string
  type: 'percentage' | 'fixed'
  rate: number
  applies_to: 'all' | 'food' | 'beverage' | 'room' | 'service'
  is_active: boolean
  is_default: boolean
  description?: string
  created_at?: string
  updated_at?: string
}

export interface TaxRateFormData {
  name: string
  code: string
  type: 'percentage' | 'fixed'
  rate: number
  applies_to: 'all' | 'food' | 'beverage' | 'room' | 'service'
  is_active: boolean
  is_default: boolean
  description?: string
}
