import { ref, computed, type Ref, type ComputedRef } from 'vue'
import type { MenuItem, CartItem } from '@/types/qrMenu'

export function useQRMenuCart() {
  const cartItems: Ref<CartItem[]> = ref([])

  const subtotal: ComputedRef<number> = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.price * item.quantity, 0)
  })

  const tax: ComputedRef<number> = computed(() => {
    return subtotal.value * 0.15
  })

  const serviceCharge: ComputedRef<number> = computed(() => {
    return subtotal.value * 0.1
  })

  const cartTotal: ComputedRef<number> = computed(() => {
    return subtotal.value + tax.value + serviceCharge.value
  })

  const addToCart = (item: MenuItem, quantity: number = 1) => {
    const existingItem = cartItems.value.find((ci) => ci.id === item.id)
    if (existingItem) {
      existingItem.quantity += quantity
      console.log(`[useQRMenuCart] Increased ${item.name} quantity to ${existingItem.quantity}`)
    } else {
      cartItems.value.push({ ...item, quantity })
      console.log(`[useQRMenuCart] Added ${item.name} to cart with quantity ${quantity}`)
    }
  }

  const removeFromCart = (itemId: string | number) => {
    const item = cartItems.value.find((i) => i.id === itemId)
    cartItems.value = cartItems.value.filter((item) => item.id !== itemId)
    console.log(`[useQRMenuCart] Removed item ${itemId} from cart`)
  }

  const incrementQuantity = (itemId: string | number) => {
    const item = cartItems.value.find((i) => i.id === itemId)
    if (item) {
      const oldQuantity = item.quantity
      item.quantity++
      console.log(`[useQRMenuCart] Increased ${item.name} quantity from ${oldQuantity} to ${item.quantity}`)
    }
  }

  const decrementQuantity = (itemId: string | number) => {
    const item = cartItems.value.find((i) => i.id === itemId)
    if (item && item.quantity > 1) {
      const oldQuantity = item.quantity
      item.quantity--
      console.log(`[useQRMenuCart] Decreased ${item.name} quantity from ${oldQuantity} to ${item.quantity}`)
    } else if (item) {
      console.log(`[useQRMenuCart] Removing ${item.name} from cart (quantity was 1)`)
      removeFromCart(itemId)
    }
  }

  const clearCart = () => {
    console.log(`[useQRMenuCart] Clearing cart (${cartItems.value.length} items)`)
    cartItems.value = []
  }

  const formatPrice = (price: number): string => {
    return `ETB ${price.toFixed(2)}`
  }

  return {
    cartItems,
    subtotal,
    tax,
    serviceCharge,
    cartTotal,
    addToCart,
    removeFromCart,
    incrementQuantity,
    decrementQuantity,
    clearCart,
    formatPrice,
  }
}
