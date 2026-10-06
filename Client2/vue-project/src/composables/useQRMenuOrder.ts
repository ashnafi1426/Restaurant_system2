import { ref, type Ref } from 'vue'
import type { Router } from 'vue-router'
import type { CartItem, OrderContext, PaymentForm } from '@/types/qrMenu'
import { unifiedOrderService } from '@/services/unifiedOrderService'

export function useQRMenuOrder() {
  const isPlacingOrder: Ref<boolean> = ref(false)
  const orderNumber: Ref<string> = ref('')
  const estimatedTime: Ref<number> = ref(30)
  const showSuccessModal: Ref<boolean> = ref(false)

  const placeOrderWithRoomCharge = async (
    cartItems: CartItem[],
    subtotal: number,
    tax: number,
    serviceCharge: number,
    total: number,
    qrToken: string,
    orderContext: OrderContext | null,
    router: Router
  ) => {
    if (isPlacingOrder.value) return
    if (cartItems.length === 0) {
      alert('Your cart is empty')
      return
    }

    if (!orderContext) {
      alert('Order context not loaded. Please refresh the page.')
      return
    }

    isPlacingOrder.value = true

    try {
      const orderItems = cartItems.map((item) => ({
        menu_item_id: String(item.id),
        quantity: item.quantity,
      }))

      const orderResponse = await unifiedOrderService.createOrder({
        qr_token: qrToken,
        items: orderItems,
        special_requests: '',
        payment_type: 'room_charge',
      })

      if (orderResponse && orderResponse.success && orderResponse.data) {
        const createdOrderId = orderResponse.data.id || orderResponse.data.order_id
        orderNumber.value = orderResponse.data.order_number
        estimatedTime.value = 30
        
        // Build complete order data object with items for OrderStatusPage
        const completeOrderData = {
          ...orderResponse.data,
          id: createdOrderId,
          order_id: createdOrderId,
          items: cartItems.map(item => ({
            id: item.id,
            name: item.name,
            description: item.description,
            quantity: item.quantity,
            price: item.price,
            image: item.image,
            total: item.price * item.quantity
          })),
          subtotal: subtotal,
          tax: tax,
          service_charge: serviceCharge,
          total: total,
          status: 'pending',
          payment_status: 'pending',
          payment_type: 'room_charge',
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        }
        
        // Store order data for OrderStatusPage
        if (orderResponse.data.hotel_id) {
          localStorage.setItem('hotel_id', orderResponse.data.hotel_id)
        }
        if (qrToken) {
          localStorage.setItem('guest_qr_token', qrToken)
        }
        
        // Store the complete order data for immediate display
        console.log('[useQRMenuOrder] Storing complete order data:', completeOrderData)
        console.log('[useQRMenuOrder] Created order ID:', createdOrderId)
        localStorage.setItem('pending_order_data', JSON.stringify(completeOrderData))
        
        // Verify it was stored
        const verifyStored = localStorage.getItem('pending_order_data')
        console.log('[useQRMenuOrder] Verified stored data:', verifyStored ? 'Success ✅' : 'Failed ❌')
        
        // Redirect to real-time Order Status page with all necessary params
        console.log('[useQRMenuOrder] Redirecting to order status with ID:', createdOrderId)
        router.push({
          name: 'order-status',
          params: { orderId: createdOrderId },
          query: { 
            hotel_id: orderResponse.data.hotel_id,
            qr_token: qrToken,
            order_number: orderResponse.data.order_number
          }
        })
        return
      } else {
        throw new Error(orderResponse.message || 'Failed to place order')
      }
    } catch (error: any) {
      console.error('[useQRMenuOrder] Error placing order with room charge:', error)
      alert(error.message || 'Failed to place order. Please try again.')
    } finally {
      isPlacingOrder.value = false
    }
  }

  const initializeWalkInPayment = async (
    cartItems: CartItem[],
    qrToken: string,
    orderContext: OrderContext,
    paymentForm: PaymentForm
  ) => {
    if (isPlacingOrder.value) return
    if (cartItems.length === 0) {
      alert('Your cart is empty')
      return
    }

    isPlacingOrder.value = true

    try {
      const orderItems = cartItems.map((item) => ({
        menu_item_id: String(item.id),
        quantity: item.quantity,
      }))

      const paymentResponse = await unifiedOrderService.initializeWalkInPayment({
        table_id: orderContext.id,
        qr_token: qrToken,
        items: orderItems,
        special_requests: '',
        first_name: paymentForm.first_name,
        last_name: paymentForm.last_name,
        email: paymentForm.email,
        phone: paymentForm.phone,
      })

      if (paymentResponse.success && paymentResponse.checkout_url) {
        // Build payment data object with all necessary fields
        const paymentData = {
          payment_id: paymentResponse.payment_id,
          tx_ref: paymentResponse.tx_ref,
          amount: paymentResponse.amount,
          qr_token: qrToken,
          table_number: orderContext.displayName,
          items: cartItems.map(item => ({
            name: item.name,
            quantity: item.quantity,
            price: item.price,
            total: item.price * item.quantity,
          })),
          calculation: paymentResponse.calculation,
        }
        
        // Store to BOTH localStorage AND sessionStorage for persistence through Chapa redirect
        localStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
        sessionStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
        
        console.log('[useQRMenuOrder] Stored payment data before redirect:', paymentData)
        console.log('[useQRMenuOrder] Redirecting to Chapa:', paymentResponse.checkout_url)
        
        window.location.href = paymentResponse.checkout_url
        return
      } else {
        throw new Error(paymentResponse.message || 'Failed to initialize payment')
      }
    } catch (error: any) {
      console.error('[useQRMenuOrder] Error initializing walk-in payment:', error)
      let errorMessage = 'Something went wrong. Please try again.'
      if (error.message) {
        errorMessage = error.message
      }
      alert(`Payment Error: ${errorMessage}`)
    } finally {
      isPlacingOrder.value = false
    }
  }

  return {
    isPlacingOrder,
    orderNumber,
    estimatedTime,
    showSuccessModal,
    placeOrderWithRoomCharge,
    initializeWalkInPayment,
  }
}
