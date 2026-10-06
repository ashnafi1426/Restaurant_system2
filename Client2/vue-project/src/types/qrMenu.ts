export interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  total_price?: number
  base_price?: number
  tax_amount?: number
  tax_rate?: {
    id?: string | number
    name?: string
    rate: number
    is_active?: boolean
  } | null
  tax_included?: boolean
  image: string | null
  category: string
  rating?: number
  badge?: string
  dietary?: string[]
  calories?: number
  preparationTime?: number
  is_available?: boolean
}

export interface CartItem extends MenuItem {
  quantity: number
}

export interface OrderContext {
  type: 'room' | 'table'
  id: string
  displayName: string
  paymentOptions: Array<{ value: string; label: string }>
  isCheckedIn?: boolean
  canOrder?: boolean
  reservationStatus?: string
  eligibilityMessage?: string
}

export interface PaymentForm {
  first_name: string
  last_name: string
  email: string
  phone: string
}
